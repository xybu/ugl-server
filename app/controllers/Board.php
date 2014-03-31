<?php
/**
 * The controller for Board class, also handles api for posts.
 */

namespace controllers;

class Board extends \Controller {
	
	const MAX_BOARD_DESC_LEN = 70;
	
	function __construct() {
		parent::__construct();
	}
	
	function api_listByUser($base, $args) {
	}
	
	function api_listByGroup($base, $args) {
	}
	
	/**
	 * Create a Board
	 * Required:	user login, board title, description, new_board permission if group_id exists
	 * Optional:	group_id
	 */
	function api_create($base) {
		try {
			$user_status = $this->getUserStatus();
			$user = $this->user;
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			$group_id = $base->get("POST.group_id");
			
			if (!empty($group_id)) {
				//throw new \Exception("Group id not specified", 3);
				if (!is_numeric($group_id)) throw new \Exception("Invalid group id", 3);
				
				$group = \models\Group::instance();
				
				$target_group = $group->findById($group_id);
				if (empty($target_group)) throw new \Exception("Group not found", 4);
				
				$user_permissions = $group->getPermissions($user_id, $group_id, $target_group);
				
				if (!$user_permissions["new_board"]) throw new \Exception("You are not allowed to create boards for the group", 5);
			} else $group_id = null;
			
			$Board = \models\Board::instance();
			
			$board_title = $Board->filterTitle($base->get("POST.title"));
			if (empty($board_title)) throw new \Exception("Board title is empty or contains invalid chars", 6);
			if ($Board->findByTitleAndIds($board_title, $user_id, $group_id))
				 throw new \Exception("Board title has been used in the specified visibility scope", 7);
			
			$board_description = $Board->filterContent($base->get("POST.description"), static::MAX_BOARD_DESC_LEN);
			
			$new_board = $Board->create($user_id, $group_id, $board_title, $board_description);
			$new_board["discussion_list"] = array();
			
			if ($base->exists("POST.returnHtml")) {
				$base->set("me", $user_info);
				$base->set("board_item", $new_board);
				$new_board = \View::instance()->render("board.html");
			}
			
			$this->json_printResponse(array("message" => "You have successfully created a board.", "board_data" => $new_board));
		
		} catch (\Exception $e) {
			$this->json_printException($e);
		}
	}
	
	/**
	 * Delete a Board
	 * Required:	user login, board id, delete board permission if group_id exists
	 * Optional:	group_id
	 */
	function api_delete($base) {
		
	}
	
	function api_edit($base) {
		
	}
	
	function api_addPost($base) {
		
	}
	
	function api_editPost($base) {
		
	}
	
	function api_delPost($base) {
		
	}
	
}