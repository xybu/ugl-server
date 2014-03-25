<?php
/**
 * The board model used in the system
 *
 * @author	Xiangyu Bu
 * @version	0.1
 */

namespace models;

class Board extends \Model {

	const BOARD_CACHE_TTL = 1800;
	
	function findById($id) {
		if ($this->cache->exists("board_id_" . $id))
			return $this->cache->get("board_id_" . $id);
		
		$result = $this->queryDb("SELECT * FROM boards WHERE id=? LIMIT 1;", $id);
		if (count($result) == 1){
			$result = $result[0];
			$this->cache->set("board_id_" . $id, $result, static::BOARD_CACHE_TTL);
		} else $result = null;
		
		return $result;
	}
	
	function findByUserId($user_id) {
		$result = $this->queryDb("SELECT id FROM boards WHERE user_id=? LIMIT 1;", $user_id);
		if (empty($result) or count($result) == 0) return null;
		
		$boards = array();
		foreach ($result as $k => $v) $boards[] = $this->findById($v["id"]);
		return $boards;
	}
	
	function findByGroupId($group_id) {
		$result = $this->queryDb("SELECT id FROM boards WHERE group_id=? LIMIT 1;", $group_id);
		if (empty($result) or count($result) == 0) return null;
		
		$boards = array();
		foreach ($result as $k => $v) $boards[] = $this->findById($v["id"]);
		return $boards;
	}
	
	function create($user_id, $group_id, $title, $description, $order = 0){
		$created_at = date("Y-m-d H:i:s");
		$this->queryDb(
			"INSERT INTO boards (user_id, group_id, title, description, order, created_at, last_active_at) " .
			"VALUES (:user_id, :group_id, :title, :description, :order, :created_at, NOW()); ",
			array(
				':user_id' => $user_id,
				':group_id' => $group_id,
				':title' => $title,
				':description' => $description,
				':order' => $order,
				':created_at' => $created_at
			)
		);
		
		$result = $this->queryDb(
			"SELECT id FROM boards WHERE user_id=:user_id AND group_id=:group_id AND created_at=:created_at LIMIT 1;", 
			array(
				':user_id' => $user_id,
				':group_id' => $group_id,
				':created_at' => $created_at
			)
		);
		
		return $this->findById($result[0]["id"]);
	}
	
	function save(&$board_info){
		$this->queryDb("UPDATE boards " .
			"SET order=:order, user_id=:user_id, group_id=:group_id, title=:title, description=:description, last_active_at=NOW() ".
			"WHERE id=:id;",
			array(
				":id" => $board_info["id"],
				":order" => $board_info["order"],
				":user_id" => $board_info["user_id"],
				":group_id" => $board_info["group_id"],
				":title" => $board_info["title"],
				":description" => $board_info["description"]
			)
		);
		
		if ($this->cache->exists("board_id_" . $board_info["id"]))
			$this->cache->set("board_id_" . $board_info["id"], $board_info);
	}
}