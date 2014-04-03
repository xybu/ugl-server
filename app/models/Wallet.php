<?php
/**
 * A combined data model for wallet and wallet_records
 */

namespace models;

class Wallet extends \Model {
	
	const WALLET_CACHE_TTL = 1800;
	const WALLET_QUERY_CACHE_TTL = 600;
	
	function findById($id) {
		if ($this->cache->exists("wallet_id_" . $id))
			return $this->cache->get("wallet_id_" . $id);
		
		$result = $this->queryDb("SELECT * FROM wallets WHERE id=? LIMIT 1;", $id);
		if (count($result) == 1) {
			$result = $result[0];
			$this->cache->set("wallet_id_" . $id, $result, static::WALLET_CACHE_TTL);
		} else $result = null;
		
		return $result;
	}
	
	function findByUserId($user_id) {
		$result = $this->queryDb("SELECT id FROM wallets WHERE user_id=:user_id OR group_id IN (SELECT _joined_groups FROM users WHERE id=:user_id) ORDER BY group_id ASC", array(":user_id" => $user_id));
		if (empty($result) or count($result) == 0) return null;
		
		$wallets = array();
		foreach ($result as $k => $v) $wallets[] = $this->findById($v["id"]);
		return array("count" => count($wallets), "wallets" => $wallets);
	}
	
	function findByGroupId($group_id) {
		$result = $this->queryDb("SELECT id FROM wallets WHERE group_id=?;", $group_id, static::WALLET_QUERY_CACHE_TTL);
		if (empty($result) or count($result) == 0) return null;
		
		$wallets = array();
		foreach ($result as $k => $v) $wallets[] = $this->findById($v["id"]);
		return $wallets;
	}
	
	function findByNameAndIds($name, $user_id, $group_id) {
		$result = $this->queryDb("SELECT id FROM wallets WHERE name=:name AND (group_id=:group_id OR (user_id=:user_id AND group_id IS NULL)) LIMIT 1;",
			array(
				":name" => $name, 
				":user_id" => $user_id, 
				":group_id" => $group_id
			)
		);
		
		if (empty($result) or count($result) == 0) return null;
		
		return $this->findById($result[0]["id"]);
	}
	
	function create($user_id, $group_id, $name, $description) {
		$this->queryDb(
			"INSERT INTO wallets (user_id, group_id, name, description, created_at) " .
			"VALUES (:user_id, :group_id, :name, :description, NOW()); ",
			array(
				':user_id' => $user_id,
				':group_id' => $group_id,
				':name' => $name,
				':description' => $description
			)
		);
		return $this->findByNameAndIds($name, $user_id, $group_id);
	}
	
	function delete($wallet_info) {
		$this->queryDb("DELETE FROM wallets WHERE id=? LIMIT 1;", $wallet_info["id"]);
		
		//if ($this->cache->exists("wallet_id_" . $wallet_info["id"]))
		//	$this->cache->clear("wallet_id_" . $wallet_info["id"]);
	}
	
	function save(&$wallet_info) {
		$wallet_info["last_update_at"] = date("Y-m-d H:i:s");
		$this->queryDb("UPDATE wallets " .
			"SET user_id=:user_id, group_id=:group_id, name=:name, description=:description, balance=:balance, last_active_at=:last_update_at ".
			"WHERE id=:id;",
			array(
				":id" => $wallet_info["id"],
				":user_id" => $wallet_info["user_id"],
				":group_id" => $wallet_info["group_id"],
				":name" => $wallet_info["name"],
				":description" => $wallet_info["description"],
				":balance" => $wallet_info["balance"],
				":last_update_at" => $wallet_info["last_update_at"]
			)
		);
		
		if ($this->cache->exists("wallet_id_" . $wallet_info["id"]))
			$this->cache->set("wallet_id_" . $wallet_info["id"], $wallet_info);
	}
	
	function findRecordsByWalletId($wallet_id, $limit = null) {
		if (!empty($limit)) $limit = " LIMIT " . $limit;
		else $limit = "";
		$result = $this->queryDb("SELECT * FROM wallet_records WHERE wallet_id=? ORDER BY created_at DESC" . $limit . "", $wallet_id);
		$ret = array("count" => count($result));
		if (count($result) > 0) {
			// array_column is a PHP function >= 5.5.0
			$ids = array_column($result, "id");
			$ret["records"] = array_combine($ids, $result);
		}
		
		return $ret;
	}
	
	function findRecordById($record_id) {
		//TODO: check cache chunk
		
		$result = $this->queryDb("SELECT * FROM wallet_records WHERE id=? LIMIT 1;", $record_id);
		if (count($result) == 0) return null;
		return $result[0];
	}
	
	function findRecordByIdsAndTime($user_id, $wallet_id, $created_at) {
		//TODO: check cache chunk
		
		$result = $this->queryDb("SELECT * FROM wallet_records WHERE user_id=:user_id AND wallet_id=:wallet_id AND created_at=:created_at LIMIT 1;",
			array(
				":user_id" => $user_id, 
				":wallet_id" => $wallet_id,
				":created_at" => $created_at
			)
		);
		
		if (empty($result) or count($result) == 0) return null;
		
		return $result[0];
	}
	
	function createRecord($user_id, $wallet_id, $category, $sub_category, $amount, $description, &$wallet_info) {
		$currentTime = date("Y-m-d H:i:s");
		$this->queryDb(
			"INSERT INTO wallet_records (user_id, wallet_id, category, sub_category, amount, description, created_at) " .
			"VALUES (:user_id, :wallet_id, :category, :sub_category, :amount, :description, :now); ",
			array(
				':user_id' => $user_id,
				':wallet_id' => $group_id,
				':category' => $category,
				':sub_category' => $sub_category,
				':amount' => $amount,
				':description' => $description,
				':now' => $currentTime
			)
		);
		$wallet_info["balance"] = $wallet_info["balance"] + $amount;
		return $this->findRecordByIdsAndTime($user_id, $wallet_id, $currentTime);
	}
	
	function saveRecord(&$wallet_record_info, $previous_amount, &$wallet_info) {
		$this->queryDb("UPDATE wallet_records " .
			"SET user_id=:user_id, wallet_id=:wallet_id, category=:category, sub_category=:sub_category, amount=:amount, description=:description ".
			"WHERE id=:id;",
			array(
				":id" => $wallet_record_info["id"],
				":user_id" => $wallet_record_info["user_id"],
				":wallet_id" => $wallet_record_info["wallet_id"],
				':category' => $wallet_record_info["category"],
				':sub_category' => $wallet_record_info["sub_category"],
				':amount' => $wallet_record_info["amount"],
				':description' => $wallet_record_info["description"]
			)
		);
		
		$wallet_info["balance"] = $wallet_info["balance"] - $previous_amount + $wallet_record_info["amount"];
		
		//TODO: update cache chunk
	}
	
	function deleteRecord($wallet_record_info, &$wallet_info) {
		$this->queryDb("DELETE FROM wallets WHERE id=? LIMIT 1;", $wallet_record_info["id"]);
		
		$wallet_info["balance"] -= $wallet_record_info["amount"];
		//TODO: update cache chunk
	}
	
}