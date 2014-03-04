<?php

namespace models;

class User extends \Model {
	
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
			if ($result[0]["password"] == $password)
				return $result[0];
			else return null;
		}
		return null;
	}
	
	function createUser($email, $password, $first_name, $last_name, $avatar_url = ""){
		if (!$avatar_url or $avatar_url == "")
			$avatar_url = "/assets/img/default_avatar.png";
		$this->queryDb(
			"INSERT INTO users (email, password, first_name, last_name, avatar_url, created_at) " .
			"VALUES (:email, :password, :first_name, :last_name, :avatar_url, NOW());",
			array(
				':email' => $email,
				':password' => $password,
				':first_name' => $first_name,
				':last_name' => $last_name,
				':avatar_url' => $avatar_url
			)
		);
		
		$result = $this->queryDb(
			"SELECT id FROM users WHERE email=:email LIMIT 1, 1;",
			array(
				':email' => $email
			)
		);
		
		if (count($result) == 1){
			return $result[0]['id'];
		} else return -1;
	}
	
	
}