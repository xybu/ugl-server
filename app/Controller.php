<?php
/**
 * Controller.php
 *
 * The base controller class
 * Controllers work with models to render views
 *
 * @author	Xiangyu Bu
 * @date	Mar 03, 2014
 */

class Controller {

	// Cache is a singleton
	protected $cache;
	protected $view = null;
	protected $logger = null;
	protected $base;
	
	function __construct() {
		$this->cache = \Cache::instance();
	}
	
	function setView($filename){
		$this->view = $filename;
	}
	
	//! HTTP route pre-processor
	function beforeroute($base) {
		$this->base=$base;
	}

	//! HTTP route post-processor
	function afterroute() {
		// Render HTML layout
		//echo Template::instance()->render('layout.htm');
		if ($this->view)
			echo View::instance()->render($this->view);
	}
	
	function json_printException(Exception $e){
		$s = json_encode(
			array(
				"status" => "error",
				"error" => $e->getCode(), 
				"message" => $e->getMessage(), 
				"file" => $e->getFile() . 
				"line ". $e->getLine(), 
				"trace" => $e->getTraceAsString()
			) , JSON_PRETTY_PRINT);
		//header('HTTP/1.0 403 Forbidden');
		header("Content-Type: application/json");
		header("Cache-Control: no-cache, must-revalidate");
		header("Content-Length: " . strlen($s));
		echo $s;
		exit();
	}
	
	function json_printResponse($data, $expiration = 0){
		$s = json_encode(
			array(
				"status" => "success", 
				"expiration" => date('c', strtotime("+" . $expiration . " hour")), 
				"data" => $data
			), JSON_PRETTY_PRINT);
		header("Content-Type: application/json");
		header("Cache-Control: no-cache, must-revalidate");
		header("Content-Length: " . strlen($s));
		echo $s;
		exit();
	}
}