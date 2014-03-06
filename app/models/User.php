<?php

namespace models;

class User extends \Model {
	
	private $token_salt = "ugl>salt.";
	private $token_expiration_time = 168; // one week, in hrs
	
	function findById($id){
		$sql = "SELECT * FROM users WHERE id = '$id' LIMIT 1";
		$result = $this->queryDb($sql);
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	function findByEmail($email){
		$sql = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
		$result = $this->queryDb($sql);
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	function findByEmailAndPassword($email, $password){
		$sql = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
		$result = $this->queryDb($sql);
		if (count($result) == 1){
			if (password_verify($this->getUserToken(0, $password), $result[0]["password"]))
				return array_merge($result[0], array("ugl_token" => $this->getUserToken($result[0]["id"], $result[0]["token_active_at"])));
			else return null;
		}
		return null;
	}
	
	function createUser($email, $password, $first_name, $last_name, $avatar_url = ""){
		if (!$avatar_url or $avatar_url == "")
			$avatar_url = "/assets/img/default_avatar.png";
		$this->queryDb(
			"INSERT INTO users (email, password, first_name, last_name, avatar_url, created_at, token_active_at) " .
			"VALUES (:email, :password, :first_name, :last_name, :avatar_url, NOW(), NOW());",
			array(
				':email' => $email,
				':password' => $this->getUserToken(0, $password),
				':first_name' => $first_name,
				':last_name' => $last_name,
				':avatar_url' => $avatar_url
			)
		);
		
		$result = $this->queryDb(
			"SELECT id, token_active_at FROM users WHERE email=:email LIMIT 1;",
			array(':email' => $email)
		);
		
		if (count($result) == 1){
			return array("id" => $result[0]["id"], "ugl_token" => $this->getUserToken($result[0]["id"], $result[0]["token_active_at"]));
		} else return -1;
	}
	
	function verifyToken($id, $token, $dt_str = null){
		if ($id === "" or $token === "") return false;
		
		if (!$dt_str){
			$result = $this->queryDb(
				"SELECT token_active_at FROM users WHERE id=:id LIMIT 1;",
				array(
					':id' => $id
				)
			);
			$dt_str = $result[0]['token_active_at'];
		}
		
		if (count($result) == 1){
			if (date('c', strtotime("+" . $dt_str . " hour")) < time())
				return false;
			if (password_verify($this->token_salt . $dt_str, $token))
				return true;
			else return false;
		} else return false;
	}
	
	function refreshToken($id){
		if ($id === "" or !is_numeric($id)) return null;
		
		if ($this->query("UPDATE users SET token_active_at=NOW() WHERE id=:id",
			array(':id' => $id))) return $this->getUserToken($id);
		else return null;
	}
	
	function getUserToken($id, $str = null){
		if ($id === "" or !is_numeric($id)) return null;
		
		if (!$str){
			$result = $this->queryDb(
				"SELECT token_active_at FROM users WHERE id=:id LIMIT 1;",
				array(':id' => $id)
			);
			if (count($result) == 1){
				$str = $result[0]['token_active_at'];
			} else return null;
		}
		
		return password_hash($this->token_salt . $str, PASSWORD_DEFAULT);
	}
}