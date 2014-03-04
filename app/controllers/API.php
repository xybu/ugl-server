<?php
namespace controllers;

class API extends \Controller {
	
	function get_SecurityQuestions($f3) {
		$f3->set('expiration', 24);
		$f3->set('responseData', $f3->get("securityQuestions"));
		setView("response.json");
	}
	
}