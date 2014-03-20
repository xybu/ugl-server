<?php
/**
 * The board model used in the system
 *
 * @author	Xiangyu Bu
 * @version	0.1
 */

namespace models;

class Board extends \Model {
	
	function findById($id){
		$result = $this->queryDb("SELECT * FROM boards WHERE id=? LIMIT 1;", $id, 1800);
		if (count($result) == 1) return $result[0];
		return null;
	}
	
	function findByTitle($title){
		$result = $this->queryDb("SELECT * FROM boards WHERE id=? LIMIT 1;", $id, 1800);
		if (count($result) == 1) return $result[0];
		return null;
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
		
		return $result[0];
	}
}