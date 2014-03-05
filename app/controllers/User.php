<?php
namespace controllers;

class User extends \Controller {
	
	protected $session;
	protected $user;
	protected $auth;
	protected $isUser = false;
	
	function __construct() {
		parent::__construct();
		
		//$this->user = new \DB\SQL\Mapper($db, 'Users');
		//$this->auth = new \Auth($user, array('id'=>'userId', 'pw'=>'userPass_hash'));
		
		//$this->isUser = $this->auth->login('admin','secret_pwd');
	}
	
	/**
	 * oauth_getConfig()
	 * 
	 * HybridAuth config implementation
	 */
	function oauth_getConfig(){
		return array(
			'path' => '/oauth/',
			'callback_url' => '{path}callback',
			'security_salt' => 'OtSMJTYIys8phRlkTzGWdxlhbFGDPa5mQevss5c8xtEzsOJgrbgv2Q1eJZmLOdN',
			'callback_transport' => 'session',
			'Strategy' => array(
				'Facebook' => array(
					'app_id' => '1376194325978420',
					'app_secret' => 'e4d275c92c9f73fa4924e15deee55d23'
				),
				'Google' => array(
					'client_id' => 'YOUR CLIENT ID',
					'client_secret' => 'YOUR CLIENT SECRET'
				),
				'Twitter' => array(
					'key' => 'YOUR CONSUMER KEY',
					'secret' => 'YOUR CONSUMER SECRET'
				),
				'GitHub' => array(
					'client_id' => '613dce24298b1abd2c39',
					'client_secret' => '60f896c4c4a3f4c6e8eb83ed9c266657e7f4ba3e'
				),
				'Live' => array(
					'client_id' => '0000000048114882',
					'client_secret' => 'vhfhYtlwTUW1KkJdnKHEOSSRlqcjOm7T'
				)
			)
		);
	}
	
	function oauth_connectWith($f3) {
		$provider = $f3->get('PARAMS.provider');
		$action = $f3->get('PARAMS.action');
		
		if ($provider == "callback")
			$oauth_run = false;
		else $oauth_run = true;
		
		try {
			
			$opauth = new \opauth\Opauth($f3, $this->oauth_getConfig(), array(
				"strategy" => strtolower($provider),
				"action" => $action
			), $oauth_run);
			
			if (!$oauth_run){ //callback
				$response = null;
				
				switch($opauth->env['callback_transport']){	
					case 'session':
						$response = $f3->get('SESSION.opauth');;
						$f3->set('SESSION.opauth', null);
						break;
					case 'post':
						$response = unserialize(base64_decode($_POST['opauth']));
						break;
					case 'get':
						$response = unserialize(base64_decode($_GET['opauth']));
						break;
					default:
						throw new Exception("Unsupported callback_transport", 0);
						break;
				}
				
				if (array_key_exists('error', $response))
					throw new Exception("Authentication Error: " . $response['error'], 1);
				
				if (empty($response['auth']) || empty($response['timestamp']) || empty($response['signature']) || empty($response['auth']['provider']) || empty($response['auth']['uid']))
					throw new Exception("Invalid auth response: Missing key auth response components.", 2);
				
				if (!$opauth->validate(sha1(print_r($response['auth'], true)), $response['timestamp'], $response['signature'], $reason))
					throw new Exception("Invalid response: " . $reason, 3);
				
				// load user and authentication models
				$authentication = new \models\Authentication();
				$user = new \models\User();
				
				// check if user already has authenticated using this provider before				
				$authentication_info = $authentication->findByProviderUid($response['auth']['provider'], $response['auth']['uid']);
				
				// if authentication already exists, reroute to dashboard
				if ($authentication_info){
					$f3->set("SESSION.user", $authentication_info["user_id"]);
					$f3->reroute("/user/dashboard");
				}
				
				$provider_uid  = $response['auth']['uid'];
				$email         = $response['auth']['info']['email'];
				$first_name    = $response['auth']['info']['first_name'];
				$last_name     = $response['auth']['info']['last_name'];
				$display_name  = $response['auth']['info']['name'];
				$avatar_url   = $response['auth']['info']['image'];

				if (array_key_exists('website', $response['auth']['info']['urls']))
					$website_url   = $response['auth']['info']['urls']['website'];
				else $website_url = "";
				
				if ($email){
					
					$user_info = $user->findByEmail($email);
					$user_id = null;
					
					if ($user_info) {
						// the user registered the email, but hasn't assoc with his account
						$user_id = $user_info["id"];
						$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
					} else {
						// the user hasn't registered the email
						$password = rand();
						$user_id = $user->createUser($email, $password, $first_name, $last_name, $avatar_url);
						$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
						
						//TODO: can send an email later
					}
					
					$f3->set("SESSION.user", $user_id);
					$f3->reroute("/user/dashboard");
				} else
					throw new Exception("No email specified.", 101);	
			}
		} catch( Exception $e ) {
			//TODO: show a hint page and move on
			$this->json_printException($e);
		}
	}
	
	function showDashboard($f3) {
		if (!$f3->exists("SESSION.user"))
			$f3->reroute("/");
		
		$f3->set('page_title','Dashboard | Ugli');
		$f3->set('header','header.html');
		$f3->set('footer','footer.html');
		$this->setView("dashboard.html");
	}
	
	
}