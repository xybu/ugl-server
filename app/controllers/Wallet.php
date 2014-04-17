<?php
/**
 * The controller for Wallet class, also handles api for records.
 */

namespace controllers;

class Wallet extends \Controller {
	
	function __construct() {
		parent::__construct();
	}
	
	function api_createWallet($base) {
		try {
			$user_status = $this->getUserStatus();
			$user = $this->user;
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			$group_id = $base->get("POST.group_id");
			
			if (!empty($group_id)) {
				if (!is_numeric($group_id)) throw new \Exception("Invalid group id", 3);
				
				$group = \models\Group::instance();
				
				$target_group = $group->findById($group_id);
				if (empty($target_group)) throw new \Exception("Group not found", 4);
				
				$user_permissions = $group->getPermissions($user_id, $group_id, $target_group);
				if (!$user_permissions["create_wallet"]) throw new \Exception("You cannot create new wallets for the group", 5);
			} else $group_id = null;
			
			$Wallet = \models\Wallet::instance();
			
			$name = $Wallet->filterTitle($base->get("POST.name"), 48);
			if (empty($name)) throw new \Exception("Wallet name is empty or contains invalid chars", 6);
			
			if ($Wallet->findByNameAndIds($name, $user_id, $group_id))
				 throw new \Exception("The wallet name has been used", 7);
			
			$description = $Wallet->filterContent($base->get("POST.description"), 150);
			
			$new_wallet = $Wallet->create($user_id, $group_id, $name, $description);
			
			if ($base->exists("POST.returnHtml")) {
				$base->set("me", $user_info);
				$base->set("wallet_item", $new_wallet);
				$new_wallet = \View::instance()->render("wallet_brief.html");
			}
			
			$this->json_printResponse(array("message" => "You have successfully created a wallet.", "wallet_data" => $new_wallet));
		
		} catch (\Exception $e) {
			$this->json_printException($e);
		}
	}
	
	function api_editWallet($base) {
		try {
			$user_status = $this->getUserStatus();
			$user = $this->user;
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
			$wallet_id = $base->get("POST.wallet_id");
			$Wallet = \models\Wallet::instance();
			
			if (empty($wallet_id) or !is_numeric($wallet_id)) throw new \Exception("Invalid wallet id", 3);
			
			$wallet_info = $Wallet->findById($wallet_id);
			if (empty($wallet_info)) throw new \Exception("Wallet not found", 4);
			
			if (!empty($wallet_info["group_id"])) {
				$Group = \models\Group::instance();
				$group_info = $Group->findById($wallet_info["group_id"]);
				if (empty($group_info)) throw new \Exception("Group not found", 5);
				
				$user_permissions = $Group->getPermissions($user_id, $wallet_info["group_id"], $group_info);
				if (!$user_permissions["edit_wallet"]) throw new \Exception("You cannot edit the group wallet", 6);
			} else if ($wallet_info["user_id"] != $user_id) throw new \Exception("You cannot edit the wallet information", 7);
			
			if ($base->exists("POST.name")) {
				$name = $Wallet->filterTitle($base->get("POST.name"), 48);
				if (empty($name)) throw new \Exception("Wallet name is empty or contains invalid chars", 7);
			
				if ($name != $wallet_info["name"] and $Wallet->findByNameAndIds($name, $user_id, $wallet_info["group_id"]))
					throw new \Exception("The wallet name has been used", 7);
				
				$wallet_info["name"] = $name;
			}
			
			if ($base->exists("POST.description")) {
				$description = $Wallet->filterContent($base->get("POST.description"), 150);
				
				$wallet_info["description"] = $description;
			}
			
			$Wallet->save($wallet_info);
			
			if ($base->exists("POST.returnHtml")) {
				$base->set("me", $user_info);
				$base->set("wallet_item", $wallet_info);
				$new_wallet = \View::instance()->render("wallet_brief.html");
			}
			
			$this->json_printResponse(array("message" => "You have successfully updated a wallet.", "wallet_data" => $wallet_info));
		
		} catch (\Exception $e) {
			$this->json_printException($e);
		}
	}
	
	function api_deleteWallet($base) {
		
	}
	
	function api_listWallet($base) {
	}
	
	function api_addRecords($base) {
		try {
			$user_status = $this->getUserStatus();
			$user = $this->user;
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			$group_id = $base->get("POST.group_id");
			
			$wallet_id = $base->get("POST.wallet_id");
			$Wallet = \models\Wallet::instance();
			
			if (empty($wallet_id) or !is_numeric($wallet_id)) throw new \Exception("Invalid wallet id", 3);
			
			$wallet_info = $Wallet->findById($wallet_id);
			if (empty($wallet_info)) throw new \Exception("Wallet not found", 4);
			
			if (!empty($wallet_info["group_id"])) {
				$Group = \models\Group::instance();
				$group_info = $Group->findById($wallet_info["group_id"]);
				if (empty($group_info)) throw new \Exception("Group not found", 5);
				
				$user_permissions = $Group->getPermissions($user_id, $wallet_info["group_id"], $group_info);
				if (!$user_permissions["create_record"]) throw new \Exception("You cannot add records to the wallet", 6);
			} else if ($wallet_info["user_id"] != $user_id) throw new \Exception("You cannot add records to the wallet", 7);
			
			var_dump($base->get("POST.item"));
			die();
			/*
			if (DateTime::createFromFormat('Y-m-d G:i:s', $myString) !== FALSE) {
  // it's a date
}
			*/
			
			$new_record = array();
			
			$this->json_printResponse(array("message" => "You have successfully added the record.", "record_data" => $new_record));
		
		} catch (\Exception $e) {
			$this->json_printException($e);
		}
	}
	
	function api_editRecord($base) {
		try {
			$user_status = $this->getUserStatus();
			$user = $this->user;
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
			
		} catch (\Exception $e) {
			$this->json_printException($e);
		}
	}
	
	function api_deleteRecord($base) {
	}
	
	function api_listRecords($base) {
		try {
			$user_status = $this->getUserStatus();
			$user = $this->user;
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
			$wallet_id = $base->get("POST.wallet_id");
			
			$Wallet = \models\Wallet::instance();
			
			if (empty($wallet_id) or !is_numeric($wallet_id)) throw new \Exception("Invalid wallet id", 3);
			
			$wallet_info = $Wallet->findById($wallet_id);
			if (empty($wallet_info)) throw new \Exception("Wallet not found", 4);
			
			if (!empty($wallet_info["group_id"])) {
				$Group = \models\Group::instance();
				$group_info = $Group->findById($wallet_info["group_id"]);
				if (empty($group_info)) throw new \Exception("Group not found", 5);
				
				$user_permissions = $Group->getPermissions($user_id, $wallet_info["group_id"], $group_info);
				if (!$user_permissions["view_wallet"]) throw new \Exception("You cannot view the group wallet", 6);
			} else if ($wallet_info["user_id"] != $user_id) throw new \Exception("You cannot view the wallet information", 7);
			
			$page = $base->get("POST.page");
			if (empty($page) or !is_numeric($page)) $page = 1;
			
			$limit = $base->get("POST.limit");
			if (empty($limit) or !is_numeric($limit)) $limit = 100;
			$limit = intval($limit);
			
			$records = $Wallet->findRecordsByWalletId($wallet_id, $page, $limit);
			
			if ($records["count"] > 0) {
				if ($base->exists("POST.returnHtml")) {
					$base->set("records", $records["records"]);
					$records_html = \View::instance()->render("wallet_record.html");
					$records["records"] = $records_html;
				}
			}
			
			$this->json_printResponse($records);
			
		} catch (\Exception $e) {
			$this->json_printException($e);
		}
	}
	
}