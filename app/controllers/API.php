<?php
namespace controllers;

class API extends \Controller {
	
	const ENABLE_LOG = true;
	const RSTPWD_REQ_PER_SESSION = 3;
	const RSTPWD_REQ_EXPIRATION = 24; // in hrs
	const API_WIDE_KEY = "1POm3YWVlVFriePu2aJfa+K5UElFA0ESeN+4Bb57YnYFyZGDit/Cw1o9rSWZQeFs";
	const API_ANDROID_CLI_KEY = "2IwehG2VEm3WhjLRMK/1aUPqAdW7KNvvRuskedxuOgOQ2jbO+wkKs5p5qJwh98GM";
	
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
	
	function loginUser($f3){
		try {
			if (!$f3->exists("POST.email") or !$f3->exists("POST.password"))
				throw new \Exception("Email or password not provided", 100);
			
			$user = new \models\User();
			$email = $f3->get("POST.email");
			$password = $f3->get("POST.password");
			
			if (!$user->isValidEmail($email))
				throw new \Exception("Invalid email address", 101);
			
			$password = $f3->get("POST.password");
			if (!$user->isValidPassword($password))
				throw new \Exception("Password should not be empty", 103);
			
			$user_data = $user->findByEmailAndPassword($email, $password);
			
			// user found?
			if ($user_data){
				$f3->set("SESSION.user", $user_data);
				$this->json_printResponse(array("user_id" => $user_data["id"], "ugl_token" => $user_data["ugl_token"]));
			} else 
				throw new \Exception("User not found, or email and password do not match.", 102);
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function web_logoutUser($f3){
		if ($f3->exists("SESSION.user")){
			$user_info = $f3->get("SESSION.user");
			$f3->set("POST.user_id", $user_info["id"]);
			$f3->set("POST.ugl_token", $user_info["ugl_token"]);
			$this->revokeToken($f3, true);
			$f3->set("SESSION.user", null);
		}
		$this->json_printResponse(array("message" => "You have successfully logged out"));
	}
	
	function registerUser($f3){
		try {
			
			if (!$f3->exists("POST.agree") or $f3->get("POST.agree") != "true")
				throw new \Exception("You must agree to the terms of services to sign up", 105);
		
			if (!$f3->exists("POST.email") or !$f3->exists("POST.password") or !$f3->exists("POST.confirm_pass") or 
				!$f3->exists("POST.first_name") or !$f3->exists("POST.last_name"))
					throw new \Exception("Email, password, or name not provided", 100);
			
			$user = new \models\User();
			
			$email = $f3->get("POST.email");
			
			if (!$user->isValidEmail($email))
				throw new \Exception("Invalid email address", 101);
			
			$password = $f3->get("POST.password");
			if (!$user->isValidPassword($password))
				throw new \Exception("Password should be at least 6 chars", 106);
			
			$confirm_password = $f3->get("POST.confirm_pass");
			if ($password != $confirm_password)
				throw new \Exception("Password and confirm password do not match", 102);
			
			$first_name = $f3->get("POST.first_name");
			$last_name = $f3->get("POST.last_name");
			
			if (!$user->isValidName($first_name) or !$user->isValidName($last_name))
				throw new \Exception("First name or last name should be non-empty words", 103);
			
			$user_info = $user->findByEmail($email);
			if ($user_info)
				throw new \Exception("Email already registered", 104);
			
			$new_user_creds = $user->createUser($email, $password, $first_name, $last_name);
			
			$f3->set("SESSION.user", $new_user_creds);
			
			$this->json_printResponse(array("user_id" => $new_user_creds["id"], "ugl_token" => $new_user_creds["ugl_token"]));
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function revokeToken($f3, $no_output = false){
		try {
			if (!$f3->exists("POST.user_id") or !$f3->exists("POST.ugl_token"))
					throw new \Exception("Authentication fields missing", 1);
			
			$user_id = $f3->get("POST.user_id");
			$ugl_token = $f3->get("POST.ugl_token");
			
			if (!is_numeric($user_id))
				throw new \Exception("User id should be a number", 2);
		
			$user = new \models\User();
			$user->verifyToken($user_id, $ugl_token, "1970-1-1T00:00:00+0000");
			if (!$no_output) $this->json_printResponse(array("message" => "Token has been revoked."));
		} catch (\Exception $e){
			if (!$no_output) $this->json_printException($e);
		}
	}
	
	function resetPassword($f3){
		try {
			if (!$f3->exists("POST.email"))
				throw new \Exception("Please enter your email address", 1);
			
			if ($f3->exists("SESSION.resetPass_count") && intval($f3->get("SESSION.resetPass_count")) > static::RSTPWD_REQ_PER_SESSION)
				throw new \Exception("Please try this operation later", 2);
			
			$user = new \models\User();
			$email = $f3->get("POST.email");
			if (!$user->isValidEmail($email))
				throw new \Exception("Invalid email address", 3);
			
			$user_info = $user->findByEmail($email);
			if (!$user_info)
				throw new \Exception("Email not registered", 4);			
			
			$first_name = $user_info["first_name"];
			$last_name = $user_info["last_name"];
			
			$ticket_info = array("email" => $email, "old_pass" => $user_info["password"], "time" => date("c"));
			
			$url = $f3->get("WEB_APP_URL") . "/forgot_pass?t=" . urlencode(base64_encode(static::api_encrypt(json_encode($ticket_info), static::API_WIDE_KEY)));
			
			$mail = new \models\Mail();
			$mail->addTo($email, $first_name . ' ' . $last_name);
			$mail->setFrom($this->f3->get("EMAIL_SENDER_ADDR"), "UGL Team");
			$mail->setSubject("Reset Your Password");
			$mail->setMessage("Hello " . $first_name . ' ' . $last_name . ",\n\n" .
								"Thanks for using Ugl. To change your password, please open this link in your browser:\n" .
								"" . $url . "\n\n" .
								
								"If you did not request this email, please disregard this.\n\nThanks for using our service.\n\n" .
								"Best,\nUGL Team");
			$mail->send();
			
			$f3->set("SESSION.resetPass_count", intval($f3->get("SESSION.resetPass_count")) + 1);
			
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
	
	
}