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
	const BOARD_QUERY_CACHE_TTL = 600;
	
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
		$result = $this->queryDb("SELECT id FROM boards WHERE user_id=:user_id OR group_id IN (SELECT _joined_groups FROM users WHERE id=:user_id)", array(":user_id" => $user_id), static::BOARD_QUERY_CACHE_TTL);
		if (empty($result) or count($result) == 0) return null;
		
		$boards = array();
		foreach ($result as $k => $v) $boards[] = $this->findById($v["id"]);
		return array("count" => count($boards), "boards" => $boards);
	}
	
	function findByGroupId($group_id) {
		$result = $this->queryDb("SELECT id FROM boards WHERE group_id=?;", $group_id, static::BOARD_QUERY_CACHE_TTL);
		if (empty($result) or count($result) == 0) return null;
		
		$boards = array();
		foreach ($result as $k => $v) $boards[] = $this->findById($v["id"]);
		return $boards;
	}
	
	function findByTitleAndIds($title, $user_id, $group_id) {
		$result = $this->queryDb("SELECT id FROM boards WHERE title=:title AND (group_id=:group_id OR (user_id=:user_id AND group_id IS NULL)) LIMIT 1;",
			array(
				":title" => $title, 
				":user_id" => $user_id, 
				":group_id" => $group_id
			)
		);
		
		if (empty($result) or count($result) == 0) return null;
		
		return $this->findById($result[0]["id"]);
	}
	
	function create($user_id, $group_id, $title, $description) {
		$this->queryDb(
			"INSERT INTO boards (user_id, group_id, title, description, created_at, last_active_at) " .
			"VALUES (:user_id, :group_id, :title, :description, NOW(), NOW()); ",
			array(
				':user_id' => $user_id,
				':group_id' => $group_id,
				':title' => $title,
				':description' => $description
			)
		);
		return $this->findByTitleAndIds($title, $user_id, $group_id);
	}
	
	function delete($board_info) {
		$this->queryDb("DELETE FROM boards WHERE id=? LIMIT 1;", $board_info["id"]);
		
		if ($this->cache->exists("board_id_" . $board_info["id"]))
			$this->cache->clear("board_id_" . $board_info["id"]);
	}
	
	function save(&$board_info) {
		$board_info["last_active_at"] = date("Y-m-d H:i:s");
		$this->queryDb("UPDATE boards " .
			"SET order=:order, user_id=:user_id, group_id=:group_id, title=:title, description=:description, last_active_at=:last_active_at ".
			"WHERE id=:id;",
			array(
				":id" => $board_info["id"],
				":order" => $board_info["order"],
				":user_id" => $board_info["user_id"],
				":group_id" => $board_info["group_id"],
				":title" => $board_info["title"],
				":description" => $board_info["description"],
				":last_active_at" => $board_info["last_active_at"]
			)
		);
		
		if ($this->cache->exists("board_id_" . $board_info["id"]))
			$this->cache->set("board_id_" . $board_info["id"], $board_info);
	}
}