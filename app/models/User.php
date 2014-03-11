<?php

namespace models;

class User extends \Model {

	const ENABLE_LOG = true;
	const TOKEN_SALT = "ugl>salt.";
	const TOKEN_VALID_HRS = 168; // one week, in hrs
	
	function __construct(){
		parent::__construct();
		if (static::ENABLE_LOG)
			$this->logger = new \Log("model.user.log");
	}
	
	function findById($id){
		$result = $this->queryDb("SELECT * FROM users WHERE id=? LIMIT 1;", $id, 1800);
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	function findByEmail($email){
		$result = $this->queryDb("SELECT * FROM users WHERE email=? LIMIT 1;", $email);
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	function findByEmailAndPassword($email, $password){
		$result = $this->queryDb("SELECT * FROM users WHERE email=? LIMIT 1;", $email);
		if (count($result) == 1){
			if (password_verify(static::TOKEN_SALT . $password, $result[0]["password"]))
				return array_merge($result[0], array("ugl_token" => $this->getUserToken($result[0]["id"], $result[0]["token_active_at"])));
			else return null;
		}
		return null;
	}
	
	function createUser($email, $password, $first_name, $last_name, $avatar_url = ""){
		$send_email = false;
		
		if (!$avatar_url or $avatar_url == "")
			$avatar_url = "/assets/img/default_avatar.png";
		
		// send email with random password if $password not set
		if ($password === ""){
			$original_password = $this->getRandomStr(12);
			$password = md5(base64_encode(sha1(sha1($original_password))));
			$send_email = true;
		}
		
		$result = $this->queryDb(
			array(
				"INSERT INTO users (email, password, first_name, last_name, avatar_url, created_at, token_active_at) " .
				"VALUES (:email, :password, :first_name, :last_name, :avatar_url, NOW(), NOW());",
				"SELECT id, token_active_at FROM users WHERE email=:email LIMIT 1;"
			),
			array(
				':email' => $email,
				':password' => $this->getUserToken(0, $password),
				':first_name' => $first_name,
				':last_name' => $last_name,
				':avatar_url' => $avatar_url
			)
		);
		
		if ($send_email){
			try {
				$mail = new Mail();
				$mail->addTo($email, $first_name . ' ' . $last_name);
				$mail->setFrom($this->f3->get("EMAIL_SENDER_ADDR"), "UGL Team");
				$mail->setSubject("Thanks for Using Ugl!");
				$mail->setMessage("Hello " . $first_name . ' ' . $last_name . ",\n\n" .
									"Thanks for using Ugl. At the first time you sign in with your " .
									"social account, we assigned you a randomly generated password \"" . $original_password . "\"" .
									" (without quotes) so that you can use Ugl independently. Please save the password, or change it to " . 
									"your own one at Ugl control panel.\n\n" .
									"Again, thanks for using our service.\n\n" .
									"Best,\nUGL Team");
				$mail->send();
			// log exceptions but do not behave
			} catch (\InvalidArgumentException $e){
				if (static::ENABLE_LOG)
					$this->logger->write($e->__toString());
			} catch (\RuntimeException $e){
				if (static::ENABLE_LOG)
					$this->logger->write($e->__toString());
			}
		}
		
		// can still work if failed to send email
		if (count($result) == 1){
			return array("id" => $result[0]["id"], "ugl_token" => $this->getUserToken($result[0]["id"], $result[0]["token_active_at"]));
		} else return -1;
	}
	
	function getUserProfile($id, $email = ""){
		if ($email != "" and $this->isValidEmail($email)) $email = " email='" . $email . "'";
		else $email = "";
		
		if (is_numeric($id)) $id = " id = " . $id . "";
		else $id = "";
		
		if ($id == "" and $email == "") return null;
		if ($id != "" and $email != "") $email = " AND" . $email;
		
		$result = $this->queryDb("SELECT id, email, first_name, last_name, avatar_url, created_at FROM users WHERE" . $id . $email . " LIMIT 1;", null, 3600);
		
		if (count($result) == 1){
			return $result[0];
		}
		
		return null;
	}
	
	function getUserPref($id){
		if (!is_numeric($id)) return null;
		
		$result = $this->queryDb("SELECT * FROM users WHERE id=? LIMIT 1;", $id, 1800);
		
		if (count($result) == 1)
			return new Preference($result[0]["preferences"], true);
		return null;
	}
	
	function setUserPref($id, Preference $pref){
		if (!is_numeric($id) or $pref == null) return;
		
		$this->queryDb("UPDATE users SET preferences=:pref WHERE id=:id LIMIT 1;",
						array(":id" => $id, "pref" => $pref->toJson())
		);
	}
	
	/**
	 * verifyToken
	 * @param id: the user id
	 * @param token: the client token
	 * @param dt_str: the original date; will fetch from database if set null
	 * 
	 * Usage: 
	 * verify if the token is valid for the user id
	 * reset the token if dt_str is expired
	 */
	function verifyToken($id, $token, $dt_str = null){
		if ($id === "" or $token === "") return false;
		
		if (!$dt_str){
			$result = $this->queryDb("SELECT token_active_at FROM users WHERE id=? LIMIT 1;", $id);
			if (count($result) != 1) return false;
			$dt_str = $result[0]['token_active_at'];
		}
		
		if (count($result) == 1){
			if (password_verify(static::TOKEN_SALT . $dt_str, $token)){
				if (strtotime("+" . static::TOKEN_VALID_HRS . " hour", strtotime($dt_str)) < time()){
					$new_token = $this->refreshToken($id);
					return false;
				} else return true;
			}
		}
		
		return false;
	}
	
	function refreshToken($id){
		if ($id === "" or !is_numeric($id)) return null;
		
		if ($this->queryDb("UPDATE users SET token_active_at=NOW() WHERE id=:id",
			array(':id' => $id))) return $this->getUserToken($id);
		return null;
	}
	
	function getUserToken($id, $str = null){
		if ($id === "" or !is_numeric($id)) return null;
		
		if (!$str){
			$result = $this->queryDb("SELECT token_active_at FROM users WHERE id=? LIMIT 1;", $id);
			if (count($result) == 1){
				$str = $result[0]['token_active_at'];
			} else return null;
		}
		
		return password_hash(static::TOKEN_SALT . $str, PASSWORD_DEFAULT);
	}
}