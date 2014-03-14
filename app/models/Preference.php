<?php
/**
 * Preference
 * A general model for preferences field.
 *
 * To use it in a specific model, the model class must have
 * a default associative defined as the normal data set.
 *
 * @author	Xiangyu Bu <xybu92@live.com>
 * @version	1.1
 */

namespace models;

class Preference {
	protected $data = array();
	
	/**
	 * Constructor
	 * @param str		the string to be parsed
	 * @param isJson	if true, str will be parsed as JSON text
	 *					otherwise, parsed as a serialized array
	 */
	public function __construct($str = null, $isJson = false){
		if (!empty($str)){
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
	
	/**
	 * normalize
	 * Given the default key-value pairs, add the missing keys to
	 * current data array.
	 * 
	 * @param def	The default key-value pair
	 */
	public function normalize($def){
		$this->data = array_merge($def, $this->data);
	}
	
	public function toString(){
		return serialize($this->data);
	}
	
	public function toJson(){
		return json_encode($this->data);
	}
	
	public function toArray(){
		return $this->data;
	}
}