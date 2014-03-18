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
	
	// group-wide (minimum) permissions
	static $DEFAULT_GROUP_PERMISSION = array(
		"role_name" => "guest",
		"view_profile" => false,	// view group profile
		"apply" => false, 		// apply to join
		"view_board" => false,	// view the boards
		"new_board" => false,	// new board
		"edit_board" => false,	// edit a board
		"del_board" => false,	// delete a board
		"post" => false, 	// post subjects
		"comment" => false, // comment on posts
		"delete" => false,	// delete posts
		"edit" => false, 	// edit subjects
		"manage" => false 	// manage members and change profile
	);
	
	/**
	 * isValidGroupName
	 * Check if the string contains chars other than alphanumerical, -, _
	 * and non-empty, and length not greater than 65.
	 */
	static function isValidAlias($str){
		if (empty($str)) return false;
		if (preg_match('/[^\-_A-Za-z0-9]/', $str)) return false;
		if (strlen($str) > static::MAX_ALIAS_LENGTH) return false;
		return true;
	}
	
	static function isValidVisibility($v){
		return is_numeric($v) and $v >= 0 and $v < 64;
	}
	
	static function isPubliclyVisible($v){
		return $v > 0;
	}
	
	static function filterDescription($str){
		$str = htmlspecialchars($str);
		$str = substr($str, 0, static::MAX_DESC_LENGTH);
		//$str = filter_var($str, FILTER_SANITIZE_SPECIAL_CHARS);
		//$str = filter_var($str, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH);
		
		return $str;
	}
	
	static function filterTags($str){
		// remove non alphanumerical chars or delimiters
		$str = preg_replace("/[^A-Za-z0-9 ]/", "", strtolower($str));
		// remove repeated words
		$str = preg_replace("/\b(\w+)\s+\\1\b/i", "$1", $str);
		$str_a = explode(" ", $str);
		sort($str_a); // sort the words
		$str = implode(" ", $str_a);
		return $str;
	}
	
	function findById($id){
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
	
	function findByAlias($alias){
		$result = $this->queryDb("SELECT id FROM groups WHERE alias=?;", $alias);
		if (count($result) == 1)
			return $this->findById($result[0]["id"]);
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
			$g_data = $this->findById($d["id"]);
			if ($g_data["visibility"] >= $visibility)
				$result[] = $g_data;
		}
		
		if (count($result) > 0)
			return array("count" => count($result), "groups" =>$result);
		return array("count" => 0);
	}
	
	function getPermissions($user_id, $group_id, $group_data = null){
		
		$permissions = self::$DEFAULT_GROUP_PERMISSION;
		if (!$group_data)
			$group_data = $this->findById($group_id);
		
		if (array_key_exists("admin", $group_data["users"]) and in_array($user_id, $group_data["users"]["admin"])){
			$permissions["role_name"] = "admin";
			$permissions["view_profile"] = true;
			$permissions["view_board"] = true;
			$permissions["new_board"] = true;
			$permissions["edit_board"] = true;
			$permissions["del_board"] = true;
			$permissions["post"] = true; 
			$permissions["comment"] = true; 
			$permissions["delete"] = true;
			$permissions["edit"] = true;
			$permissions["manage"] = true;
		} else if (array_key_exists("member", $group_data["users"]) and in_array($user_id, $group_data["users"]["member"])){
			$permissions["role_name"] = "member";
			$permissions["view_profile"] = true;
			$permissions["view_board"] = true;
			$permissions["new_board"] = true;
			$permissions["edit_board"] = true;
			$permissions["post"] = true;
			$permissions["comment"] = true;
		} else {
			if ($group_data["visibility"] > 0)
				$permissions["view_profile"] = true;
			
			if (array_key_exists("pending", $group_data["users"]) and in_array($user_id, $group_data["users"]["pending"])){
				$permissions["role_name"] = "applicant";
			}
		}
		
		return $permissions;
	}
	
	function create($user_id, $alias, $desc, $tags, $visibility){
		$this->queryDb(
			"INSERT INTO groups (creator_user_id, visibility, alias, description, tags, users, created_at) " .
			"VALUES (:user_id, :visibility, :alias, :desc, :tags, :users, NOW()); ",
			array(
				':user_id' => $user_id,
				':visibility' => $visibility,
				':alias' => $alias,
				':desc' => $desc,
				':tags' => $tags,
				':users' => json_encode(array("admin" => array($user_id)))
			)
		);
		
		return $this->findByAlias($alias);
	}
	
	function save($group_data){
		$this->querDb("UPDATE groups " .
			"SET visibility=:visibility, alias=:alias, description=:description, avatar_url=:avatar_url, tags=:tags, creator_user_id=:creator_user_id, users=:users ".
			"WHERE id=:id;",
			array(
				":id" => $group_data["id"],
				":visibility" => $group_data["visibility"],
				":alias" => $group_data["alias"],
				":description" => $group_data["description"],
				":avatar_url" => $group_data["avatar_url"],
				":tags" => $group_data["tags"],
				":creator_user_id" => $group_data["creator_user_id"],
				":users" => json_encode($group_data["users"])
			)
		);
	}
	
	function deleteById($gid){
		//TODO: delete all records related to the group before deleting it
		
		// delete all the news about the group
		//$news = new News();
		//$news->deleteByGroupId($gid);
		
		// delete the group
		$this->queryDb("DELETE FROM groups WHERE id=?;", $gid);
		$this->cache->clear("group_id_" . $gid);
	}
	
	function setCreatorUserId($uid, &$group_data){
		$group_data["creator_user_id"] = $uid;
		
		if ($this->cache->exists("group_id_" . $gid))
			$this->cache->set("group_id_" . $gid, $group_data);
	}
	
	function addUser($uid, $role, &$group_data){
		$group_data["users"][$role][] = $uid;
		
		if ($this->cache->exists("group_id_" . $gid))
			$this->cache->set("group_id_" . $gid, $group_data);
	}
	
	function kickUser($uid, &$group_data){
		$flag = false;
		
		foreach ($group_data["users"] as $role => $users){
			$i = array_search("" . $uid . "", $users);
			if ($i) {
				unset($group_data["users"][$role][$i]);
				$flag = true;
			}
		}
		
		if ($flag and $this->cache->exists("group_id_" . $gid))
				$this->cache->set("group_id_" . $gid, $group_data);
		
		return $flag;
	}
	
	function changeUserRole($uid, $role, &$group_data){
		$this->kickUser($uid, $group_data);
		$group_data["users"][$role][] = "" . $uid . "";
		
		if ($this->cache->exists("group_id_" . $gid))
			$this->cache->set("group_id_" . $gid, $group_data);
	}
}