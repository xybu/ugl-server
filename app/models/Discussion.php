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
		$result = $this->queryDb("SELECT * FROM discussions WHERE id=:id OR parent_id=:id ORDER BY created_at ASC;", array(":id" => $id));
		$ret = array("count" => 0);
		if (empty($result) or count($result) == 0) return $ret;
		$ret["discussions"] = $result;
		return array("count" => count($result), "discussion" => $discussions);
	}
	
	function create($parent_id, $user_id, $board_id, $discussion, $body){
		$created_at = date("Y-m-d H:i:s");
		$this->queryDb(
			"INSERT INTO discussions (parent_id, user_id, board_id, discussion, body, created_at, last_update_at) " .
			"VALUES (:parent_id, :user_id, :board_id, :discussion, :body, :last_update_at, :last_update_at);",
			array(
				':parent_id' => $parent_id,
				':user_id' => $user_id,
				':board_id' => $board_id,
				':discussion' => $discussion,
				':body' => $body,
				':last_update_at' => $created_at
			)
		);
		
		$result = $this->queryDb(
			"SELECT id FROM discussions WHERE parent_id=:parent_id AND user_id=:user_id AND board_id=:board_id AND created_at=:created_at LIMIT 1;", 
			array(
				':parent_id' => $parent_id,
				':user_id' => $user_id,
				':board_id' => $board_id,
				':discussion' => $discussion,
				':body' => $body,
				':created_at' => $created_at
			)
		);
		
		return $this->findById($result[0]["id"]);
	}
	
	function delete($id) {
		$this->queryDb("DELETE FROM discussions WHERE id=:id OR parent_id=:id LIMIT 1;", array(":id" => $id));
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
	}
}