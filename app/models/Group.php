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