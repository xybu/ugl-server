<?php
/**
 * Prefernce.php
 * A general model for preferences field
 
 * @author	Xiangyu Bu
 * @date	Mar 12, 2014
 */

namespace models;

class Preference {
	private $data = array();
	
	/**
	 * Constructor
	 * @param str		the string to be parsed
	 * @param isJson	if true, str will be parsed as JSON text
	 *					otherwise, parsed as a serialized array
	 */
	public function __construct($str, $isJson = false){
		if ($str != "") {
			if ($isJson) $this->data = json_decode($str, true); // to associative array
			else $this->data = unserialize($str);
		}
	}
	
	public function __destruct(){
	}
	
	public function hasField($f){
		return array_key_exists($f, $this->data);
	}
	
	public function getField($f){
		if ($this->hasField($f))
			return $this->data[$f];
		return null;
	}
	
	public function setField($f, $v){
		$this->data[$f] = $v;
	}
	
	public function unsetField($f){
		if (!$this->hasField($f)) return;
		unset($this->data[$f]);
	}
	
	public function toJson(){
		return json_encode($this->data);
	}
	
	public function toString(){
		return serialize($this->data);
	}
	
	public function toArray(){
		return $this->data;
	}
}