<?php
/**
 * Home.php
 * The home controller
 * 
 * @author	Xiangyu Bu
 * @date	Mar 08, 2014
 */

namespace controllers;

class Home extends \Controller {
	
	function showHomepage($f3) {
		$f3->set('page_title','Unified Group Life');
		$f3->set('header','header.html');
		$f3->set('footer','footer.html');
		$this->setView('homepage.html');
	}
	
	function showForgetPassword($f3){
		
	}
}