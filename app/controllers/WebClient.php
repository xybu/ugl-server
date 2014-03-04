<?php
namespace controllers;

class WebClient extends \Controller {
	
	function showHomepage($f3) {
		$f3->set('page_title','Unified Group Life Demo');
		$f3->set('header','header.html');
		$f3->set('footer','footer.html');
		$this->setView('homepage.html');
	}
	
}