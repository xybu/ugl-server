<?php
namespace controllers;

class Home extends \Controller {
	
	function showHomepage($f3) {
		$f3->set('page_title','Unified Group Life');
		$f3->set('header','header.html');
		$f3->set('footer','footer.html');
		$this->setView('homepage.html');
	}
	
}