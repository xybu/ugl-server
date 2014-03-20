<?php
/**
 * File upload handler model
 */
namespace models;

class Upload extends \Model {
	
	static $ACCEPTED_IMAGE_MIMES = array(
		"image/jpeg",
		"image/png",
		"image/gif"
	);
	
	static $MAX_FILE_SIZE = 102400; // 100 KiB
	static $MAX_AVATAR_FILE_SIZE = 102400; // 100 KiB
	
	private $web = null;
	
	function __construct() {
		parent::__construct();
		$this->web = \Web::instance();
		
	}
	
	/**
	 * save each upload file and rename them to the corresponding element in names
	 *
	 * @param	path	path relative to index.php
	 * @param	names	array(0 => "user_1_avatar.gif", 1 => "user_3_.gif")), etc.
	 */
	function uploadImages($path = "assets/upload/", $names = array(), $max_width = 150, $max_height = 150) {
		$overwrite = true;
		$slug = true;
		$files = $this->web->receive(
			function($file){
				//var_dump($file);
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
				
				if (!in_array($file["type"], self::$ACCEPTED_IMAGE_MIMES))
					return false;
				
				return true; // move the file from php_tmp to app_tmp
			},
			$overwrite,
			$slug
		);
		
		$names_left = count($names);
		$i = 0;
		foreach ($files as $name => $status)
			if ($status){
				if ($i < $names_left){
					try {
						$img = new \Image($name);
						$img->resize($max_width, $max_height, true, false); // crop; no enlarge
						imagepng(imagecreatefromstring($img->dump()), realpath(dirname($name) . "/../../" . $path) . "/" . $names[i], 9,  PNG_ALL_FILTERS);
						++$i;
					} catch (\Exception $e){
						//ignore exceptions
					}
				}
				unlink($name); // delete the temp upload file
			}
		return $i;
	}
}