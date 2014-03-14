<?php
/**
 * The API controller for project Ugl.
 *
 * @author	Xiangyu Bu <xybu92@live.com>
 * @version	0.3
 */

namespace controllers;

class API extends \Controller {
	
	const ENABLE_LOG = true;
	const RSTPWD_REQ_PER_SESSION = 3;
	const RSTPWD_REQ_EXPIRATION = 24; // in hrs
	const API_WIDE_KEY = "1POm3YWVlVFriePu2aJfa+K5UElFA0ESeN+4Bb57YnYFyZGDit/Cw1o9rSWZQeFs";
	
	private static $API_KEYS = array(
		"ugl_android" => "7wR+GgG/r2Mm7hkymXXeMGuXU9ojN2HV5AlIuoJqg+TZ41DlwCIQpf93A3MJs2hI",
		"ugl_web" => "2IwehG2VEm3WhjLRMK/1aUPqAdW7KNvvRuskedxuOgOQ2jbO+wkKs5p5qJwh98GM"
	);
	
	function __construct(){	
		parent::__construct();
		if (static::ENABLE_LOG)
			$this->logger = new \Log("controller.api.log");
	}
	
	static function api_encrypt($str, $key){
		return openssl_encrypt($str, "AES-256-ECB", $key);
	}
	
	static function api_decrypt($str, $key){
		$trial = openssl_decrypt($str, "AES-256-ECB", $key);
		if (!$trial) return null;
		return $trial;
	}
	
	function loginUser($base){
		try {
			if (!$base->exists("POST.email") or !$base->exists("POST.password"))
				throw new \Exception("Email or password not provided", 100);
			
			$user = new \models\User();
			$email = $base->get("POST.email");
			$password = $base->get("POST.password");
			
			if (!$user->isValidEmail($email))
				throw new \Exception("Invalid email address", 101);
			
			$password = $base->get("POST.password");
			if (!$user->isValidPassword($password))
				throw new \Exception("Password should not be empty", 103);
			
			$user_data = $user->findByEmailAndPassword($email, $password);
			
			// user found?
			if ($user_data){
				$base->set("SESSION.user", $user_data);
				$this->json_printResponse(array("user_id" => $user_data["id"], "ugl_token" => $user_data["ugl_token"]));
			} else 
				throw new \Exception("User not found, or email and password do not match.", 102);
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function web_logoutUser($base){
		if ($base->exists("SESSION.user")){
			$user_info = $base->get("SESSION.user");
			$base->set("POST.user_id", $user_info["id"]);
			$base->set("POST.ugl_token", $user_info["ugl_token"]);
			$this->revokeToken($base, true);
			$base->set("SESSION.user", null);
		}
		$this->json_printResponse(array("message" => "You have successfully logged out"));
	}
	
	function registerUser($base){
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
			
			$first_name = $base->get("POST.first_name");
			$last_name = $base->get("POST.last_name");
			
			if (!$user->isValidName($first_name) or !$user->isValidName($last_name))
				throw new \Exception("First name or last name should be non-empty words", 103);
			
			$user_info = $user->findByEmail($email);
			if ($user_info)
				throw new \Exception("Email already registered", 104);
			
			$new_user_creds = $user->createUser($email, $password, $first_name, $last_name);
			
			$base->set("SESSION.user", $new_user_creds);
			
			$this->json_printResponse(array("user_id" => $new_user_creds["id"], "ugl_token" => $new_user_creds["ugl_token"]));
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function revokeToken($base, $no_output = false){
		try {
			if (!$base->exists("POST.user_id") or !$base->exists("POST.ugl_token"))
					throw new \Exception("Authentication fields missing", 1);
			
			$user_id = $base->get("POST.user_id");
			$ugl_token = $base->get("POST.ugl_token");
			
			if (!is_numeric($user_id))
				throw new \Exception("User id should be a number", 2);
		
			$user = new \models\User();
			if ($user->verifyToken($user_id, $ugl_token))
				$user->refreshToken($user_id);
			
			if (!$no_output) $this->json_printResponse(array("message" => "Token has been revoked."));
		} catch (\Exception $e){
			if (!$no_output) $this->json_printException($e);
		}
	}
	
	function resetPassword($base){
		try {
			if (!$base->exists("POST.email"))
				throw new \Exception("Please enter your email address", 1);
			
			if ($base->exists("SESSION.resetPass_count") && intval($base->get("SESSION.resetPass_count")) > static::RSTPWD_REQ_PER_SESSION)
				throw new \Exception("Please try this operation later", 2);
			
			$user = new \models\User();
			$email = $base->get("POST.email");
			if (!$user->isValidEmail($email))
				throw new \Exception("Invalid email address", 3);
			
			$user_info = $user->findByEmail($email);
			if (!$user_info)
				throw new \Exception("Email not registered", 4);			
			
			$first_name = $user_info["first_name"];
			$last_name = $user_info["last_name"];
			
			$ticket_info = array("email" => $email, "old_pass" => $user_info["password"], "time" => date("c"));
			
			$url = $base->get("WEB_APP_URL") . "/forgot_pass?t=" . urlencode(base64_encode(static::api_encrypt(json_encode($ticket_info), static::API_WIDE_KEY)));
			
			$mail = new \models\Mail();
			$mail->addTo($email, $first_name . ' ' . $last_name);
			$mail->setFrom($this->base->get("EMAIL_SENDER_ADDR"), "UGL Team");
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
			if (static::ENABLE_LOG)
				$this->logger->write($e->__toString());
			throw new \Exception("Email did not send due to server error", 5);
		} catch (\RuntimeException $e){
			if (static::ENABLE_LOG)
				$this->logger->write($e->__toString());
			throw new \Exception("Email did not send due to server runtime error", 6);
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	/**
	 * getUserStatus
	 * verify the login status of the requester
	 * generalized to accommodate web client and POST-based apps
	 *
	 * @param $base	Base instance
	 * @param $user	User model
	 */
	private function getUserStatus($base, $user){
		$user_id = 0;
		$token = "";
		
		if ($base->exists("POST.user_id") and $base->exists("POST.ugl_token")){
			// app client POST API
			$user_id = $base->get("POST.user_id");
			$token = $base->get("POST.ugl_token");
		} else if ($base->exists("SESSION.user")){
			// web client session
			$session_user = $base->get("SESSION.user");
			$user_id = $session_user["id"];
			$token = $session_user["ugl_token"];
		} else throw new \Exception("You should log in to perform the request", 1);
		
		// can handle nonexistent user id
		if (!$user->verifyToken($user_id, $token))
			throw new \Exception("Unauthorized request", 2);
		
		return array("user_id" => $user_id, "ugl_token" => $token);
	}
	
	function getMyPrefs($base){
		try {
			$user = new \models\User();
			$user_status = $this->getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$user_pref = $user->getUserPref($user_id);
			$this->json_printResponse($user_pref);
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function setMyPrefs($base){
		try {
			$user = new \models\User();
			$user_status = $this->getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			
			$pref_obj = new \models\Preference();
			$pref_array = $user->getDefaultPrefArray();
			
			foreach ($pref_array as $key => $val){
				if ($base->exists("POST." . $key)){
					$post_val = $base->get("POST." . $key);
					if (gettype($post_val) == gettype($val))
						$val = $post_val;
				}
				$pref_obj->setField($key,$post_val);
			}
			
			$user->setUserPref($user_id, $pref_obj);
			
			$this->json_printResponse(array_merge(array("message" => "You have successfully updated your preferences."), $pref_obj->toArray()));
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	/*
	function setMyPrefs($base){
		try {
			$user = new \models\User();
			$user_status = $this->getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			
			if (!$base->exists("POST.data") or !$base->exists("POST.from"))
				throw new \Exception("Wrong data format", 3);
			
			$post_source = $base->get("POST.from");
			if (!array_key_exists($post_source, self::$API_KEYS))
				throw new \Exception("Unrecognized data source", 4);
			
			$post_data = self::api_decrypt(base64_decode(urldecode($base->get("POST.data"))), self::$API_KEYS[$post_source]);
			if (empty($post_data))
				throw new \Exception("Failed to decrypt the data", 5);
			
			$json_data = json_decode($post_data, true);
			if (empty($json_data))
				throw new \Exception("Cannot parse the data as json", 6);
			
			$pref_array = $user->getDefaultPrefArray();
			foreach ($pref_array as $key => $val){
				if (array_key_exists($key, $json_data)){
					
				}
			}
			
			$this->json_printResponse($user_pref);
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	*/
	function listGroupsOf($base){
		try {
			
			$user = new \models\User();
			$user_status = $this->getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$token = $user_status["ugl_token"];
			$target_user_id = $user_id;
			$target_visibility = 0;
			
			if ($base->exists("PARAMS.user_id")){
				$target_user_id = $base->get("PARAMS.user_id");
				if (!is_numeric($target_user_id))
					throw new \Exception("User id should be a number", 3);
				
				if ($target_user_id != $user_id){
					$target_user = $user->findById($target_user_id);
					if (!$target_user)
						throw new \Exception("The user does not exist", 4);
					
					//if (USER does not allow to request his list){
					//	throw new \Exception("The user did not allow you to view his or her group list.", 5);
					//}
					
					$target_visibility = 1;
				}
			}
			
			$group = new \models\Group();
			$group_list = $group->listGroupsOfUserId($target_user_id, $target_visibility);
			
			$this->json_printResponse($group_list);
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
}