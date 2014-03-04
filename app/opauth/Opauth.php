<?php
/**
 * Opauth
 * Multi-provider authentication framework for PHP
 *
 * Modified to adapt to autoloader environment
 * 
 * @copyright    Copyright © 2012 U-Zyn Chua (http://uzyn.com)
 * @link         http://opauth.org
 * @package      Opauth
 * @license      MIT License
 */

/**
 * Opauth
 * Multi-provider authentication framework for PHP
 * 
 * @package			Opauth
 */

namespace opauth;

class Opauth{
	/**
	 * User configuraable settings
	 * Refer to example/opauth.conf.php.default or example/opauth.conf.php.advanced for sample
	 * More info: https://github.com/uzyn/opauth/wiki/Opauth-configuration
	 */
	public $config;	
	
	/**
	 * Environment variables
	 */
	public $env;
	
	/** 
	 * Strategy map: for mapping URL-friendly name to Class name
	 */
	public $strategyMap;
	
	public $f3;
	
	/**
	 * Constructor
	 * Loads user configuration and strategies.
	 * 
	 * @param array $config User configuration
	 * @param boolean $run Whether Opauth should auto run after initialization.
	 */
	public function __construct($f3, $config = array(), $params = array(), $run = true){
		/**
		 * Configurable settings
		 */
		 
		$this->f3 = $f3;
		
		$this->config = array_merge(array(
			'host' => ((array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'],
			'callback_url' => '{path}callback',
			'callback_transport' => 'session',
			'debug' => true,
			'security_iteration' => 300,
			'security_timeout' => '2 minutes'
		), $config);
		
		/**
		 * Environment variables, including config
		 * Used mainly as accessors
		 */
		$this->env = array_merge(array(
			'request_uri' => $_SERVER['REQUEST_URI'],
			'complete_path' => $this->config['host'].$this->config['path'],
			'params' => $params
		), $this->config);
		
		foreach ($this->env as $key => $value){
			$this->env[$key] = \opauth\OpauthStrategy::envReplace($value, $this->env);
		}
		
		$this->loadStrategies();
		
		if ($run) $this->run();
	}
	
	/**
	 * Run Opauth:
	 * Parses request URI and perform defined authentication actions based based on it.
	 */
	public function run(){
		//$this->parseUri();
		
		if (!empty($this->env['params']['strategy'])){
			if (strtolower($this->env['params']['strategy']) == 'callback'){
				$this->callback();
			} else if (array_key_exists($this->env['params']['strategy'], $this->strategyMap)){
				$name = $this->strategyMap[$this->env['params']['strategy']]['name'];
				$class = $this->strategyMap[$this->env['params']['strategy']]['class'];
				$strategy = $this->env['Strategy'][$name];
				
				
				
				
				// Strip out critical parameters
				$safeEnv = $this->env;
				unset($safeEnv['Strategy']);
				$class_path = "\\opauth\\Strategy\\" . $class . "Strategy";
				$this->Strategy = new $class_path($strategy, $safeEnv, $this->f3);
				
				if (empty($this->env['params']['action'])){
					$this->env['params']['action'] = 'request';
				}
				$this->Strategy->callAction($this->env['params']['action']);
			} else {
				trigger_error('Unsupported or undefined Opauth strategy - '.$this->env['params']['strategy'], E_USER_ERROR);
			}
		} else {
			$sampleStrategy = array_pop($this->env['Strategy']);
			trigger_error('No strategy is requested. Try going to '.$this->env['complete_path'].$sampleStrategy['strategy_url_name'].' to authenticate with '.$sampleStrategy['strategy_name'], E_USER_NOTICE);
		}
	}
	
	
	/**
	 * Load strategies from user-input $config
	 */	
	private function loadStrategies(){
		if (isset($this->env['Strategy']) && is_array($this->env['Strategy']) && count($this->env['Strategy']) > 0){
			foreach ($this->env['Strategy'] as $key => $strategy){
				if (!is_array($strategy)){
					$key = $strategy;
					$strategy = array();
				}
				
				$strategyClass = $key;
				if (array_key_exists('strategy_class', $strategy)) $strategyClass = $strategy['strategy_class'];
				else $strategy['strategy_class'] = $strategyClass;
				
				$strategy['strategy_name'] = $key;
				
				// Define a URL-friendly name
				if (empty($strategy['strategy_url_name'])) $strategy['strategy_url_name'] = strtolower($key);
				$this->strategyMap[$strategy['strategy_url_name']] = array(
					'name' => $key,
					'class' => $strategyClass
				);
				
				$this->env['Strategy'][$key] = $strategy;
			}
		}
		else{
			trigger_error('No Opauth strategies defined', E_USER_ERROR);
		}
	}
		
	/**
	 * Validate $auth response
	 * Accepts either function call or HTTP-based call
	 * 
	 * @param string $input = sha1(print_r($auth, true))
	 * @param string $timestamp = $_REQUEST['timestamp'])
	 * @param string $signature = $_REQUEST['signature']
	 * @param string $reason Sets reason for failure if validation fails
	 * @return boolean true: valid; false: not valid.
	 */
	public function validate($input = null, $timestamp = null, $signature = null, &$reason = null){
		$functionCall = true;
		if (!empty($_REQUEST['input']) && !empty($_REQUEST['timestamp']) && !empty($_REQUEST['signature'])){
			$functionCall = false;
			$provider = $_REQUEST['input'];
			$timestamp = $_REQUEST['timestamp'];
			$signature = $_REQUEST['signature'];
		}
		
		$timestamp_int = strtotime($timestamp);
		if ($timestamp_int < strtotime('-'.$this->env['security_timeout']) || $timestamp_int > time()){
			$reason = "Auth response expired";
			return false;
		}
		
		$hash = \opauth\OpauthStrategy::hash($input, $timestamp, $this->env['security_iteration'], $this->env['security_salt']);
		
		if (strcasecmp($hash, $signature) !== 0){
			$reason = "Signature does not validate";
			return false;
		}
		
		return true;
	}
	
	/**
	 * Callback: prints out $auth values, and acts as a guide on Opauth security
	 * Application should redirect callback URL to application-side.
	 * Refer to example/callback.php on how to handle auth callback.
	 */
	public function callback(){
		echo "<strong>Note: </strong>Application should set callback URL to application-side for further specific authentication process.\n<br>";
		
		$response = null;
		switch($this->env['callback_transport']){
			case 'session':
				$response = $this->f3->get('SESSION.opauth');
				$this->f3->get('SESSION.opauth', null);
				break;
			case 'post':
				$response = unserialize(base64_decode( $_POST['opauth'] ));
				break;
			case 'get':
				$response = unserialize(base64_decode( $_GET['opauth'] ));
				break;
			default:
				echo '<strong style="color: red;">Error: </strong>Unsupported callback_transport.'."<br>\n";
				break;
		}
		
		/**
		 * Check if it's an error callback
		 */
		if (array_key_exists('error', $response)){
			echo '<strong style="color: red;">Authentication error: </strong> Opauth returns error auth response.'."<br>\n";
		}

		/**
		 * No it isn't. Proceed with auth validation
		 */
		else{
			if (empty($response['auth']) || empty($response['timestamp']) || empty($response['signature']) || empty($response['auth']['provider']) || empty($response['auth']['uid'])){
				echo '<strong style="color: red;">Invalid auth response: </strong>Missing key auth response components.'."<br>\n";
			}
			elseif (!$this->validate(sha1(print_r($response['auth'], true)), $response['timestamp'], $response['signature'], $reason)){
				echo '<strong style="color: red;">Invalid auth response: </strong>'.$reason.".<br>\n";
			}
			else{
				echo '<strong style="color: green;">OK: </strong>Auth response is validated.'."<br>\n";
			}
		}		
		
		/**
		 * Auth response dump
		 */
		echo "<pre>";
		print_r($response);
		echo "</pre>";
	}
	
	/**
	 * Prints out variable with <pre> tags
	 * Silence if Opauth is not in debug mode
	 * 
	 * @param mixed $var Object or variable to be printed
	 */	
	public function debug($var){
		if ($this->env['debug'] !== false){
			echo "<pre>";
			print_r($var);
			echo "</pre>";
		}
	}
}