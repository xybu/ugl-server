<?php
/**
 * Model.php
 *
 * The base model class
 * @author	Xiangyu Bu
 * @date	Mar 03, 2014
 */

class Model {
	protected $f3;
	protected $cache;
	protected $db = null;
	protected $logger = null;
	
	function __construct() {
		$this->f3=Base::instance();
		$this->cache = \Cache::instance();
	}
	
	function connectDb() {
		if (!$this->db)
			$this->db=new DB\SQL("mysql:host=localhost;port=3306;dbname=ugl_test","root","");
			//$this->db=new DB\SQL("mysql:host=localhost;port=3306;dbname=ugli_wdyBzpxs","ugli_pJmjACwx","KjwfF4Sp");
		//new DB\SQL\Session($db); // Use database-managed sessions
		
		//$this->db=$db;
	}
	
	function queryDb($cmds, $args=NULL, $ttl=0, $log=TRUE) {
		//TODO: always connect to improve performance
		if (!$this->db) $this->connectDb();
		
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
		return (!$str === "" && !strstr($str, "<") && !strstr($str, ">") &&
				!strstr($str, ";") && !strstr($str, '"') &&
				!strstr($str, "\n") && !strstr($str, "\t"));
	}
	
	function getRandomStr($len) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		
		return substr(str_shuffle(substr(str_shuffle($chars), 0, $len / 2 + 1) . substr(str_shuffle($chars), 0, $len / 2 + 1)), 0, $len);
	}
}
