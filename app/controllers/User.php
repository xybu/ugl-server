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
					'client_id' => '1066850527889-9bkq0vhvljp8ou765vagouk2jbgdrt9t.apps.googleusercontent.com',
					'client_secret' => 'ewKUAvKJhm9OmAXW7f5Apisj'
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
						$base->clear('SESSION.opauth');
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
				
				if (empty($response))
					throw new \Exception("Error occurred during authentication. Please retry.");
				
				if (array_key_exists('error', $response))
					throw new \Exception("Authentication Error: " . $response['error']['message'], 1);
				
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
					$base->reroute("@usercp(@panel=dashboard)");
				}
				
				if (!array_key_exists("email", $response['auth']['info']))
					throw new \Exception("Sorry, your provide did not share your email address with Ugl. Please register directly.", 101);
				
				$provider  = $response['auth']['provider']; // replace 'callback' by the real provider
				$provider_uid  = $response['auth']['uid'];
				$email         = $response['auth']['info']['email'];
				$first_name    = array_key_exists("first_name", $response['auth']['info']) ? $response['auth']['info']['first_name'] : "";
				$last_name     = array_key_exists("last_name", $response['auth']['info']) ? $response['auth']['info']['last_name'] : "";
				$display_name  = $response['auth']['info']['name'];
				$avatar_url   = $response['auth']['info']['image'];
				
				if (array_key_exists('website', $response['auth']['info']['urls']))
					$website_url   = $response['auth']['info']['urls']['website'];
				else $website_url = "";
				
				$user_info = $user->findByEmail($email);
				$user_id = null;
				$user_token = null;
				$reroute_panel = "dashboard";
				if ($user_info) {
					// the user registered the email, but hasn't assoc with his account
					$user_id = $user_info["id"];
					$user_token = $user->getUserToken($user_id);
					$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
				} else {
					// the user hasn't registered the email
					// User model will generate a random password and send email
					$user_creds = $user->createUser($email, "", $first_name, $last_name, $avatar_url);
					$user_id = $user_creds["id"];
					$user_token = $user_creds["ugl_token"];
					$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
					$reroute_panel = "settings";
				}
				$base->set("SESSION.user", array("id" => $user_id, "ugl_token" => $user_token));
				$base->reroute("@usercp(@panel=". $reroute_panel .")");
					
			}
		} catch (\Exception $e) {
			$base->set("rt_notification_modal", array(
				"type" => "warning", 
				"title" => "Authentication failed", 
				"message" => $e->getMessage())
			);
			$base->set('page_title','Unified Group Life');
			$this->setView('homepage.html');
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
						$user_id = $user_creds["id"];
						$user_token = $user_creds["ugl_token"];
						$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
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
		
		if ($base->exists("SESSION.loginFail_count"))
			$base->clear("SESSION.loginFail_count");
		
		$user = new \models\User();
		$me = $base->get("SESSION.user");
		$panel = $base->get("PARAMS.panel");
		
		if (!$user->verifyToken($me["id"], $me["ugl_token"]))
			$this->backToHomepage($base);
		
		$my_profile = $user->getUserProfile($me["id"]);
		if (!$my_profile) $this->backToHomepage($base);
		
		try {
			switch ($panel){
				case "dashboard":
					break;
				case "group":
					$panel = "groups";
					$item_id = $base->get("PARAMS.item_id");
					if (!is_numeric($item_id))
						throw new \Exception("Group id should be a number", 3);
					
					$group = new \models\Group();
					$group_info = $group->findById($item_id);
					
					if (!$group_info)
						throw new \Exception("Group not found", 4);
					
					$my_permissions = $group->getPermissions($me["id"], $item_id, $group_info);
					
					if (!$my_permissions["view_profile"])
						throw new \Exception("You are not allowed to view the profile of this group", 5);
					$base->set("my_permissions", $my_permissions);
					$base->set("group_info", $group_info);
					$sub_panel = "group";
				case "groups":
					$group = new \models\Group();
					$group_list = $group->listGroupsOfUserId($me["id"], 0);
					$base->set("groupList", $group_list);
					break;
				case "boards":
					break;
				case "items":
					break;
				case "wallet":
					break;
				case "preferences":
					break;
				case "settings":
					break;
				default:
					die();
			}
			
		} catch (\Exception $e){
			$sub_panel = "usercp_error";
			$base->set("exception", $e);
		}
		
		if (isset($sub_panel)) $base->set('sub_panel', $sub_panel);
		$base->set('panel', $panel);
		$base->set('me', $my_profile);
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
		
		try {
			switch ($panel){
				case "dashboard":
					break;
				case "group":
					$panel = "groups";
						$item_id = $base->get("PARAMS.item_id");
						if (!is_numeric($item_id))
							throw new \Exception("Group id should be a number", 3);
						
						$group = new \models\Group();
						$group_info = $group->findById($item_id);
						
						if (!$group_info)
							throw new \Exception("Group not found", 4);
						
						$my_permissions = $group->getPermissions($me["id"], $item_id, $group_info);
						
						if (!$my_permissions["view_profile"])
							throw new \Exception("You are not allowed to view the profile of this group", 5);
						$base->set("my_permissions", $my_permissions);
						$base->set("group_info", $group_info);
						$sub_panel = "group";
				case "groups":
					$group = new \models\Group();
					$group_list = $group->listGroupsOfUserId($me["id"], 0);
					$base->set("groupList", $group_list);
					break;
				case "boards":
					break;
				case "items":
					break;
				case "wallet":
					break;
				case "preferences":
					break;
				case "settings":
					break;
				default:
					die();
			}
		} catch (\Exception $e){
			$sub_panel = "usercp_error";
			$base->set("exception", $e);
		}
		
		if (isset($sub_panel))
			$this->setView($sub_panel . '.html');
		else $this->setView('my_' . $panel . '.html');
	}
	
	function backToHomepage($base, $revokeSession = true, $redirect = true){
		if ($revokeSession) $base->clear("SESSION.user");
		if ($redirect) $base->reroute("/");
		else die();
	}
	
}