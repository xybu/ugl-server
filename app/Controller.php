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
	
	function __construct() {
		$f3=Base::instance();
		
		
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
	
}
