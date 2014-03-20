<?php

namespace controllers;

class File extends \Controller {
	
	protected $web;
	
	static $ACCEPTED_MIME_LIST = array(
		"image/jpeg",
		"image/png",
		"image/gif"
	);
	
	static $MAX_FILE_SIZE = 204800; //200 KiB
	
	function __construct() {
		parent::__construct();
		$this->web = \Web::instance();
		
	}
	
	function upload($base){
		$overwrite = true;
		$slug = true;
		
		echo "start...\n";
		
		$files = $this->web->receive(
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
				if ($file['size'] > (self::$MAX_FILE_SIZE)) // if bigger than 2 MB
					return false; // this file is not valid, return false will skip moving it
				
				if (!in_array($file["type"], self::$ACCEPTED_MIME_LIST))
					return false;
				
				// everything went fine, hurray!
				return true; // allows the file to be moved from php tmp dir to your defined upload dir
			},
			$overwrite,
			$slug
		);
		
		//$num_of_files = 0;
		
		var_dump($files);
		
		foreach ($files as $name => $status){
			try {
				echo dirname($name) . "<br>";
				//if ($num_of_files == 0){
				$img = new \Image($name);
				$img->resize(150, 150, true, false);
			
				echo realpath(dirname($name) . "/../../assets/upload");
				imagepng(imagecreatefromstring($img->dump()), realpath(dirname($name) . "/../../assets/upload") . "/avatar.png", 9,  PNG_ALL_FILTERS);
			} catch (\Exception $e){
				//ignore exceptions
				
			}
				//}
			unlink($name);
			//++$num_of_files;
		}
	}
	
	function sampleUploadForm($base){
		$this->setView("upload.html");
	}
}