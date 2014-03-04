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
		$sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password' LIMIT 1";
		$result = $this->queryDb($sql);
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	function createUser($email, $password, $first_name, $last_name){
		$this->queryDb(
			"INSERT INTO users (email, password, first_name, last_name, created_at) " .
			"VALUES (:email, :password, :first_name, :last_name, NOW());",
			array(
				':email' => $email,
				':password' => $password,
				':first_name' => $first_name,
				':last_name' => $last_name
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