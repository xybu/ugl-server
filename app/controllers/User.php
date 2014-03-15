<?php
namespace controllers;

class User extends \Controller {
	
	protected $user;
	protected $auth;
	protected $isUser = false;
	
	function __construct() {
		parent::__construct();
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
	
	function oauth_connectWith($base) {
		$provider = $base->get('PARAMS.provider');
		$action = $base->get('PARAMS.action');
		
		if ($provider == "callback")
			$oauth_run = false;
		else $oauth_run = true;
		
		try {
			
			$opauth = new \opauth\Opauth($base, $this->oauth_getConfig(), array(
				"strategy" => strtolower($provider),
				"action" => $action
			), $oauth_run);
			
			if (!$oauth_run){ //callback
				$response = null;
				
				switch($opauth->env['callback_transport']){	
					case 'session':
						$response = $base->get('SESSION.opauth');;
						$base->set('SESSION.opauth', null);
						break;
					case 'post':
						$response = unserialize(base64_decode($_POST['opauth']));
						break;
					case 'get':
						$response = unserialize(base64_decode($_GET['opauth']));
						break;
					default:
						throw new \Exception("Unsupported callback_transport", 0);
						break;
				}
				
				if (array_key_exists('error', $response))
					throw new \Exception("Authentication Error: " . $response['error'], 1);
				
				if (empty($response['auth']) || empty($response['timestamp']) || empty($response['signature']) || empty($response['auth']['provider']) || empty($response['auth']['uid']))
					throw new \Exception("Invalid auth response: Missing key auth response components.", 2);
				
				if (!$opauth->validate(sha1(print_r($response['auth'], true)), $response['timestamp'], $response['signature'], $reason))
					throw new \Exception("Invalid response: " . $reason, 3);
				
				// load user and authentication models
				$authentication = new \models\Authentication();
				$user = new \models\User();
				
				// check if user already has authenticated using this provider before				
				$oauth_info = $authentication->findByProviderUid($response['auth']['provider'], $response['auth']['uid']);
				
				// if authentication already exists, reroute to dashboard
				if ($oauth_info){
					$base->set("SESSION.user", array("id" => $oauth_info["user_id"], "ugl_token" => $user->refreshToken($oauth_info["user_id"])));
					$base->reroute("/user/dashboard");
				}
				
				$provider  = $response['auth']['provider']; // replace 'callback' by the real provider
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
					$user_token = null;
					if ($user_info) {
						// the user registered the email, but hasn't assoc with his account
						$user_id = $user_info["id"];
						$user_token = $user->getUserToken($user_id);
						$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
					} else {
						// the user hasn't registered the email
						// User model will generate a random password and send email
						$user_creds = $user->createUser($email, "", $first_name, $last_name, $avatar_url);
						$authentication->createAuth($user_creds["id"], $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
						$user_id = $user_creds["id"];
						$user_token = $user_creds["ugl_token"];
					}
					
					$base->set("SESSION.user", array("id" => $user_id, "ugl_token" => $user_token));
					$base->reroute("/my/dashboard");
				} else
					throw new \Exception("No email specified.", 101);	
			}
		} catch (\Exception $e) {
			//TODO: show a hint page and move on
			$this->json_printException($e);
		}
	}
	
	function oauth_clientCallback($base){
		try {
			if (!$base->exists("POST.data") or !$base->exists("POST.from"))
				throw new \Exception("Missing required POST fields", 1);
			if (!array_key_exists($base->get("POST.from"), API::$API_KEYS))
				throw new \Exception("Unrecognized client", 2);
			
			$oauth_str = urldecode($base->get("POST.data"));
			$oauth_str = base64_decode($oauth_str);
			$oauth_str = API::api_decrypt($oauth_str, API::$API_KEYS[$base->get("POST.from")]);
			if (empty($oauth_str))
				throw new \Exception("Failed to decrypt the information", 3);
			
			
			$oauth_obj = new \models\OAuthObject();
			$oauth_obj->loadJSON($oauth_str);
			$response = $oauth_obj->toArray();
			
			$authentication = new \models\Authentication();
			$user = new \models\User();
			
			$oauth_info = $authentication->findByProviderUid($response['auth']['provider'], $response['auth']['uid']);
			$user_id = null;
			$user_token = null;
			// if authentication already exists, reroute to dashboard
			if ($oauth_info){
				$user_id = $oauth_info["user_id"];
				$user_token = $user->refreshToken($oauth_info["user_id"]);
			} else {
				$provider = $response['auth']['provider']; // replace 'callback' by the real provider
				$provider_uid = $response['auth']['uid'];
				$email = $response['auth']['info']['email'];
				$first_name = $response['auth']['info']['first_name'];
				$last_name = $response['auth']['info']['last_name'];
				$display_name = $response['auth']['info']['name'];
				$avatar_url = $response['auth']['info']['image'];
				
				if (array_key_exists('website', $response['auth']['info']['urls']))
					$website_url   = $response['auth']['info']['urls']['website'];
				else $website_url = "";
				
				if ($email){
					$user_info = $user->findByEmail($email);
					
					if ($user_info) {
						// the user registered the email, but hasn't assoc with his account
						$user_id = $user_info["id"];
						$user_token = $user->refreshToken($user_id);
						$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
					} else {
						// the user hasn't registered the email
						// User model will generate a random password and send email
						$user_creds = $user->createUser($email, "", $first_name, $last_name, $avatar_url);
						$authentication->createAuth($user_creds["id"], $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
						$user_id = $user_creds["id"];
						$user_token = $user_creds["ugl_token"];
					}
				} else throw new \Exception("Email does not exist in the fields", 4);
			}
			$this->json_printResponse(array("user_id" => $user_id, "ugl_token" => $user_token));
		} catch (\Exception $e) {
			$this->json_printException($e);
		}
	}
	
	function showUserPanel($base){
		if (!$base->exists("SESSION.user"))
			$this->backToHomepage($base);
		
		$user = new \models\User();
		$me = $base->get("SESSION.user");
		$panel = $base->get("PARAMS.panel");
		
		if (!$user->verifyToken($me["id"], $me["ugl_token"]))
			$this->backToHomepage($base);
		
		$my_profile = $user->getUserProfile($me["id"]);
		if (!$my_profile) $this->backToHomepage($base);
		
		switch ($panel){
			case "dashboard":
				break;
			case "groups":
				break;
			case "boards":
				break;
			case "items":
				break;
			case "wallet":
				break;
			case "preferences":
				break;
			case "profile":
				break;
			default:
				die();
		}
		
		$base->set('panel', $panel);
		$base->set('me', $my_profile); //hide the token in the view model
		
		$this->setView('usercp.html');
	}
	
	function loadUserPanel($base){
		if (!$base->exists("SESSION.user"))
			die();
		
		$user = new \models\User();
		$me = $base->get("SESSION.user");
		$panel = $base->get("PARAMS.panel");
		
		$my_profile = $user->getUserProfile($me["id"]);
		if (!$my_profile) $this->backToHomepage($base, true, false);
		
		$base->set('me', $my_profile); //hide the token in the view model
		$base->set('panel', $panel);
		
		switch ($panel){
			case "dashboard":
				break;
			case "groups":
				break;
			case "boards":
				break;
			case "items":
				break;
			case "wallet":
				break;
			case "preferences":
				break;
			case "profile":
				break;
			default:
				die();
		}
		
		$this->setView('my_' . $panel . '.html');
	}
	
	function backToHomepage($base, $revokeSession = true, $redirect = true){
		if ($revokeSession) $base->set("SESSION.user", null);
		if ($redirect) $base->reroute("/");
		else die();
	}
	
}