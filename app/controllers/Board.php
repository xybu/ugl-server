<?php
namespace controllers;

class Board extends \Controller {
	
	function __construct() {
		parent::__construct();
	}
	
	function api_listByUser($base, $args) {
	}
	
	function api_listByGroup($base, $args) {
	}
	
	/**
	 * Create a Board
	 * Required:	user login, board title, description, visibility
	 * Optional:	group_id
	 */
	function api_create($base) {
		
	}
	
	/**
	 * Delete a Board
	 * Required:	user login, board id, delete board permission if group id exists
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
	
	