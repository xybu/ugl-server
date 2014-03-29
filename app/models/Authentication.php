<?php

namespace models;

class Authentication extends \Model {
	
	function findByProviderUid($provider, $provider_uid){
		$result = self::queryDb("SELECT * FROM authentications WHERE provider = :provider AND provider_uid = :provider_uid LIMIT 1", 
							array(":provider" => $provider, ":provider_uid" => $provider_uid));
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	function findByUserId($user_id){
		$result = self::queryDb("SELECT * FROM authentications WHERE user_id = ? LIMIT 1", $user_id);
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	function createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url){ 
		
		$result = self::queryDb(
			"INSERT INTO authentications (user_id, provider, provider_uid, email, display_name, first_name, last_name, avatar_url, website_url, created_at) VALUES (:user_id, :provider, :provider_uid, :email, :display_name, :first_name, :last_name, :avatar_url, :website_url, NOW()); " .
			"SELECT id FROM authentications WHERE user_id=:user_id AND provider_uid=:provider_uid LIMIT 1;", 
			array(
				':user_id' => $user_id, 
				':provider' => $provider, 
				':provider_uid' => $provider_uid,
				':email' => $email, 
				':display_name' => $display_name, 
				':first_name' => $first_name, 
				':last_name' => $last_name, 
				':avatar_url' => $avatar_url, 
				':website_url' => $website_url
			)
		);
				
		if (count($result) == 1){
			return $result[0]['id'];
		} else return -1;
	}
	
}