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
					API::setUserStatus($base, $user, $oauth_info["user_id"], $user->token_refresh(array("id" => $oauth_info["user_id"])));
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
					$user_token = $user->token_get($user_info);
					$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
				} else {
					// the user hasn't registered the email
					// User model will generate a random password and send email
					$user_creds = $user->create($email, "", $first_name, $last_name, $avatar_url);
					$user_id = $user_creds["id"];
					$user_token = $user_creds["ugl_token"];
					$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
					$reroute_panel = "settings";
				}
				API::setUserStatus($base, $user, $user_id, $user_token);
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
				$user_token = $user->token_refresh(array("id" => $oauth_info["user_id"]));
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
						$user_token = $user->token_refresh($user_info);
						$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
					} else {
						// the user hasn't registered the email
						// User model will generate a random password and send email
						$user_creds = $user->create($email, "", $first_name, $last_name, $avatar_url);
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
	
	function showUserPanel($base, $args){
		$user = new \models\User();
		try {
			$session_creds = API::getUserStatus($base, $user);
		} catch (\Exception $e) {
			$this->backToHomepage($base);
		}
		
		$me = $session_creds["user_info"];
		//var_dump($me);
		//die();
		
		if (empty($me) or !$user->token_verify($me, $session_creds["ugl_token"]))
			$this->backToHomepage($base);
		
		$panel = $args["panel"];
		
		try {
			switch ($panel){
				case "dashboard":
					break;
				case "groups":
					$group = new \models\Group();
					$group_list = $group->listGroupsOfUserId($me["id"], $group::STATUS_INACTIVE);
					$base->set("groupList", $group_list);
					break;
				case "group":
					$panel = "groups";
					$item_id = $args["item_id"];
					if (!is_numeric($item_id))
						throw new \Exception("Group id should be a number", 3);
					
					$group = new \models\Group();
					$group_info = $group->findById($item_id);
					
					if (!$group_info)
						throw new \Exception("Group not found", 4);
					
					$my_permissions = $group->getPermissions($me["id"], $item_id, $group_info);
					
					if (!$my_permissions["view_profile"])
						throw new \Exception("You are not allowed to view the profile of this group", 5);
					
					$new_users = array();
					foreach ($group_info["users"] as $role => $ids){
						foreach ($ids as $key => $val)
							$new_users[$role][] = $user->filterOutPrivateKeys($user->findById($val));
					}
				
					$group_info["users"] = $new_users;
					
					$base->set("my_permissions", $my_permissions);
					$base->set("group_info", $group_info);
					$sub_panel = "group";
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
		$base->set('me', $me);
		$this->setView('usercp.html');
	}
	
	function loadUserPanel($base, $args){
		$user = new \models\User();
		try {
			$session_creds = API::getUserStatus($base, $user);
		} catch (\Exception $e) {
			die();
		}
		
		$me = $session_creds["user_info"];
		
		$panel = $args["panel"];
		
		try {
			switch ($panel){
				case "dashboard":
					break;
				case "groups":
					$group = new \models\Group();
					$group_list = $group->listGroupsOfUserId($me["id"], $group::STATUS_INACTIVE);
					$base->set("groupList", $group_list);
					break;
				case "group":
					$panel = "groups";
					$item_id = $args["item_id"];
					if (!is_numeric($item_id))
						throw new \Exception("Group id should be a number", 3);
					
					$group = new \models\Group();
					$group_info = $group->findById($item_id);
					
					if (!$group_info)
						throw new \Exception("Group not found", 4);
					
					$my_permissions = $group->getPermissions($me["id"], $item_id, $group_info);
					
					if (!$my_permissions["view_profile"])
						throw new \Exception("You are not allowed to view the profile of this group", 5);
					
					$new_users = array();
					foreach ($group_info["users"] as $role => $ids){
						foreach ($ids as $key => $val)
							$new_users[$role][] = $user->filterOutPrivateKeys($user->findById($val));
					}
				
					$group_info["users"] = $new_users;
					
					$base->set("my_permissions", $my_permissions);
					$base->set("group_info", $group_info);
					$sub_panel = "group";
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
		
		$base->set('me', $me); //hide the token in the view model
		$base->set('panel', $panel);
		if (isset($sub_panel)) $this->setView($sub_panel . '.html');
		else $this->setView('my_' . $panel . '.html');
	}
	
	function loadUserModal($base, $args){
		$user = new \models\User();
		try {
			$session_creds = API::getUserStatus($base, $user);
		} catch (\Exception $e) {
			die();
		}
		
		$me = $session_creds["user_info"];
		
		if (empty($me) or !$user->token_verify($me, $session_creds["ugl_token"]))
			die();
		
		$panel = $args["panel"];
		$item_id = $args["id"];
		$modal = $args["modal"];
		
		try {
			switch ($panel){
				case "group":
					switch ($modal){
						case "man":
							if (!is_numeric($item_id))
								throw new \Exception("Group id should be a number", 3);
							
							$group = new \models\Group();
							$group_info = $group->findById($item_id);
						
							if (!$group_info)
								throw new \Exception("Group not found", 4);
							
							$my_permissions = $group->getPermissions($me["id"], $item_id, $group_info);
							
							if (!$my_permissions["manage"])
								throw new \Exception("Invalid request", 5);
							
							$new_users = array();
							
							foreach ($group_info["users"] as $role => $ids){
								foreach ($ids as $key => $val)
									$new_users[$role][] = $user->findById($val);
							}
							
							$group_info["users"] = $new_users;
							
							$base->set("my_permissions", $my_permissions);
							$base->set("group_info", $group_info);
							
							break;
						default:
							break;
					}
					break;
				default:
					die();
			}
			$base->set('me', $me);
			$base->set('panel', $panel);
			$this->setView($panel . '_' . $modal . '.html');
		} catch (\Exception $e) {
			throw $e;
		}
	}
	
	function backToHomepage($base, $revokeSession = true, $redirect = true){
		//var_dump($base->get("SESSION.user"));
		//die();
		if ($revokeSession) API::voidUserStatus($base);
		if ($redirect) $base->reroute("/");
		else die();
	}
	
	function api_register($base){
		try {
			if (!$base->exists("POST.agree") or $base->get("POST.agree") != "true")
				throw new \Exception("You must agree to the terms of services to sign up", 105);
			
			if (!$base->exists("POST.email") or !$base->exists("POST.password") or !$base->exists("POST.confirm_pass") or 
				!$base->exists("POST.first_name") or !$base->exists("POST.last_name"))
					throw new \Exception("Email, password, or name not provided", 100);
			
			$user = new \models\User();
			
			$email = $base->get("POST.email");
			
			if (!$user->isValidEmail($email))
				throw new \Exception("Invalid email address", 101);
			
			$password = $base->get("POST.password");
			if (!$user->isValidPassword($password))
				throw new \Exception("Password should be at least 6 chars", 106);
			
			$confirm_password = $base->get("POST.confirm_pass");
			if ($password != $confirm_password)
				throw new \Exception("Password and confirm password do not match", 102);
			
			$first_name = $user->filterHtmlChars($base->get("POST.first_name"));
			$last_name = $user->filterHtmlChars($base->get("POST.last_name"));
			
			if (!$user->isValidName($first_name) or !$user->isValidName($last_name))
				throw new \Exception("First name or last name should be non-empty words", 103);
			
			$user_info = $user->findByEmail($email);
			if ($user_info)
				throw new \Exception("Email already registered", 104);
			
			$new_user_info = $user->create($email, $password, $first_name, $last_name);
			API::setUserStatus($base, $user, $new_user_info["id"], $new_user_info["ugl_token"]);
			$this->json_printResponse(array("user_id" => $new_user_info["id"]));
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_getInfo($base, $args){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			$target_user_id = $args["target_user_id"];
			$target_user_info = null;
			if ($target_user_id != $user_id){
				if (empty($target_user_id) or !is_numeric($target_user_id)) throw new \Exception("Invalid user id", 3);
				$target_user_info = $user->findById($target_user_id);
				if (empty($target_user_info)) throw new \Exception("User not found", 4);
				if (!$target_user_info["_preferences"]["showMyProfile"])
					 throw new \Exception("The profile is set private", 5);
				$user->removePrivateKeys($target_user_info, 1);
			} else {
				$target_user_info = $user->filterOutPrivateKeys($user_info, 2);
			}
			$this->json_printResponse($target_user_info);
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_setInfo($base){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
			if ($base->exists("POST.email")){
				$email = $base->get("POST.email");
				if (!$user->isValidEmail($email))
					throw new \Exception("Invalid email address", 3);
				else if ($email != $user_info["email"]){
					$dup_user_info = $user->findByEmail($email);
					if ($dup_user_info) throw new \Exception("Email already registered", 4);
				}
				$user_info["email"] = $email;
			}
			
			if ($base->exists("POST.first_name"))
				$first_name = $user->filterHtmlChars($base->get("POST.first_name"));
			else $first_name = $user_info["first_name"];
			
			if ($base->exists("POST.last_name"))
				$last_name = $user->filterHtmlChars($base->get("POST.last_name"));
			else $last_name = $user_info["last_name"];
			
			if ($base->exists("POST.nickname"))
				$nickname = $user->filterHtmlChars($base->get("POST.nickname"));
			else $nickname = $user_info["nickname"];
			
			if (!$user->isValidName($first_name) or !$user->isValidName($last_name))
				throw new \Exception("First name or last name should be non-empty words", 5);
			
			if ($base->exists("POST.avatar_url")){
				$avatar_url = $base->get("POST.avatar_url");
				if (empty($avatar_url)) $avatar_url = "";
				$user_info["avatar_url"] = $avatar_url;
			}
			
			if ($base->exists("POST.phone")){
				$phone = $base->get("POST.phone");
				if (empty($phone)) $phone = "";
				$user_info["phone"] = $phone;
			}
			
			if ($base->exists("POST.description")){
				$description = $user->filterDescription($base->get("POST.description"));
				if (empty($description)) $description = "";
				$user_info["description"] = $description;
			}
			
			$prefs = $user_info["_preferences"];
			
			foreach ($prefs as $key => $value){
				if ($base->exists("POST." . $key)){
					$post_val = $base->get("POST." . $key);
					if (is_bool($value)){
						if ($post_val != "" . $value) $prefs[$key] = !$value;
					}
				}
			}
			
			$user_info["first_name"] = $first_name;
			$user_info["last_name"] = $last_name;
			$user_info["nickname"] = $nickname;
			$user_info["_preferences"] = $prefs;
			
			$user->save($user_info);
			
			$user->removePrivateKeys($user_info, 2);
			$this->json_printResponse($user_info);
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_uploadAvatar($base){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
			$upload = new \models\Upload();
			$numOfFiles = $upload->uploadImages($base->get("UPLOAD_AVATARS_DIR"), array("user_" . $user_id . ".png"));
			
			if ($numOfFiles == 1) {
				$new_avatar_url = $base->get("UPLOAD_AVATARS_DIR") . "user_" . $user_id . ".png";
				$user_info["avatar_url"] = $new_avatar_url;
				$user->save($user_info);
				$this->json_printResponse(array("avatar_url" => $new_avatar_url));
			} else if ($numOfFiles == 0)
				throw new \Exception("File upload failed. Please check if the file is an image of JPEG, PNG, or GIF format with size no more than 100KiB.", 3);
				// should use $upload->MAX_AVATAR_FILE_SIZE as max file size
			else trigger_error("Uploaded more than one file: " . $numOfFiles, E_USER_ERROR);
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
}