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
			$group = new \models\Group();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$token = $user_status["ugl_token"];
			$target_user_id = $user_id;
			$target_status = $group::STATUS_INACTIVE;
			
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
					
					$target_status = $group::STATUS_PUBLIC;
				}
			}
			
			$group_list = $group->listGroupsOfUserId($target_user_id, $target_status);
			
			if ($group_list["count"] > 0)
				foreach ($group_list["groups"] as $key => $val)
					$group->removePrivateKeys($group_list["groups"][$key]);
			
			$this->json_printResponse($group_list);
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	/**
	 * api_getInfo should be an app-exclusive API
	 */
	function api_getInfo($base){
		try {
			$user = new \models\User();
			if ($base->exists("COOKIE.ugl_user")){
				$user_status = API::getUserStatus($base, $user);
				$user_id = $user_status["user_id"];
			} else {
				$user_id = -1;
			}
			
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
			
			if (!$user_permissions["view_profile"])
				throw new \Exception("You cannot access the group", 6);
			
			if (!$user_permissions["manage"])
				$group->removePrivateKeys($target_group);
			else {
				$new_users = array();
							
				foreach ($group_info["users"] as $role => $ids){
					foreach ($ids as $key => $val)
						$new_users[$role][] = $user->filterOutPrivateKeys($user->findById($val));
				}
				
				$target_group["users"] = $new_users;
			}
			
			$this->json_printResponse(array("my_permissions" => $user_permissions, "group_data" => $target_group));
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_find($base) {
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
			$keyword = $user->filterHtmlChars($base->get("POST.keyword"));
			if (empty($keyword)) throw new \Exception("Empty keyword", 3);
			
			$group = new \models\Group();
			$search_result = $group->findByKeyword($keyword);
			
			if ($search_result["count"] == 0)
				throw new \Exception("No group match the given keyword", 4);
			
			foreach ($search_result["groups"] as $k => $group_info) {
				$user_permissions = $group->getPermissions($user_id, $group_info["id"], $group_info);
				
				if (!$user_permissions["view_profile"]){
					$search_result["count"] -= 1;
					unset($search_result["groups"][$k]);
				}
				
				$group->removePrivateKeys($search_result["groups"][$k]);
			}
			
			if ($search_result["count"] == 0)
				throw new \Exception("No group match the given keyword", 4);
			
			$this->json_printResponse(array("count" => $search_result["count"], "groups" => $search_result["groups"]));
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_create($base) {
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
			$group = new \models\Group();
			
			$group_name = $base->get("POST.alias");
			if (!$group->isValidAlias($group_name)) throw new \Exception("Group name is not of the specified format. Plese check", 3);
			if ($group->findByAlias($group_name)) throw new \Exception("Group name \"" . $group_name . "\" is already taken", 4);
			
			if ($base->exists("POST.description"))
				$group_description = $group->filterDescription($base->get("POST.description"));
			else $group_description = "";
			
			if ($base->exists("POST.tags"))
				$group_tags = $group->filterTags($base->get("POST.tags"));
			else $group_tags = "";
			
			$group_status = $base->get("POST.status");
			if (!$group->isValidStatus($group_status))
				 throw new \Exception("Please choose a valid status option from the list", 5);
			
			$group_data = $group->create($user_id, $group_name, $group_description, $group_tags, $group_status);
			
			$user->joinGroup($user_info, $group_data["id"]);
			$user->save($user_info);
			
			$this->json_printResponse(array("message" => "Successfully created a new group", "group_data" => $group_data));
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_leave($base){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
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
					
					$group->delete($target_group, $user);
					$this->json_printResponse(array("message" => "The group is now closed"));
				}
				
				if (!empty($target_user_id)){
					$kick_list = explode(",", $target_user_id);
					
					foreach ($kick_list as $id){
						if (!is_numeric($id) or $target_group["creator_user_id"] == $id)
							continue;
						// whether the user exists or not does not matter.
						$group->kickUser($id, $group_data);
						
						$target_user_info = $user->findById($id);
						if ($target_user_info) {
							$user->leaveGroup($target_user_info, $target_gid);
							$user->save($target_user_info);
						}
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
			$user->leaveGroup($user_info, $target_gid);
			$user->save($user_info);
			$group->save($group_data);
			$this->json_printResponse(array("message" => "You have successfully left the group \"" . $target_group["alias"] . "\""));
		} catch (\Exception $e) {
			$this->json_printException($e);
		}
	}
	
	function api_edit($base){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			
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
			
			if (!$user_permissions["manage"])
				throw new \Exception("Unauthorized request", 6);
			
			$group_name = $base->get("POST.alias");
			if (!$group->isValidAlias($group_name)) throw new \Exception("Group name is not of the specified format. Plese check", 7);
			
			if ($base->exists("POST.description"))
				$group_description = $group->filterDescription($base->get("POST.description"));
			else $group_description = $target_group["description"];
			
			if ($base->exists("POST.tags"))
				$group_tags = $group->filterTags($base->get("POST.tags"));
			else $group_tags = $target_group["tags"];
			
			$group_status = $base->get("POST.status");
			if (!$group->isValidStatus($group_status))
				 throw new \Exception("Please choose a valid status option from the list", 8);
			
			$group->update($target_group, $group_name, $group_description, $group_tags, $group_status);
			
			$this->json_printResponse(array("message" => "You have successfully updated group profile.", "group_data" => $target_group));
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_changeCreator($base){
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
	}
	
	function api_changeUsers($base){
		
	}
	
	function api_invite($base){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
			if (!$base->exists("POST.group_id"))
				throw new \Exception("Group id not specified", 3);
			
			$group_id = $base->get("POST.group_id");
			if (!is_numeric($group_id))
				throw new \Exception("Invalid group id", 4);
			
			$group = new \models\Group();
			
			$group_info = $group->findById($group_id);
			if (empty($group_info))
				throw new \Exception("Group not found", 5);
			
			$user_permissions = $group->getPermissions($user_id, $group_id, $group_info);
			
			if (!$user_permissions["manage"])
				throw new \Exception("Unauthorized request", 6);
			
			if (!$base->exists("POST.invite"))
				throw new \Exception("Invitation list is not specified", 7);
			
			$invite_list = explode(",", $base->get("POST.invite"));
			$send_list = array();
			$skip_list = array();
			
			foreach ($invite_list as $i => $email) {
				if ($group->isValidEmail($email)) {
					$invitee_info = $user->findByEmail($email);
					if (!empty($invitee_info)) {
						// the user has registered, check preference
						$in_group = $user->isInGroup($invitee_info, $group_id);
						
						if ($in_group > -1) {
							// if already in the group, do nothing
							$skip_list[] = $email . " (already a member)";
							continue;
						} else if ($group->getRoleOf($group_info, $invitee_info["id"]) != "guest") {
							$skip_list[] = $email . " (already in group member list)";
						} else if ($invitee_info["_preferences"]["autoAcceptInvitation"]) {
							// if accept automatically, add to group member
							$group->addUser($invitee_info["id"], "member", $group_info);
							$user->joinGroup($invitee_info, $group_id);
							continue;
						} else {
							// add to invitee role and send email
							$group->addUser($invitee_info["id"], "invitee", $group_info);
						}
					}
					
					$send_list[] = $email;
					
				} else $skip_list[] = $email . " (invalid address)";
			}
			
			$group->save($group_info);
			
			if (count($send_list) > 0) {
				
				$base->set("user_info", $user_info);
				$base->set("group_info", $group_info);
				
				$ticket = array(
					"group_id" => $group_info["id"],
					"inviter_id" => $user_info["id"],
					"ticket_time" => date("c"),
				);
				
				$ticket_str = urlencode(base64_encode(API::api_encrypt(json_encode($ticket), API::API_WIDE_KEY)));
				$base->set("ticket_str", $ticket_str);
				$base->set("public_group_url", $base->get("APP_URL") . "/group/" . $group_info["id"]);
				//TODO: finish things above
				
				$message = \Template::instance()->render('email_groupInvitation.html');
				
				$mail = new \models\Mail();
				foreach ($send_list as $email) {
					$mail->addTo($email, "");
				}
				$mail->setFrom($this->base->get("SMTP_FROM"), "UGL Team");
				$mail->setSubject("Group Invitation from " . $user_info["first_name"] . " " . $user_info["last_name"]);
				$mail->setMessage($message);
				$mail->send();
				
			}
			
			$this->json_printResponse(array("message" => "Invitation sent to " . implode(", ", $send_list) . ".", "skipped" => $skip_list));
		} catch (\InvalidArgumentException $e){
			throw new \Exception("Email did not send due to server error", 8);
		} catch (\RuntimeException $e){
			throw new \Exception("Email did not send due to server runtime error", 9);
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_apply($base){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
			if (!$base->exists("POST.group_id"))
				throw new \Exception("Group id not specified", 3);
			
			$group_id = $base->get("POST.group_id");
			if (!is_numeric($group_id))
				throw new \Exception("Invalid group id", 4);
			
			$group = new \models\Group();
			
			$target_group = $group->findById($group_id);
			if (empty($target_group))
				throw new \Exception("Group not found", 5);
			
			$user_permissions = $group->getPermissions($user_id, $group_id, $target_group);
			
			if ($group->hasUser($target_group, $user_id))
				throw new \Exception("You already applied to the group or are already a member", 6);
			
			if (!$user_permissions["apply"])
				throw new \Exception("You cannot apply to join the group", 7);
			
			if ($target_group["_preferences"]["autoApproveApplication"]) {
				$group->addUser($user_id, "member", $target_group);
				$message = "You have joined the group";
			} else {
				$group->addUser($user_id, "pending", $target_group);
				$message = "You have applied to the group";
				//TODO: send an email
			}
			$group->save($target_group);
			$this->json_printResponse(array("message" => $message));
			
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function api_uploadAvatar($base){
		try {
			$user = new \models\User();
			$user_status = API::getUserStatus($base, $user);
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
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
			
			if (!$user_permissions["manage"])
				throw new \Exception("Unauthorized request", 6);
			
			$upload = new \models\Upload();
			$numOfFiles = $upload->uploadImages($base->get("UPLOAD_AVATARS_DIR"), array("group_" . $target_gid . ".png"));
			
			if ($numOfFiles == 1) {
				$new_avatar_url = $base->get("UPLOAD_AVATARS_DIR") . "group_" . $target_gid . ".png";
				$target_group["avatar_url"] = $new_avatar_url;
				$group->save($target_group);
				$this->json_printResponse(array("avatar_url" => $new_avatar_url));
			} else if ($numOfFiles == 0)
				throw new \Exception("File upload failed. Please check if the file is an image of JPEG, PNG, or GIF format with size no more than 100KiB.", 3);
				// should use $upload->MAX_AVATAR_FILE_SIZE as max file size
			else trigger_error("Uploaded more than one file: " . $numOfFiles, E_USER_ERROR);
		} catch (\Exception $e){
			$this->json_printException($e);
		}
	}
	
	function html_showGroupPage($base){
		die();
	}
	
}