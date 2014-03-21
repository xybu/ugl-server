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
	const LOGIN_REQ_PER_SESSMION = 5;
	const RSTPWD_REQ_EXPIRATION = 24; // in hrs
	const API_WIDE_KEY = "1POm3YWVlVFriePu2aJfa+K5UElFA0ESeN+4Bb57YnYFyZGDit/Cw1o9rSWZQeFs";
	
	public static $API_KEYS = array(
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
			if ($base->exists("SESSION.loginFail_count") && intval($base->get("SESSION.loginFail_count")) > static::LOGIN_REQ_PER_SESSMION)
				throw new \Exception("Your account is temporarily on held for security concern. Please retry later or log in with your social account", 104);
			
			if (!$base->exists("POST.email") or !$base->exists("POST.password"))
				throw new \Exception("Email or password not provided", 100);
			
			$user = new \models\User();
			$email = $base->get("POST.email");
			$password = $base->get("POST.password");
			
			if (!$user->isValidEmail($email))
				throw new \Exception("Invalid email address", 101);
			
			if (!$user->isValidPassword($password))
				throw new \Exception("Password should not be empty", 103);
			
			$user_data = $user->findByEmailAndPassword($email, $password);
			
			// user found?
			if ($user_data){
				$user_creds = array("user_id" => $user_data["id"], "ugl_token" => $user_data["ugl_token"]);
				$base->set("SESSION.user", $user_creds);
				if ($base->exists("SESSION.loginFail_count")) $base->clear("SESSION.loginFail_count");
				$this->json_printResponse($user_creds);
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
		if ($base->exists("SESSION.user")){
			$this->revokeToken($base, true);
			$base->clear("SESSION.user");
		}
		$this->json_printResponse(array("message" => "You have successfully logged out"));
	}
	
	function revokeToken($base, $no_output = false){
		try {
			$user = new \models\User();
			$user_status = self::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			
			$user->token_refresh(array("id" => $user_id));
			
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
			
			$url = $base->get("APP_URL") . "/forgot_pass?t=" . urlencode(base64_encode(static::api_encrypt(json_encode($ticket_info), static::API_WIDE_KEY)));
			
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
	static function getUserStatus($base, $user){
		$user_id = -1;
		$token = "";
		
		if ($base->exists("POST.user_id") and $base->exists("POST.ugl_token")){
			// app client POST API
			$user_id = $base->get("POST.user_id");
			$token = $base->get("POST.ugl_token");
		} else if ($base->exists("SESSION.user")){
			// web client session
			$session_user = $base->get("SESSION.user");
			$user_id = $session_user["user_id"];
			$token = $session_user["ugl_token"];
		} else throw new \Exception("You should log in to perform the request", 1);
		
		// can handle nonexistent user id
		$user_info = $user->findById($user_id);
		if (empty($user_info) or !$user->token_verify($user_info, $token))
			throw new \Exception("Unauthorized request", 2);
		
		return array("user_id" => $user_id, "user_info" => $user_info, "ugl_token" => $token);
	}
}