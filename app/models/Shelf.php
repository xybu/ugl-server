<?php
namespace models;

class Shelf extends \Model {
	
	function findById($id) {
		//if ($this->cache->exists("shelf_id_" . $id))
		//	return $this->cache->get("shelf_id_" . $id);
		
		$result = $this->queryDb("SELECT * FROM shelves WHERE id=? LIMIT 1;", $id);
		if (count($result) == 1) {
			$result = $result[0];
			//$this->cache->set("shelf_id_" . $id, $result, static::WALLET_CACHE_TTL);
		} else $result = null;
		
		return $result;
	}
	
	function findByUserId($user_id) {
		$result = $this->queryDb("SELECT id FROM shelves WHERE user_id=:user_id OR group_id IN (SELECT _joined_groups FROM users WHERE id=:user_id) ORDER BY group_id ASC", array(":user_id" => $user_id));
		if (empty($result) or count($result) == 0) return null;
		
		$shelves = array();
		foreach ($result as $k => $v) $shelves[] = $this->findById($v["id"]);
		return array("count" => count($shelves), "shelves" => $shelves);
	}
	
	function findByGroupId($group_id) {
		$result = $this->queryDb("SELECT id FROM shelves WHERE group_id=?;", $group_id);
		if (empty($result) or count($result) == 0) return null;
		
		$shelves = array();
		foreach ($result as $k => $v) $shelves[] = $this->findById($v["id"]);
		return $shelves;
	}
	
	function findByNameAndIds($name, $user_id, $group_id) {
		$result = $this->queryDb("SELECT id FROM shelves WHERE name=:name AND (group_id=:group_id OR (user_id=:user_id AND group_id IS NULL)) LIMIT 1;",
			array(
				":name" => $name, 
				":user_id" => $user_id, 
				":group_id" => $group_id
			)
		);
		
		if (empty($result) or count($result) == 0) return null;
		
		return $this->findById($result[0]["id"]);
	}
	
	function findItemsByShelfId($shelf_id) {
		$result = $this->queryDb(
			"SELECT * FROM shelf_items WHERE shelf_id=:shelf_id ORDER BY created_at DESC", 
			array(
				":shelf_id" => $shelf_id
			));
		$ret = array("count" => count($result));
		if (count($result) > 0) {
			$ret["items"] = $result;
		}
		
		return $ret;
	}
	
	function findItemById($record_id) {
		$result = $this->queryDb("SELECT * FROM shelf_items WHERE id=? LIMIT 1;", $record_id);
		if (count($result) == 0) return null;
		return $result[0];
	}
	
}
