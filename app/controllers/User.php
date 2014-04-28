<?php
namespace controllers;

class User extends \Controller {
	
	const RSTPWD_REQ_PER_SESSION = 3;
	const LOGIN_REQ_PER_SESSMION = 5;
	const RSTPWD_REQ_EXPIRATION = 24; // in hrs
	
	function __construct() {
		parent::__construct();
	}
	
	/**
	 * oauth_getConfig()
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
				$authentication = \models\Authentication::instance();
				$user = \models\User::instance();
				
				// check if user already has authenticated using this provider before				
				$oauth_info = $authentication->findByProviderUid($response['auth']['provider'], $response['auth']['uid']);
				
				// if authentication already exists, reroute to dashboard
				if ($oauth_info){
					$user_info = $user->findById($oauth_info["user_id"]);
					$this->setUserStatus($oauth_info["user_id"], $user->token_refresh($user_info));
					$base->reroute("@usercp(@panel=groups)");
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
				$reroute_panel = "groups";
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
				$this->setUserStatus($user_id, $user_token);
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
			$client_key_name = strtoupper($base->get("POST.from") . "") . "_KEY";
			
			if (!$base->exists("POST.data") or !$base->exists("POST.from"))
				throw new \Exception("Missing required POST fields", 1);
			if (!$base->exists($client_key_name))
				throw new \Exception("Unrecognized client", 2);
			
			$oauth_str = urldecode($base->get("POST.data"));
			$oauth_str = base64_decode($oauth_str);
			$oauth_str = self::api_decrypt($oauth_str, $base->get($client_key_name));
			if (empty($oauth_str))
				throw new \Exception("Failed to decrypt the information", 3);
			
			$oauth_obj = new \models\OAuthObject();
			$oauth_obj->loadJSON($oauth_str);
			$response = $oauth_obj->toArray();
			
			$authentication = \models\Authentication::instance();
			$user = \models\User::instance();
			
			$oauth_info = $authentication->findByProviderUid($response['auth']['provider'], $response['auth']['uid']);
			$user_id = null;
			$user_token = null;
			$user_info = null;
			// if authentication already exists, reroute to dashboard
			if ($oauth_info){
				$user_id = $oauth_info["user_id"];
				$user_info = $user->findById($oauth_info["user_id"]);
				$user_token = $user->token_refresh($user_info);
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
						$user_info = $user->create($email, "", $first_name, $last_name, $avatar_url);
						$user_id = $user_info["id"];
						$user_token = $user_info["ugl_token"];
						$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url);
					}
				} else throw new \Exception("Email does not exist in the fields", 4);
			}
			$this->setUserStatus($user_id, $user_token);
			$this->json_printResponse(array("user_id" => $user_id));
		} catch (\Exception $e) {
			$this->json_printException($e);
		}
	}
	
	function showUserPanel($base, $args){		
		try {
			$session_creds = $this->getUserStatus();
		} catch (\Exception $e) {
			$this->backToHomepage($base);
		}
		$user = $this->user;
		$me = $session_creds["user_info"];
		
		$group = \models\Group::instance();
		
		if ($base->exists("SESSION.invitation")) {
			$invited_group = $group->findById($base->get("SESSION.invitation")["group_id"]);
			$group->addUser($me["id"], "member", $invited_group);
			$user->joinGroup($me, $invited_group["id"]);
			$user->save($me);
			$group->save($invited_group);
			$base->set("rt_notification_modal", array(
				"type" => "success", 
				"title" => "Accepting Invitation", 
				"message" => "You have successfully joined the group " . $invited_group["alias"] . ".")
			);
			$base->clear("SESSION.invitation");
		}
		
		$panel = $args["panel"];
		
		try {
			switch ($panel){
				case "dashboard":
					break;
				case "groups":
					$group_list = $group->listGroupsOfUserId($me["id"], $group::STATUS_INACTIVE);
					$base->set("groupList", $group_list);
					break;
				case "group":
					$panel = "groups";
					$item_id = $args["item_id"];
					if (!is_numeric($item_id))
						throw new \Exception("Group id should be a number", 3);
					
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
					
					$Board = \models\Board::instance();
					$board_list = $Board->findByGroupId($item_id);
					$discussion = \models\Discussion::instance();
					if ($board_list["count"] > 0)
						foreach ($board_list["boards"] as $keyId => &$board){
							$board["discussion_list"] = $discussion->listByBoardId($board["id"]);
						}
					
					$base->set("board_list", $board_list);
					
					$base->set("my_permissions", $my_permissions);
					$base->set("group_info", $group_info);
					$sub_panel = "group";
					break;
				case "boards":
					$group = \models\Group::instance();
					foreach ($me["_joined_groups"] as $key => $id) {
						$me["_joined_groups"][$key] = $group->findById($id);
						$me["_joined_groups"][$key]["my_permissions"] = $group->getPermissions($me["id"], $id, $me["_joined_groups"][$key]);
					}
					$board = \models\Board::instance();
					$board_list = $board->findByUserId($me["id"]);
					$discussion = \models\Discussion::instance();
					if ($board_list["count"] > 0)
						foreach ($board_list["boards"] as $keyId => &$board){
							$board["discussion_list"] = $discussion->listByBoardId($board["id"]);
						}
					$base->set("board_list", $board_list);
					break;
				case "items":
					$Group = \models\Group::instance();
					foreach ($me["_joined_groups"] as $key => $id) {
						$me["_joined_groups"][$key] = $Group->findById($id);
						$me["_joined_groups"][$key]["my_permissions"] = $Group->getPermissions($me["id"], $id, $me["_joined_groups"][$key]);
					}
					$Shelf = \models\Shelf::instance();
					$shelf_list = $Shelf->findByUserId($me["id"]);
					if ($shelf_list["count"] > 0)
						foreach ($shelf_list["shelves"] as &$s){
							$s["item_list"] = $Shelf->findItemsByShelfId($s["id"]);
						}
					$base->set("shelf_list", $shelf_list);
					break;
				case "wallets":
					$group = \models\Group::instance();
					foreach ($me["_joined_groups"] as $key => $id) {
						$me["_joined_groups"][$key] = $group->findById($id);
						$me["_joined_groups"][$key]["my_permissions"] = $group->getPermissions($me["id"], $id, $me["_joined_groups"][$key]);
					}
					$Wallet = \models\Wallet::instance();
					$wallet_list = $Wallet->findByUserId($me["id"]);
					if ($wallet_list["count"] > 0)
						foreach ($wallet_list["wallets"] as $key => &$wallet_info) {
							$wallet_info["records"] = $Wallet->findRecordsByWalletId($wallet_info["id"], 1, 5);
						}
					$base->set("wallet_list", $wallet_list);
					break;
				case "wallet":
					$wallet_id = $args["item_id"];
					if (!is_numeric($wallet_id) or empty($wallet_id))
						die();
					
					$Wallet = \models\Wallet::instance();
					$wallet_info = $Wallet->findById($wallet_id);
					if ($wallet_info["group_id"]) {
						$Group = \models\Group::instance();
						$group_info = $Group->findById($wallet_info["group_id"]);
						if (!$group_info) die();
						
						$my_permissions = $Group->getPermissions($me["id"], $wallet_info["group_id"], $group_info);
						if (!$my_permissions["view_wallet"])
							die();
						
						$base->set("my_permissions", $my_permissions);
						$base->set("group_info", $group_info);
					}
					
					$base->set("wallet_item", $wallet_info);
					$panel = "wallets";
					$sub_panel = "wallet";
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
		try {
			$session_creds = $this->getUserStatus();
		} catch (\Exception $e) {
			die();
		}
		$user = $this->user;
		$me = $session_creds["user_info"];
		
		$panel = $args["panel"];
		
		try {
			switch ($panel){
				case "dashboard":
					break;
				case "groups":
					$group = \models\Group::instance();
					$group_list = $group->listGroupsOfUserId($me["id"], $group::STATUS_INACTIVE);
					$base->set("groupList", $group_list);
					break;
				case "group":
					$panel = "groups";
					$item_id = $args["item_id"];
					if (!is_numeric($item_id))
						throw new \Exception("Group id should be a number", 3);
					
					$group = \models\Group::instance();
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
					
					$Board = \models\Board::instance();
					$board_list = $Board->findByGroupId($item_id);
					$discussion = \models\Discussion::instance();
					if ($board_list["count"] > 0)
						foreach ($board_list["boards"] as $keyId => &$board){
							$board["discussion_list"] = $discussion->listByBoardId($board["id"]);
						}
					
					$base->set("board_list", $board_list);
					
					
					$base->set("my_permissions", $my_permissions);
					$base->set("group_info", $group_info);
					$sub_panel = "group";
					break;
				case "boards":
					$group = \models\Group::instance();
					foreach ($me["_joined_groups"] as $key => $id) {
						$me["_joined_groups"][$key] = $group->findById($id);
						$me["_joined_groups"][$key]["my_permissions"] = $group->getPermissions($me["id"], $id, $me["_joined_groups"][$key]);
					}
					$board = \models\Board::instance();
					$board_list = $board->findByUserId($me["id"]);
					$discussion = \models\Discussion::instance();
					if ($board_list["count"] > 0)
						foreach ($board_list["boards"] as $keyId => &$board){
							$board["discussion_list"] = $discussion->listByBoardId($board["id"]);
						}
					//var_dump($board_list);
					//die();
					$base->set("board_list", $board_list);
					break;
				case "items":
					$Group = \models\Group::instance();
					foreach ($me["_joined_groups"] as $key => $id) {
						$me["_joined_groups"][$key] = $Group->findById($id);
						$me["_joined_groups"][$key]["my_permissions"] = $Group->getPermissions($me["id"], $id, $me["_joined_groups"][$key]);
					}
					$Shelf = \models\Shelf::instance();
					$shelf_list = $Shelf->findByUserId($me["id"]);
					if ($shelf_list["count"] > 0)
						foreach ($shelf_list["shelves"] as &$s){
							$s["item_list"] = $Shelf->findItemsByShelfId($s["id"]);
						}
					$base->set("shelf_list", $shelf_list);
					break;
				case "wallets":
					$group = \models\Group::instance();
					foreach ($me["_joined_groups"] as $key => $id) {
						$me["_joined_groups"][$key] = $group->findById($id);
						$me["_joined_groups"][$key]["my_permissions"] = $group->getPermissions($me["id"], $id, $me["_joined_groups"][$key]);
					}
					$Wallet = \models\Wallet::instance();
					$wallet_list = $Wallet->findByUserId($me["id"]);
					if ($wallet_list["count"] > 0)
						foreach ($wallet_list["wallets"] as $key => &$wallet_info) {
							$wallet_info["records"] = $Wallet->findRecordsByWalletId($wallet_info["id"], 1, 5);
						}
					$base->set("wallet_list", $wallet_list);
					break;
				case "wallet":
					$wallet_id = $args["item_id"];
					if (!is_numeric($wallet_id) or empty($wallet_id))
						die();
					
					$Wallet = \models\Wallet::instance();
					$wallet_info = $Wallet->findById($wallet_id);
					if ($wallet_info["group_id"]) {
						$Group = \models\Group::instance();
						$group_info = $Group->findById($wallet_info["group_id"]);
						if (!$group_info) die();
						
						$my_permissions = $Group->getPermissions($me["id"], $wallet_info["group_id"], $group_info);
						if (!$my_permissions["view_wallet"])
							die();
						
						$base->set("my_permissions", $my_permissions);
						$base->set("group_info", $group_info);
					}
					$base->set("wallet_item", $wallet_info);
					$panel = "wallets";
					$sub_panel = "wallet";
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
		try {
			$session_creds = $this->getUserStatus();
		} catch (\Exception $e) {
			die();
		}
		$user = $this->user;
		$me = $session_creds["user_info"];
		
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
							
							$group = \models\Group::instance();
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
		if ($revokeSession) $this->voidUserStatus();
		if ($redirect) $base->reroute("/");
		else die();
	}
	
	function api_loginUser($base){
		try {
			if ($base->exists("SESSION.loginFail_count") && intval($base->get("SESSION.loginFail_count")) > static::LOGIN_REQ_PER_SESSMION)
				throw new \Exception("Your account is temporarily on held for security concern. Please retry later or log in with your social account", 104);
			
			if (!$base->exists("POST.email") or !$base->exists("POST.password"))
				throw new \Exception("Email or password not provided", 100);
			
			$this->user = \models\User::instance();
			$email = $base->get("POST.email");
			$password = $base->get("POST.password");
			
			if (!$this->user->isValidEmail($email))
				throw new \Exception("Invalid email address", 101);
			
			if (!$this->user->isValidPassword($password))
				throw new \Exception("Password should not be empty", 103);
			
			$base->clear("COOKIE.ugl_user");
			
			$user_data = $this->user->findByEmailAndPassword($email, $password);
			
			// user found?
			if ($user_data){
				$this->setUserStatus($user_data["id"], $user_data["ugl_token"]);
				if ($base->exists("SESSION.loginFail_count")) $base->clear("SESSION.loginFail_count");
				$this->json_printResponse(array("user_id" => $user_data["id"]));
			} else {
				if ($base->exists("SESSION.loginFail_count")){
					$base->set("SESSION.loginFail_count", intval($base->get("SESSION.loginFail_count")) + 1, 0);
				} else $base->set("SESSION.loginFail_count", 1, 1800);
				
				throw new \Exception("User not found, or email and password do not match.", 102);
			}
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function web_logoutUser($base){
		if ($base->exists("COOKIE.ugl_user")){
			$this->api_revokeToken($base, true);
			self::voidUserStatus($base);
		}
		$this->json_printResponse(array("message" => "You have successfully logged out"));
	}
	
	function api_revokeToken($base, $no_output = false){
		try {
			$user_status = $this->getUserStatus();
			$this->user->token_refresh($user_status["user_info"]);
			if (!$no_output) $this->json_printResponse(array("message" => "Token has been revoked."));
		} catch (\Exception $e){
			if (!$no_output) $this->json_printException($e);
		}
	}
	
	function api_register($base){
		try {
			if (!$base->exists("POST.agree") or $base->get("POST.agree") != "true")
				throw new \Exception("You must agree to the terms of services to sign up", 105);
			
			if (!$base->exists("POST.email") or !$base->exists("POST.password") or !$base->exists("POST.confirm_pass") or 
				!$base->exists("POST.first_name") or !$base->exists("POST.last_name"))
					throw new \Exception("Email, password, or name not provided", 100);
			
			$user = \models\User::instance();
			
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
			$this->setUserStatus($new_user_info["id"], $new_user_info["ugl_token"]);
			$this->json_printResponse(array("user_id" => $new_user_info["id"]));
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_getInfo($base, $args){
		try {
			$user_status = $this->getUserStatus();
			$user = $this->user;
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
			$user_status = $this->getUserStatus();
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			$user = $this->user;
			
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
			$user_status = $this->getUserStatus();
			$user = $this->user;
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
			$upload = \models\Upload::instance();
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
	
	function api_resetPassword($base){
		try {
			if (!$base->exists("POST.email"))
				throw new \Exception("Please enter your email address", 1);
			
			if ($base->exists("SESSION.resetPass_count") && intval($base->get("SESSION.resetPass_count")) > static::RSTPWD_REQ_PER_SESSION)
				throw new \Exception("Please try this operation later", 2);
			
			$user = \models\User::instance();
			$email = $base->get("POST.email");
			if (!$user->isValidEmail($email))
				throw new \Exception("Invalid email address", 3);
			
			$user_info = $user->findByEmail($email);
			if (!$user_info)
				throw new \Exception("Email not registered", 4);			
			
			$first_name = $user_info["first_name"];
			$last_name = $user_info["last_name"];
			
			$ticket_info = array("email" => $email, "old_pass" => $user_info["password"], "time" => date("c"));
			
			$url = $base->get("APP_URL") . "/forgot_pass?t=" . urlencode(base64_encode(static::api_encrypt(json_encode($ticket_info), $base->get("API_WIDE_KEY"))));
			
			$mail = new \models\Mail();
			$mail->addTo($email, $first_name . ' ' . $last_name);
			$mail->setFrom($this->base->get("SMTP_FROM"), "UGL Team");
			$mail->setSubject("Reset Your Password");
			$mail->setMessage("Hello " . $first_name . ' ' . $last_name . ",\n\n" .
								"Thanks for using Ugl. To change your password, please open this link in your browser:\n" .
								"" . $url . "\n\n" .
								
								"If you did not request this email, please disregard this.\n\nThanks for using our service.\n\n" .
								"Best,\nUGL Team");
			$mail->send();
			
			$base->set("SESSION.resetPass_count", intval($base->get("SESSION.resetPass_count")) + 1);
			
			$this->json_printResponse(array("message" => "An email containing the steps to reset password has been sent to your email account."));
			
		} catch (\InvalidArgumentException $e){
			
			throw new \Exception("Email did not send due to server error", 5);
		} catch (\RuntimeException $e){
			
			throw new \Exception("Email did not send due to server runtime error", 6);
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
}