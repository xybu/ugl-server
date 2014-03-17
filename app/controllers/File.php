<?php

namespace controllers;

class File extends \Controller {
	
	protected $web;
	
	function __construct() {
		parent::__construct();
		$this->web = \Web::instance();
		
	}
	
	function upload(){
		$overwrite = true;
		$slug = $true;
		
		$files = $web->receive(
			function($file){
				var_dump($file);
				/* looks like:
				array(5) {
					["name"] =>     string(19) "csshat_quittung.png"
					["type"] =>     string(9) "image/png"
					["tmp_name"] => string(14) "/tmp/php2YS85Q"
					["error"] =>    int(0)
					["size"] =>     int(172245)
					}
				*/
				// $file['name'] already contains the slugged name now
		
				// maybe you want to check the file size
				if($file['size'] > (2 * 1024 * 1024)) // if bigger than 2 MB
					return false; // this file is not valid, return false will skip moving it
		
				// everything went fine, hurray!
				return true; // allows the file to be moved from php tmp dir to your defined upload dir
			},
			$overwrite,
			$slug
		);
	}
}