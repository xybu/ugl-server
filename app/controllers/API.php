<?php
namespace controllers;

class API extends \Controller {
	
	function get_SecurityQuestions($f3) {
		$this->json_printResponse($f3->get("securityQuestions"), 24);
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
		
			if (!$f3->exists("POST.email") or !$f3->exists("POST.password") or !$f3->exists("POST.confirm_pass") or !$f3->exists("POST.first_name") or !$f3->exists("POST.last_name"))
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
		}  catch (\Exception $e){
			if (!$no_output) $this->json_printException($e);
		}
	}
	
	function resetPasswordFor($f3){
		
	}
	
	
}