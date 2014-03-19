<?php
/**
 * Group.php
 * The group controller
 *
 * @author	Xiangyu Bu
 * @date	Mar 10, 2014
 */

namespace controllers;

class Group extends \Controller {
	
	function __construct() {
		parent::__construct();
	}
	
	function __destruct() {
	}
	
	function api_listByUserId($base){
		try {
			
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$token = $user_status["ugl_token"];
			$target_user_id = $user_id;
			$target_visibility = 0;
			
			if ($base->exists("PARAMS.user_id")){
				$target_user_id = $base->get("PARAMS.user_id");
				
				if ($target_user_id == "me")
					$target_user_id = $user_id;
				
				if (!is_numeric($target_user_id))
					throw new \Exception("User id should be a number", 3);
				
				if ($target_user_id != $user_id){
					$target_user = $user->findById($target_user_id);
					if (!$target_user)
						throw new \Exception("The user does not exist", 4);
					
					//if (USER does not allow to request his list){
					//	throw new \Exception("The user did not allow you to view his or her group list.", 5);
					//}
					
					$target_visibility = 1;
				}
			}
			
			$group = new \models\Group();
			$group_list = $group->listGroupsOfUserId($target_user_id, $target_visibility);
			
			$this->json_printResponse($group_list);
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_create($base){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$token = $user_status["ugl_token"];
			
			$group = new \models\Group();
			
			$group_name = $base->get("POST.alias");
			if (!$group->isValidAlias($group_name)) throw new \Exception("Group name is not of the specified format. Plese check.", 3);
			if ($group->findByAlias($group_name)) throw new \Exception("Group name \"" . $group_name . "\" is already taken.", 4);
			
			if ($base->exists("POST.description"))
				$group_description = $group->filterDescription($base->get("POST.description"));
			else $group_description = "";
			
			if ($base->exists("POST.tags"))
				$group_tags = $group->filterTags($base->get("POST.tags"));
			else $group_tags = "";
			
			$group_visibility = $base->get("POST.visibility");
			if (!$group->isValidVisibility($group_visibility))
				 throw new \Exception("Please choose a valid visibility option from the list", 5);
			
			$group_data = $group->create($user_id, $group_name, $group_description, $group_tags, $group_visibility);
			
			//if ($group->isPubliclyVisible($visibility)){
			//	$news = new \models\News();
			//	$news->create($user_id, $group_data["id"], $news::VIS_PUBLIC, "group", $description);
			//}
			
			$this->json_printResponse(array("message" => "Successfully created a new group", "group_data" => $group_data));
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_delete($base){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$token = $user_status["ugl_token"];
			
			if (!$base->exists("POST.group_id"))
				throw new \Exception("Group id not specified", 3);
			
			$target_gid = $base->get("POST.group_id");
			if (!is_numeric($target_gid))
				throw new \Exception("Invalid group id", 4);
			
			$group = new \models\Group();
			
			$target_group = $group->findById($target_gid);
			if (empty($target_group))
				throw new \Exception("Group not found", 5);
			
			if ($target_group["creator_user_id"] != $user_id)
				throw new \Exception("Only the creator can delete the group", 6);
			
			if ($base->exists("POST.notify")){
				//TODO: notify all members
			}
			
			$group->deleteById($target_gid);
			
			$this->json_printResponse(array("message" => "Successfully deleted group \"" . $target_group["alias"] . "\""));
		} catch (\Exception $e) {
			$this->json_printException($e);
		}
	}
	
	function api_leave($base){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$token = $user_status["ugl_token"];
			
			if (!$base->exists("POST.group_id"))
				throw new \Exception("Group id not specified", 3);
			
			$target_gid = $base->get("POST.group_id");
			if (!is_numeric($target_gid))
				throw new \Exception("Invalid group id", 4);
			
			$group = new \models\Group();
			
			$target_group = $group->findById($target_gid);
			if (empty($target_group))
				throw new \Exception("Group not found", 5);
			
			$user_permissions = $group->getPermissions($user_id, $target_gid, $target_group);
			
			if ($user_permissions["role_name"] == "guest")
				throw new \Exception("You are not in the group", 6);
			
			// if the user can manage, then there may be a target user
			if ($user_permissions["manage"]){
				
				// get the target user, if not specified, then he means to leave
				$target_user_id = $base->get("POST.target_user_id");
				
				if ($target_user_id == $target_group["creator_user_id"] and $user_id != $target_user_id)
					// someone else wants to kick the creator
					throw new \Exception("You cannot kick the creator", 7);
				
				if ($user_id == $target_group["creator_user_id"] and (empty($target_user_id) or $user_id == $target_user_id)){
					// the creator kicks himself, delete the group
					
					if ($base->exists("POST.notify")){
						//TODO: notify all members
						
					}
					
					$group->deleteById($target_gid);
					$this->json_printResponse(array("message" => "The group is now closed"));
				}
				
				if (!empty($target_user_id)){
					$kick_list = explode(",", $target_user_id);
					
					foreach ($kick_list as $id){
						if (!is_numeric($id) or $target_group["creator_user_id"] == $id)
							continue;
						// whether the user exists or not does not matter.
						$group->kickUser($id, $group_data);
					}
					$group->save($group_data);
					$this->json_printResponse(array("message" => "You have successfully kicked the user"));
				}
			}
			
			if ($user_id == $target_group["creator_user_id"])
				// this condition happens when the creator does not have manage permission
				// which is a wrong setting
				throw new \Exception("You are the creator. Please grant yourself \"manage\" permission before leaving the group", 9);
			
			// the requestor leaves (aka., kick himself)
			$group->kickUser($user_id, $group_data);
			$group->save($group_data);
			$this->json_printResponse(array("message" => "You have successfully left the group \"" . $target_group["alias"] . "\""));
		} catch (\Exception $e) {
			$this->json_printException($e);
		}
	}
	
	function api_update($base){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$token = $user_status["ugl_token"];
			
			if (!$base->exists("POST.group_id"))
				throw new \Exception("Group id not specified", 3);
			
			$target_gid = $base->get("POST.group_id");
			if (!is_numeric($target_gid))
				throw new \Exception("Invalid group id", 4);
			
			$group = new \models\Group();
			
			$group_info = $group->findById($target_gid);
			if (empty($group_info))
				throw new \Exception("Group not found", 5);
			
			// to change the founder of the group
			if ($base->exists("POST.new_creator_user_id")){
				if ($group_info["creator_user_id"] != $user_id)
					throw new \Exception("Only the creator can transfer ownership", 6);
				
				$new_creator = $base->get("POST.new_creator_user_id");
				if (!is_numeric($new_creator))
					throw new \Exception("User id must be a number", 7);
				else if ($new_creator != $user_id){
					if (!$user->findById($new_creator))
						throw new \Exception("The specified new creator does not exist", 8);
					
					$group->changeCreatorUserId($target_gid, $new_creator);
				}
				
				// if the old and new creators are the same, skip this step
			}
			
			//TODO: to be finished
			
		} catch (\Exception $e){
			
		}
	}
	
	function html_showGroupPage($base){
		
		$user = new \models\User();
		$me = $base->get("SESSION.user");
		$panel = $base->get("PARAMS.panel");
		
		if (!$user->verifyToken($me["id"], $me["ugl_token"])){
			$me["id"] = -1;
			$my_profile = null;
		} else {
			$my_profile = $user->getUserProfile($me["id"]);
		}
		
		$item_id = $base->get("PARAMS.group_id");
		if (!is_numeric($item_id))
			throw new \Exception("Group id should be a number", 3);
		
		$group = new \models\Group();
		$group_info = $group->findById($item_id);
		
		if (!$group_info)
			throw new \Exception("Group not found", 4);
		
		$my_permissions = $group->getPermissions($me["id"], $item_id, $group_info);
		
		if (!$my_permissions["view_profile"])
			throw new \Exception("You are not allowed to view the profile of this group", 5);
		$base->set("my_permissions", $my_permissions);
		$base->set("group_info", $group_info);
		$sub_panel = "group";
		
		$base->set('page_title','Unified Group Life');
		$base->set('group_header', true);
		$base->set('group_footer', true);
		$this->setView('group.html');
	}
	
}