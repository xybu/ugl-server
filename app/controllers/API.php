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
			
			$user_data = $user->findByEmailAndPassword($email, $password);
			
			// user found?
			if ($user_data){
				$f3->set("SESSION.user", $user_data["id"]);
				$this->json_printResponse(array("user_id" => $user_data["id"]));
			} else 
				throw new \Exception("User not found, or email and password do not match.", 102);
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function logoutUser($f3){
		$f3->set("SESSION.user", null);
		$this->json_printResponse(array("message" => "You have successfully logged out"));
	}
	
	function registerUser($f3){
		try {
			if (!$f3->exists("POST.email") or !$f3->exists("POST.password") or !$f3->exists("POST.confirm_pass") or !$f3->exists("POST.first_name") or !$f3->exists("POST.last_name"))
				throw new \Exception("Email, password, or name not provided", 100);
			
			$email = $f3->get("POST.email");
			
			if (!$user->isValidEmail($email))
				throw new \Exception("Invalid email address", 101);
			
			$password = $f3->get("POST.password");
			$confirm_password = $f3->get("POST.confirm_pass");
			
			if ($password != $confirm_password)
				throw new \Exception("Password and confirm password do not match", 102);
			
			$first_name = $f3->get("POST.first_name");
			$last_name = $f3->get("POST.last_name");
			
			if ($firstname === "" or $lastname === "")
				throw new \Exception("First name or last name is empty", 103);
			
			$user = new \models\User();
			
			$user_info = $user->findByEmail($email);
			if ($user_info)
				throw new \Exception("Email already registered", 104);
			
			$new_user_id = $user->createUser($email, $password, $first_name, $last_name);
			
			$f3->set("SESSION.user", $new_user_id);
			$this->json_printResponse(array("user_id" => $user_data["id"]));
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
}