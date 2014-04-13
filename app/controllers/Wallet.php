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
			$new_wallet["records"]["count"] = 0;
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
			
			if (!empty($group_id)) {
				if (!is_numeric($group_id)) throw new \Exception("Invalid group id", 3);
				
				$group = \models\Group::instance();
				
				$target_group = $group->findById($group_id);
				if (empty($target_group)) throw new \Exception("Group not found", 4);
				
				$user_permissions = $group->getPermissions($user_id, $group_id, $target_group);
				if (!$user_permissions["create_record"]) throw new \Exception("You cannot create new records for the group", 5);
			} else $group_id = null;
			
			$Wallet = \models\Wallet::instance();
			
			$name = $Wallet->filterTitle($base->get("POST.name"), 48);
			if (empty($name)) throw new \Exception("Wallet name is empty or contains invalid chars", 6);
			
			if ($Wallet->findByNameAndIds($name, $user_id, $group_id))
				 throw new \Exception("The wallet name has been used", 7);
			
			$description = $Wallet->filterContent($base->get("POST.description"), 150);
			
			$new_wallet = $Wallet->create($user_id, $group_id, $name, $description);
			$new_wallet["records"]["count"] = 0;
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
	}
	
}