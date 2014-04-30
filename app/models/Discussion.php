<?php
/**
 * The discussion model used in the system
 *
 * @author	Xiangyu Bu
 * @version	0.1
 */

namespace models;

class Discussion extends \Model {

	const DISCUSSION_CACHE_TTL = 1800;
	
	/**
	 * Support level2 list only.
	 */
	function listById($id) {
		//if ($this->cache->exists("discussion_id_" . $id))
		//	return $this->cache->get("discussion_id_" . $id);
		
		$result = $this->queryDb("SELECT * FROM discussions WHERE id=:id OR parent_id=:id ORDER BY pin DESC, created_at ASC;", array(":id" => $id));
		$ret = array("count" => 0);
		if (empty($result) or count($result) == 0) return $ret;
		
		$ret["discussions"] = $result;
		$ret["count"] = count($result);
		//$this->cache->set("discussion_id_" . $id, $ret, static::DISCUSSION_CACHE_TTL);
		return $ret;
	}
	
	function listByBoardId($id) {
		$result = $this->queryDb("SELECT id FROM discussions WHERE board_id=:id AND parent_id=0 ORDER BY pin DESC, created_at DESC;", array(":id" => $id));
		if (empty($result) or count($result) == 0) return null;
		
		$discussions = array();
		foreach ($result as $key => $val)
			$discussions[] = $this->listById($val["id"]);
		
		return $discussions;
	}
	
	function create($parent_id, $user_id, $board_id, $subject, $body, $pin = 0){
		$created_at = date("Y-m-d H:i:s");
		$this->queryDb(
			"INSERT INTO discussions (parent_id, user_id, board_id, subject, body, created_at, last_update_at, pin) " .
			"VALUES (:parent_id, :user_id, :board_id, :subject, :body, :last_update_at, :last_update_at, :pin);",
			array(
				':parent_id' => $parent_id,
				':user_id' => $user_id,
				':board_id' => $board_id,
				':subject' => $subject,
				':body' => $body,
				':last_update_at' => $created_at,
				':pin' => $pin
			)
		);
		
		$result = $this->queryDb(
			"SELECT id FROM discussions WHERE parent_id=:parent_id AND user_id=:user_id AND board_id=:board_id AND created_at=:created_at LIMIT 1;", 
			array(
				':parent_id' => $parent_id,
				':user_id' => $user_id,
				':board_id' => $board_id,
				':created_at' => $created_at
			)
		);
		
		if ($parent_id > 0) $this->cache->clear("discussion_id_" . $parent_id);
		
		return $this->listById($result[0]["id"]);
	}
	
	function delete($id) {
		$this->queryDb("DELETE FROM discussions WHERE id=:id OR parent_id=:id LIMIT 1;", array(":id" => $id));
		if ($this->cache->exists("discussion_id_" . $id))
			$this->cache->clear("discussion_id_" . $id);
	}
	
	function save(&$discussion_info) {
		$discussion_info["last_update_at"] = date("Y-m-d H:i:s");
		$this->queryDb("UPDATE discussions " .
			"SET parent_id=:parent_id, user_id=:user_id, board_id=:board_id, discussion=:discussion, body=:body, last_update_at=:last_update_at ".
			"WHERE id=:id;",
			array(
				":id" => $discussion_info["id"],
				":parent_id" => $discussion_info["parent_id"],
				":user_id" => $discussion_info["user_id"],
				":board_id" => $discussion_info["board_id"],
				":discussion" => $discussion_info["discussion"],
				":body" => $discussion_info["body"],
				":last_update_at" => $discussion_info["last_update_at"],
			)
		);
		if ($this->cache->exists("discussion_id_" . $id))
			$this->cache->clear("discussion_id_" . $id);
	}
}