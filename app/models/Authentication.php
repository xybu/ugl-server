<?php

namespace models;

class Authentication extends \Model {
	
	//TODO: need to defend against sql injections
	function findByProviderUid($provider, $provider_uid){
		$sql = "SELECT * FROM authentications WHERE provider = '$provider' AND provider_uid = '$provider_uid' LIMIT 1";
		$result = $this->queryDb($sql);
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	function findByUserId($user_id){
		$sql = "SELECT * FROM authentications WHERE user_id = '$user_id' LIMIT 1";
		$result = $this->queryDb($sql);
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	function createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $avatar_url, $website_url){ 
		$sql = "INSERT INTO authentications (user_id, provider, provider_uid, email, display_name, first_name, last_name, avatar_url, website_url, created_at) VALUES ('$user_id', '$provider', '$provider_uid', '$email', '$display_name', '$first_name', '$last_name', '$avatar_url', '$website_url', NOW()) ";
		$result = $this->queryDb(
			"SELECT id FROM authentications WHERE user_id=:user_id AND provider_uid=:provider_uid LIMIT 1, 1;",
			array(
				':user_id' => $user_id,
				':provider_uid' => $provider_uid
			)
		);
		
		if (count($result) == 1){
			return $result[0]['id'];
		} else return -1;
	}
	
}