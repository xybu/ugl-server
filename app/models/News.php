<?php
/**
 * News model abstracts the News model used in Ugl system
 * 
 * Structure:
 * 	id: the News id
 * 	user_id: the user who created the News
 *  group_id: the group associated with the News
 * 	visibility: 
 * 		0 means private, user only news
 * 		1 means friend-wide visibility
 * 		2 means group-wide visibility
 * 		63 means public to everyone
 * 	category (max length 32 chars):
 * 		group means `created by group controller or API`
 * 		user means `created by user controller or API`
 * 		wallet means `created by wallet controller or API`
 * 		board means `created by board controller or API`
 * 		etc.
 * 	description: the one-sentence description of the News. max length 384 chars
 * 	created_at: the timestamp when the News is created
 *
 * @author	Xiangyu Bu <xybu92@live.com
 * @version	0.1
 */

namespace models;

class News extends \Model{
	
	public function __construct(){
	}
	
	public function __destruct(){
	}
	
	public function findByUserId($uid, $visibility = 63){
	}
	
	public function findByGroupId($gid, $visibility = 63){
	}
	
	public function deleteByGroupId($gid, $time = time()){
		$this->queryDb("DELETE FROM groups WHERE group_id=:gid AND created_at < :time;", 
			array(":gid" => $gid, ":time" => $time));
	}
	
	public function findByVisibility($visibility){
	}
	
	public function findByCategory($category, $visiblity = 63){
	}
	
	public function findByCreationTime($time, $visibility = 63){
	}
	
	public function create($uid, $gid, $visibility, $category, $content){
	}
	
	public function delete($eid){
	}
	
	/**
	 * Newss cannot be edited.
	*/
	
	public function cleanOldNewss($t_cutoff, $criteria = array()){
	}
}