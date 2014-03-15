<?php
/**
 * Object model for the OAuth callback data sent from native apps.
 *
 * @author	Xiangyu Bu <xybu92@live.com>
 * @version	0.1
 */

namespace models;

class OAuthObject {
	
	private $data = array(
		"auth" => array(
			"provider" => "@required",
			"uid" => "@required",
			"info" => array(
				"name" => "@required",
				"email" => "@required",
				"nickname" => "",
				"first_name" => "",
				"last_name" => "",
				"location" => "",
				"description" => "",
				"image" => "",
				"phone" => "",
				"urls" => array("website" => "")
			),
			"credentials" => array(
				"token" => "",
				"secret" => "",
			),
			"raw" => "",
		),
		"timestamp" => "@required",
		"signature" => "@required"
	);
	
	public function __construct(){
	}
	
	public function __destruct(){
	}
	
	public function loadJSON($json){
		$jdata = json_decode($json, true);
		
		// check if valid JSON
		if (json_last_error())
			throw new \InvalidArgumentException("Cannot parse the text to JSON object");
		
		// check basic structure
		if (!array_key_exists("timestamp", $jdata) or !array_key_exists("signature", $jdata))
			throw new \InvalidArgumentException("At least one of security tags is missing");
		$this->data["signature"] = $jdata["signature"];
		
		$timestamp = strtotime($jdata["timestamp"]);
		if (!$timestamp or $timestamp == -1)
			throw new \InvalidArgumentException("Invalid timestamp format");
		$this->data["timestamp"] = $timestamp;
		
		if (!array_key_exists("auth", $jdata))
			throw new \InvalidArgumentException("The authentication information is missing");
		
		foreach ($this->data["auth"] as $key => $val){
			if (is_array($val)){
				if (!array_key_exists($key, $jdata["auth"]))
					throw new \InvalidArgumentException("Field auth_" . $key . " does not exist");
				
				$this->data["auth"][$key] = array_merge($this->data["auth"][$key], $jdata["auth"][$key]);
			} else {
				if ($val == "@required"){
					if (!array_key_exists($key, $jdata["auth"]))
						throw new \InvalidArgumentException("Required auth field \"" . $key . "\" does not exist");
					$val = $jdata["auth"][$key];
				} else if (array_key_exists($key, $jdata["auth"]))
					$val = $jdata["auth"][$key];
				
				$this->data["auth"][$key] = $val;
			}
		}
		
		if ($this->data["auth"]["info"]["name"] == "@required")
			throw new \InvalidArgumentException("Name of the user not provided");
		
		if ($this->data["auth"]["info"]["email"] == "@required")
			throw new \InvalidArgumentException("Email of the user not provided");
	}
	
	public function toJSON(){
		return json_encode($this->data);
	}
	
	public function toArray(){
		return $this->data;
	}
}