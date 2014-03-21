<?php

namespace models;

class User extends \Model {

	const ENABLE_LOG = true;
	const TOKEN_SALT = "ugl>salt.";
	const TOKEN_VALID_HRS = 168; // one week, in hrs
	const USER_CACHE_TTL = 3600;
	const MAX_DESC_LENGTH = 150;
	
	public static $DEFAULT_USER_PREFERENCES = array(
		"autoAcceptInvitation" => false,
		"showMyProfile" => true,
		"showMyPublicGroups" => true
	);
	
	function __construct(){
		parent::__construct();
		if (static::ENABLE_LOG)
			$this->logger = new \Log("model.user.log");
	}
	
	static function filterDescription($str){
		$str = htmlspecialchars($str);
		$str = substr($str, 0, static::MAX_DESC_LENGTH);
		return $str;
	}
	
	function findById($id){
		if ($this->cache->exists("user_id_" . $id))
			return $this->cache->get("user_id_" . $id);
		
		$result = $this->queryDb("SELECT * FROM users WHERE id=? LIMIT 1;", $id);
		if (count($result) == 1){
			if (!empty($result[0]["_preferences"]))
				$result[0]["_preferences"] = json_decode($result[0]["_preferences"], true);
			else $result[0]["_preferences"] = self::$DEFAULT_USER_PREFERENCES;
			$this->cache->set("user_id_" . $id, $result[0], static::USER_CACHE_TTL);
			return $result[0];
		}
		return null;
	}
	
	function findByEmail($email){
		$result = $this->queryDb("SELECT id FROM users WHERE email=? LIMIT 1;", $email);
		if (count($result) == 1) return $this->findById($result[0]["id"]);
		return null;
	}
	
	function findByEmailAndPassword($email, $password){
		$result = $this->findByEmail($email);
		if (!empty($result) and password_verify(static::TOKEN_SALT . $password, $result["_password"])) {
			$this->token_refresh($result);
			$result["ugl_token"] = $this->token_get($result);
			return $result;
		}
		return null;
	}
	
	function create($email, $password, $first_name, $last_name, $avatar_url = ""){
		$send_email = false;
		
		if (!$avatar_url) $avatar_url = "";
		
		// send email with random password if $password not set
		if (empty($password)) {
			$original_password = $this->getRandomStr(12);
			$password = md5(base64_encode(sha1(sha1($original_password))));
			$send_email = true;
		}
		
		$this->queryDb(
			"INSERT INTO users (email, _password, first_name, last_name, avatar_url, created_at, _token_active_at) " .
			"VALUES (:email, :password, :first_name, :last_name, :avatar_url, NOW(), NOW()); ",
			array(
				':email' => $email,
				':password' => $this->token_get(null, $password),
				':first_name' => substr($first_name, 0, 100),
				':last_name' => substr($last_name, 0, 100),
				':avatar_url' => substr($avatar_url, 0, 300)
			)
		);
		
		if ($send_email){
			try {
				$mail = new Mail();
				$mail->addTo($email, $first_name . ' ' . $last_name);
				$mail->setFrom($this->base->get("SMTP_FROM"), "UGL Team");
				$mail->setSubject("Thanks for Using Ugl!");
				$mail->setMessage("Hello " . $first_name . ' ' . $last_name . ",\n\n" .
									"Thanks for using Ugl. At the first time you sign in with your " .
									"social account, we assigned you a randomly generated password \"" . $original_password . "\"" .
									" (without quotes) so that you can use Ugl independently. Please save the password, or change it to " . 
									"your own one at Ugl control panel.\n\n" .
									"Again, thanks for using our service.\n\n" .
									"Best,\nUGL Team");
				$mail->send();
			} catch (\InvalidArgumentException $e) {
				if (static::ENABLE_LOG)
					$this->logger->write($e->__toString());
			} catch (\RuntimeException $e) {
				if (static::ENABLE_LOG)
					$this->logger->write($e->__toString());
			}
		}
		
		$result = $this->findByEmail($email);
		
		// can still work if failed to send email
		if (!empty($result)) {
			$result["ugl_token"] = $this->token_get($result);
			return $result;
		}
		
		return null;
	}
	
	function update(&$user_info, &$fields){
		foreach ($fields as $key => $value){
			if ($key == "password"){
				if ($value == ""){
					$value = $this->getRandomStr(12);
					$fields["password"] = $value;
				}
				$password = md5(base64_encode(sha1(sha1($value))));
				$password = $this->getUserToken(0, $password);
				$user_info["password"] = $password;
			} else {
				$user_info[$key] = $value;
			}
		}
	}
	
	function save(&$user_info) {
		$pref = $user_info["_preferences"];
		if ($pref == self::$DEFAULT_USER_PREFERENCES) $pref = null;
		
		$this->queryDb(
			"UPDATE users SET email=:email, _password=:password, nickname=:nickname, first_name=:first_name, last_name=:last_name, avatar_url=:avatar_url, phone=:phone, description=:description, _token_active_at=:_token_active_at, _preferences=:_preferences WHERE id=:id LIMIT 1;",
			array(
				":id" => $user_info["id"],
				":email" => $user_info["email"],
				":password" => $user_info["_password"],
				":nickname" => substr($user_info["nickname"], 0, 100),
				":first_name" => substr($user_info["first_name"], 0, 100),
				":last_name" => substr($user_info["last_name"], 0, 100),
				":avatar_url" => substr($user_info["avatar_url"], 0, 100),
				":phone" => substr($user_info["phone"], 0, 36),
				":description" => $user_info["description"],
				":_token_active_at" => $user_info["_token_active_at"],
				":_preferences" => $pref != null ? json_encode($pref) : null
			)
		);
		
		if ($this->cache->exists("user_id_" . $user_info["id"]))
			$this->cache->set("user_id_" . $user_info["id"], $user_info);
	}
	
	function token_verify(&$user_info, $token) {
		if (empty($user_info) or $token === "") return false;
		
		$dt_str = $user_info["_token_active_at"];
		
		if (strtotime("+" . static::TOKEN_VALID_HRS . " hour", strtotime($dt_str)) < time()) {
			$this->token_refresh($user_info);
			return false;
		}
		
		if (password_verify(static::TOKEN_SALT . $dt_str, $token))
			return true;
		
		return false;
	}
	
	function token_get(&$user_info, $str = null) {
		if (empty($str)) $str = $user_info["_token_active_at"];
		
		return password_hash(static::TOKEN_SALT . $str, PASSWORD_DEFAULT);
	}
	
	function token_refresh(&$user_info) {
		$token_base = date("Y-m-d H:i:s");
		$user_info["_token_active_at"] = $token_base;
		$this->queryDb("UPDATE users SET _token_active_at=:token_base WHERE id=:id", array(
			":id" => $user_info["id"], 
			":token_base" => $token_base
		));
		
		if ($this->cache->exists("user_id_" . $user_info["id"]))
			$this->cache->set("user_id_" . $user_info["id"], $user_info);
		
		return $this->token_get($user_info, $token_base);
	}
}