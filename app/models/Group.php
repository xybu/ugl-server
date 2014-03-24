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
	const GROUP_RECORD_TTL = 1800; // in sec
	
	const STATUS_CLOSED = 0; // appears non-existent to users
	const STATUS_INACTIVE = 1; // users can still access the group data, but cannot change it 
	const STATUS_PRIVATE = 2; // private groups are invisible to outsiders and are invitation-only
	const STATUS_PUBLIC = 3; // everyone can see the group
	
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
	
	// default group preferences
	static $DEFAULT_GROUP_PREFS = array(
		"autoApproveApplication" => 0
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
	
	static function isValidStatus($v){
		return is_numeric($v) and $v > static::STATUS_CLOSED and $v <= static::STATUS_PUBLIC;
	}
	
	static function filterDescription($str){
		$str = htmlspecialchars($str);
		$str = substr($str, 0, static::MAX_DESC_LENGTH);
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
		
		$result = $this->queryDb("SELECT * FROM groups WHERE id=? AND status > " . static::STATUS_CLOSED, $id);
		if (count($result) == 1){
			$result = $result[0];
			$result["__users_raw"] = $result["users"];
			$result["users"] = json_decode($result["users"], true);
			if ($result["_preferences"]) $result["_preferences"] = json_decode($result["_preferences"], true);
			else $result["_preferences"] = self::$DEFAULT_GROUP_PREFS;
			
			$this->cache->set("group_id_" . $id, $result, static::GROUP_RECORD_TTL);
		} else $result = null;
		
		return $result;
	}
	
	function findByAlias($alias){
		$result = $this->queryDb("SELECT id FROM groups WHERE alias=? AND status > " . static::STATUS_CLOSED, $alias);
		if (count($result) == 1)
			return $this->findById($result[0]["id"]);
		return null;
	}
	
	// public only
	function findByKeyword($keyword){
		$ids = $this->queryDb("SELECT id FROM groups WHERE status=" . static::STATUS_PUBLIC . " AND CONCAT_WS(' ', alias, description, tags) LIKE '?';", "%" . $keyword . "%", 7200);
		if (count($ids) > 0){
			$result = array();
			foreach ($ids as $k => $id) $result[] = $this->findById($id);
			return $result;
		}
		return null;
	}
	
	// corresponding to static::STATUS_INACTIVE
	function listGroupsOfUserId($user_id, $target_status = 1){
		$ids = $this->queryDb("SELECT id FROM groups WHERE status >= " . $target_status . " AND users LIKE '%\"" . $user_id . "\"%' ORDER BY status DESC");
		$result = array();
		foreach ($ids as $i => $d)
			$result[] = $this->findById($d["id"]);
		
		if (count($result) > 0)
			return array("count" => count($result), "groups" =>$result);
		return array("count" => 0);
	}
	
	function getDefaultRoleName(){
		return "member";
	}
	
	function getPendingRoleName(){
		return "pending";
	}
	
	function getInviteeRoleName(){
		return "invitee";
	}
	
	function getPermissions($user_id, $group_id, $group_data = null){
		
		$permissions = self::$DEFAULT_GROUP_PERMISSION;
		if (!$group_data) $group_data = $this->findById($group_id);
		
		if (array_key_exists("admin", $group_data["users"]) and $user_id > 0 and in_array($user_id, $group_data["users"]["admin"])){
			$permissions["role_name"] = "admin";
			$permissions["view_profile"] = true;
			$permissions["view_board"] = true;
			if ($group_data["status"] > static::STATUS_INACTIVE){
				$permissions["new_board"] = true;
				$permissions["edit_board"] = true;
				$permissions["del_board"] = true;
				$permissions["post"] = true; 
				$permissions["comment"] = true; 
				$permissions["delete"] = true;
			}
			$permissions["edit"] = true;
			$permissions["manage"] = true;
		} else if (array_key_exists("member", $group_data["users"]) and $user_id > 0 and in_array($user_id, $group_data["users"]["member"])){
			$permissions["role_name"] = "member";
			$permissions["view_profile"] = true;
			$permissions["view_board"] = true;
			if ($group_data["status"] > static::STATUS_INACTIVE){
				$permissions["new_board"] = true;
				$permissions["edit_board"] = true;
				$permissions["post"] = true;
				$permissions["comment"] = true;
			}
		} else {
			// invitees and applicants are treated as guests
			
			if ($group_data["status"] > static::STATUS_PRIVATE)
				$permissions["view_profile"] = true;
			
			if ($user_id > 0 and array_key_exists("pending", $group_data["users"]) and in_array($user_id, $group_data["users"]["pending"])){
				$permissions["role_name"] = "pending";
			} else if ($user_id > 0 and $group_data["status"] > static::STATUS_PRIVATE)
				// of course a guest cannot apply
				$permissions["apply"] = true;
		}
		
		return $permissions;
	}
	
	function create($user_id, $alias, $desc, $tags, $status){
		$this->queryDb(
			"INSERT INTO groups (creator_user_id, status, alias, description, tags, users, created_at) " .
			"VALUES (:user_id, :status, :alias, :desc, :tags, :users, NOW()); ",
			array(
				':user_id' => $user_id,
				':status' => $status,
				':alias' => $alias,
				':desc' => $desc,
				':tags' => $tags,
				':users' => json_encode(array("admin" => array($user_id)))
			)
		);
		
		return $this->findByAlias($alias);
	}
	
	function update(&$group_data, $alias, $desc, $tags, $status){
		$changed = false;
		
		if ($alias != $group_data["alias"]){
			$changed = true;
			$group_data["alias"] = $alias;
		}
		
		if ($desc != $group_data["description"]){
			$changed = true;
			$group_data["description"] = $desc;
		}
		
		if ($tags != $group_data["tags"]){
			$changed = true;
			$group_data["tags"] = $tags;
		}
		
		if ($status != $group_data["status"]){
			$changed = true;
			$group_data["status"] = $status;
		}
		
		if ($changed) $this->save($group_data);
	}
	
	function save($group_data){
		$this->queryDb("UPDATE groups " .
			"SET status=:status, alias=:alias, description=:description, avatar_url=:avatar_url, tags=:tags, creator_user_id=:creator_user_id, users=:users, _preferences=:prefs ".
			"WHERE id=:id;",
			array(
				":id" => $group_data["id"],
				":status" => $group_data["status"],
				":alias" => $group_data["alias"],
				":description" => $group_data["description"],
				":avatar_url" => $group_data["avatar_url"],
				":tags" => $group_data["tags"],
				":creator_user_id" => $group_data["creator_user_id"],
				":users" => json_encode($group_data["users"]),
				":prefs" => json_encode($group_data["_preferences"]),
			)
		);
		if ($this->cache->exists("group_id_" . $group_data["id"]))
			$this->cache->set("group_id_" . $group_data["id"], $group_data);
	}
	
	function delete(&$group_info, User $user = null){
		//TODO: delete all records related to the group before deleting it
		
		if ($user == null) $user = new User();
		
		foreach ($group_info["users"] as $role => $ids) {
			foreach ($ids as $id){
				$temp = $user->findById($id);
				$user->leaveGroup($temp, $group_info["id"]);
				$user->save($temp);
			}
		}
		
		$this->queryDb("UPDATE groups SET status=:status WHERE id=:id", array(":status" => static::STATUS_CLOSED, ":id" => $group_info["id"]));
		$this->cache->clear("group_id_" . $group_info["id"]);
	}
	
	function setCreatorUserId($uid, &$group_data){
		$group_data["creator_user_id"] = $uid;
		
		if ($this->cache->exists("group_id_" . $group_data["id"]))
			$this->cache->set("group_id_" . $group_data["id"], $group_data);
	}
	
	function hasUser($group_data, $user_id) {
		foreach ($group_data["__users_raw"] as $role => $users){
			$i = array_search("" . $uid . "", $users);
			if (!($i === false)) return true;
		}
		return false;
	}
	
	function getRoleOf($group_data, $user_id) {
		foreach ($group_data["__users_raw"] as $role => $users){
			$i = array_search("" . $uid . "", $users);
			if (!($i === false)) return $role;
		}
		return "guest";
	}
	
	function addUser($uid, $role, &$group_data){
		$group_data["users"][$role][] = $uid;
	}
	
	function kickUser($uid, &$group_data){
		$flag = $this->hasUser($group_data, $uid);
		
		if ($flag and $this->cache->exists("group_id_" . $group_data["id"]))
			$this->cache->set("group_id_" . $group_data["id"], $group_data);
		
		return $flag;
	}
	
	function changeUserRole($uid, $role, &$group_data){
		$this->kickUser($uid, $group_data);
		$group_data["users"][$role][] = "" . $uid . "";
		
		if ($flag and $this->cache->exists("group_id_" . $group_data["id"]))
			$this->cache->set("group_id_" . $group_data["id"], $group_data);
	}
}