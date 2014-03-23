<?php
/**
 * Model.php
 *
 * The base model class
 * @author	Xiangyu Bu
 * @date	Mar 16, 2014
 */

class Model {
	protected $base;
	protected $cache;
	protected $db = null;
	protected $logger = null;
	
	function __construct(){
		$this->base=Base::instance();
		$this->cache = \Cache::instance();
		$this->db = new DB\SQL("mysql:host=" . $this->base->get("DB_HOST") . ";port=" . $this->base->get("DB_PORT") . ";dbname=" . $this->base->get("DB_NAME") . "", $this->base->get("DB_USER"), $this->base->get("DB_PASS"));
	}
	
	function queryDb($cmds, $args=NULL, $ttl=0, $log=TRUE) {
		return $this->db->exec($cmds, $args, $ttl, $log);
	}
	
	// some basic validations
	function isValidEmail($str){
		return filter_var($str, FILTER_VALIDATE_EMAIL);
	}
	
	function isValidPassword($str){
		// "" after hash
		return $str && strlen($str) == 32 && $str != "c206cc8346228864f9176044b4792c6a";
	}
	
	function filterHtmlChars($str){
		$str = str_replace("\t", "", $str);
		$str = str_replace("\n", "", $str);
		$str = str_replace("&", "", $str);
		$str = str_replace("\"", "", $str);
		$str = str_replace("'", "", $str);
		return $str;
	}
	
	function isValidName($str){
		return (!empty($str) and !strpos($str, "<") and !strpos($str, ">") and
				!strpos($str, ";") and !strpos($str, '"') and
				!strpos($str, "\n") and !strpos($str, "\t"));
	}
	
	function getRandomStr($len) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		return substr(str_shuffle(substr(str_shuffle($chars), 0, $len / 2 + 1) . substr(str_shuffle($chars), 0, $len / 2 + 1)), 0, $len);
	}
	
	/**
	 * removePrivateKeys
	 * Remove the private keys in an array (i.e., keys that start with an underscore)
	 */
	function removePrivateKeys(&$array, $secretLvl = 1){
		foreach ($array as $key => $val)
			if (substr($key, 0, $secretLvl) == str_repeat("_", $secretLvl)) unset($array[$key]);
	}
	
	function filterOutPrivateKeys($array, $secretLvl = 1){
		foreach ($array as $key => $val)
			if (substr($key, 0, $secretLvl) == str_repeat("_", $secretLvl))  unset($array[$key]);
		
		return $array;
	}
}
