<?php

namespace controllers;

class Shelf extends \Controller {
	
	function __construct() {
		parent::__construct();
	}
	
	function api_create($base) {
		
	}
	
	function api_removeItem($base) {
		try {
			$user_status = $this->getUserStatus();
			$user = $this->user;
			$user_id = $user_status["user_id"];
			$user_info = $user_status["user_info"];
			
			
		} catch (\Exception $e) {
		}
	}
	
}
	