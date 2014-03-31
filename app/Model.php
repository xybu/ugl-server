<?php
/**
 * Model.php
 *
 * The base model class
 * @author	Xiangyu Bu
 * @date	Mar 16, 2014
 */

class Model extends \Prefab{
	protected $base = null;
	protected $cache = null;
	protected $db = null;
	
	function __construct(){
		$this->base = \Base::instance();
		$this->cache = \Cache::instance();
	}
	
	function queryDb($cmds, $args = null, $ttl=0, $log = true) {
		if ($this->db == null){
			if (\Registry::exists("db")) {
				$this->db = \Registry::get("db");
			} else {
				$this->db = new \DB\SQL("mysql:host=" . $this->base->get("DB_HOST") . ";port=" . $this->base->get("DB_PORT") . ";dbname=" . $this->base->get("DB_NAME") . "", $this->base->get("DB_USER"), $this->base->get("DB_PASS"));
				\Registry::set('db', $this->db);
			}
		}
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
	
	function isValidName($str){
		return (!empty($str) and !strpos($str, "<") and !strpos($str, ">") and
				!strpos($str, ";") and !strpos($str, '"') and
				!strpos($str, "\n") and !strpos($str, "\t"));
	}
	
	function filterAlias($str){
		return preg_replace("/[^\-_\w\d]/", "", $str);
	}
	
	function filterTitle($str, $len = 32){
		$str = preg_replace("/[\s]+/", " ", $str);
		$str = trim(preg_replace("/[^\-_\w\d\ ]/", "", $str));
		if (strlen($str) > $len) $str = substr($str, 0, $len - 1);
		return $str;
	}
	
	function filterContent($str, $len){
		$str = htmlspecialchars($str);
		$str = substr($str, 0, $len);
		return $str;
	}
	
	function filterHtmlChars($str){
		$str = str_replace("\t", "", $str);
		$str = str_replace("\n", "", $str);
		//$str = str_replace("&", "", $str);
		$str = str_replace("\"", "", $str);
		$str = str_replace("'", "", $str);
		$str = str_replace("<", "", $str);
		$str = str_replace(">", "", $str);
		return $str;
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
