<?php
/**
 * Controller.php
 *
 * The base controller class
 * Controllers work with models to render views
 *
 * @author	Xiangyu Bu
 * @date	Feb 25, 2014
 */

class Controller {

	// Cache is a singleton
	protected $cache;
	protected $view = null;
	protected $f3;
	
	function __construct() {
		$this->f3=Base::instance();
		$this->cache = \Cache::instance();
	}
	
	function setView($filename){
		$this->view = $filename;
	}
	
	//! HTTP route pre-processor
	function beforeroute($f3) {
		//$db=$this->db;
		// Prepare user menu
		//$f3->set('menu',
		//	$db->exec('SELECT slug,title FROM pages ORDER BY position;'));
	}

	//! HTTP route post-processor
	function afterroute() {
		// Render HTML layout
		//echo Template::instance()->render('layout.htm');
		if ($this->view)
			echo View::instance()->render($this->view);
	}
	
	function renderJsonException(Exception $e){
		$f3->set('responseData', array(
					"error" => $e->getCode(), 
					"message" => $e->getMessage(), 
					"file" => $e->getFile() . 
					"line ". $e->getLine(), 
					"trace" => $e->getTraceAsString())
		);
		echo View::instance()->render("error.json");
		die();
	}
}