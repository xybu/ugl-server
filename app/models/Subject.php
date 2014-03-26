<?php
/**
 * The Subject model used in the system
 *
 * @author	Xiangyu Bu
 * @version	0.1
 */

namespace models;

class Subject extends \Model {

	const SUBJECT_CACHE_TTL = 1800;
	
	function findById($id) {
		if ($this->cache->exists("subject_id_" . $id))
			return $this->cache->get("subject_id_" . $id);
		
		$result = $this->queryDb("SELECT * FROM subjects WHERE id=? LIMIT 1;", $id);
		if (count($result) == 1){
			$result = $result[0];
			$this->cache->set("subject_id_" . $id, $result, static::subject_CACHE_TTL);
		} else $result = null;
		
		return $result;
	}
	
	function findByParentId($id) {
		$result = $this->queryDb("SELECT id FROM subjects WHERE parent_id=?;", $id);
		if (empty($result) or count($result) == 0) return null;
		
		$subjects = array();
		foreach ($result as $k => $v) $subjects[] = $this->findById($v["id"]);
		return $subjects;
	}
	
	function findByUserId($user_id) {
		$result = $this->queryDb("SELECT id FROM subjects WHERE user_id=?;", $user_id);
		if (empty($result) or count($result) == 0) return null;
		
		$subjects = array();
		foreach ($result as $k => $v) $subjects[] = $this->findById($v["id"]);
		return $subjects;
	}
	
	function findByBoardId($board_id) {
		$result = $this->queryDb("SELECT id FROM subjects WHERE board_id=?;", $board_id);
		if (empty($result) or count($result) == 0) return null;
		
		$subjects = array();
		foreach ($result as $k => $v) $subjects[] = $this->findById($v["id"]);
		return $subjects;
	}
	
	function create($parent_id, $user_id, $board_id, $subject, $body){
		$created_at = date("Y-m-d H:i:s");
		$this->queryDb(
			"INSERT INTO subjects (parent_id, user_id, board_id, subject, body, created_at, last_update_at) " .
			"VALUES (:parent_id, :user_id, :board_id, :subject, :body, :last_update_at, :last_update_at);",
			array(
				':parent_id' => $parent_id,
				':user_id' => $user_id,
				':board_id' => $board_id,
				':subject' => $subject,
				':body' => $body,
				':last_update_at' => $created_at
			)
		);
		
		$result = $this->queryDb(
			"SELECT id FROM subjects WHERE parent_id=:parent_id AND user_id=:user_id AND board_id=:board_id AND created_at=:created_at LIMIT 1;", 
			array(
				':parent_id' => $parent_id,
				':user_id' => $user_id,
				':board_id' => $board_id,
				':subject' => $subject,
				':body' => $body,
				':created_at' => $created_at
			)
		);
		
		return $this->findById($result[0]["id"]);
	}
	
	function delete($subject_info) {
		$this->queryDb("DELETE FROM subjects WHERE id=? LIMIT 1;", $subject_info["id"]);
		
		if ($this->cache->exists("subject_id_" . $subject_info["id"]))
			$this->cache->clear("subject_id_" . $subject_info["id"]);
	}
	
	function save(&$subject_info) {
		$subject_info["last_update_at"] = date("Y-m-d H:i:s");
		$this->queryDb("UPDATE subjects " .
			"SET parent_id=:parent_id, user_id=:user_id, board_id=:board_id, subject=:subject, body=:body, last_update_at=:last_update_at ".
			"WHERE id=:id;",
			array(
				":id" => $subject_info["id"],
				":parent_id" => $subject_info["parent_id"],
				":user_id" => $subject_info["user_id"],
				":board_id" => $subject_info["board_id"],
				":subject" => $subject_info["subject"],
				":body" => $subject_info["body"],
				":last_update_at" => $subject_info["last_update_at"],
			)
		);
		
		if ($this->cache->exists("subject_id_" . $subject_info["id"]))
			$this->cache->set("subject_id_" . $subject_info["id"], $subject_info);
	}
}