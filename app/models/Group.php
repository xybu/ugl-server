<?php
/**
 * Group.php
 * The group data model
 *
 * @author	Xiangyu Bu
 * @date	Mar 10, 2014
 */

namespace models;

class Group extends \Model {
	
	/**
	 * isValidGroupName
	 * Check if the string contains chars other than alphanumerical, -, _
	 * and non-empty, and length not greater than 65.
	 */
	static function isValidGroupName($str){
		if ($str === "") return false;
		if (preg_match('/[^\-_A-Za-z0-9]/', $str)) return false;
		if (strlen($str) < 65) return false;
		return true;
	}
	
	function findByGroupId($id){
	}
	
	function findByGroupName($name){
	}
	
	function listGroupsOfUserId($user_id, $visibility = 0){
		$result = $this->queryDb("SELECT * FROM groups WHERE visibility >= " . $visibility . " AND users LIKE '%\"" . $user_id . "\"%';");
		if (count($result) > 0)
			return $result;
		return null;
	}
	
	function updateGroupProfile($settings){
	}
	
	function addGroupMembers($names){
	}
	
	function deleteGroupMembers($names){
	}
	
	function updateGroupMembers($list){
	}
}