<?php
/**
 * The group model used in the system
 *
 * @author	Xiangyu Bu
 * @version	0.2
 */

namespace models;

class Group extends \Model {
	
	const MAX_ALIAS_LENGTH = 32;
	const MAX_DESC_LENGTH = 150;
	const GROUP_RECORD_TTL = 1800; //sec
	
	/**
	 * isValidGroupName
	 * Check if the string contains chars other than alphanumerical, -, _
	 * and non-empty, and length not greater than 65.
	 */
	static function isValidAlias($str){
		if ($str === "") return false;
		if (preg_match('/[^\-_A-Za-z0-9]/', $str)) return false;
		if (strlen($str) > static::MAX_ALIAS_LENGTH) return false;
		return true;
	}
	
	static function isValidVisibility($v){
		return is_numeric($v) and $v >= 0 and $v < 65;
	}
	
	static function filterDescription($str){
		$str = htmlspecialchars($str);
		$str = substr($str, 0, static::MAX_DESC_LENGTH);
		//$str = filter_var($str, FILTER_SANITIZE_SPECIAL_CHARS);
		//$str = filter_var($str, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH);
		
		return $str;
	}
	
	static function filterTags($str){
		return preg_replace("[^A-Za-z0-9 ]", "", strtolower($str));
	}
	
	function findByGroupId($id){
		if ($this->cache->exists("group_id_" . $id))
			return $this->cache->get("group_id_" . $id);
			
		$result = $this->queryDb("SELECT * FROM groups WHERE id=?;", $id);
		if (count($result) == 1){
			$result = $result[0];
			$result["users"] = json_decode($result["users"], true);
			$this->cache->set("group_id_" . $id, $result, static::GROUP_RECORD_TTL);
		} else $result = null;
		
		return $result;
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
		$ids = $this->queryDb("SELECT id FROM groups WHERE users LIKE '%\"" . $user_id . "\"%';");
		$result = array();
		foreach ($ids as $i => $d){
			$g_data = $this->findByGroupId($d["id"]);
			if ($g_data["visibility"] >= $visibility)
				$result[] = $g_data;
		}
		
		if (count($result) > 0)
			return array("count" => count($result), "groups" =>$result);
		return array("count" => 0);
	}
	
	function create($user_id, $name, $desc, $tags, $visibility){
		$this->queryDb(
			"INSERT INTO groups (creator_user_id, visibility, alias, description, tags, users, created_at) " .
			"VALUES (:user_id, :visibility, :alias, :desc, :tags, :users, NOW()); ",
			array(
				':user_id' => $user_id,
				':visibility' => $visibility,
				':alias' => $name,
				':desc' => $desc,
				':tags' => $tags,
				':users' => json_encode(array("admin" => array($user_id)))
			)
		);
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
		$this->cache->clear("group_id_" . $id);
	}
	
	function deleteById($gid){
		//TODO: delete all records related to the group before deleting it
		
		// delete all the news about the group
		$news = new News();
		$news->deleteByGroupId($gid);
		
		// delete the group
		$this->queryDb("DELETE FROM groups WHERE id=?;", $gid);
		$this->cache->clear("group_id_" . $id);
	}
	
	function setStatus($isActive = false){
		
	}
	
	function changeCreatorUserId($gid, $uid){
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