<?php
/**
 * The group model used in the system
 *
 * @author	Xiangyu Bu
 * @version	0.2
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
		$result = $this->queryDb("SELECT * FROM groups WHERE id=?;", $id, 1800);
		if (count($result) == 1)
			return $result[0];
		return null;
	}
	
	function findByGroupAlias($alias){
		$result = $this->queryDb("SELECT * FROM groups WHERE alias=?;", $alias, 1800);
		if (count($result) == 1)
			return $result[0];
		return null;
	}
	
	// public only
	function findByKeyword($keyword){
		$result = $this->queryDb("SELECT * FROM groups WHERE visibility >= 1 AND CONCAT_WS(' ', alias, description, tags) LIKE '?';", "%" . $keyword . "%", 7200);
		if (count($result) > 0)
			return $result;
		return null;
	}
	
	function listGroupsOfUserId($user_id, $visibility = 0){
		$result = $this->queryDb("SELECT * FROM groups WHERE visibility>=? AND users LIKE '%\"" . $user_id . "\"%';", $visibility, 1800);
		if (count($result) > 0)
			return $result;
		return null;
	}
	
	function updateGroupProfile($id, $visibility, $alias, $description, $tags){
		$this->queryDb(
			"UPDATE groups SET visibility=:visibility, alias=:alias, description=:description, tags=:tags WHERE id=:id LIMIT 1;",
			array(
				":id" => $id,
				":visibility" => $visibility,
				":alias" => $alias,
				":description" => $description,
				":tags" => $tags,
			)
		);
	}
	
	function updateCreatorUserId($gid, $uid){
		$this->queryDb(
			"UPDATE groups SET creator_user_id=:uid WHERE id=:gid LIMIT 1;",
			array(
				":gid" => $gid,
				":uid" => $uid
			)
		);
	}
	
	function updateGroupUsers($id, $users){
		$this->queryDb(
			"UPDATE groups SET users=:users WHERE id=:id LIMIT 1;",
			array(
				":id" => $id,
				":users" => $users
			)
		);
	}
	
	function inviteUserToGroup($uid, $gid){
		
	}
	
	function addGroupUser($gid, $uid, $role){
		
	}
	
	function updateUserRole($gid, $uid, $role){
		
	}
	
	function deleteGroupMember($gid, $uid){
		
	}
	
}