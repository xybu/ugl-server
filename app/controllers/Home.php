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
	
	function resetPassword_callBack($f3){
		try {
			if (!$f3->exists('PARAMS.ticket'))
				throw new \Exception("Ticket not set", 1);
			
			$ticket_decrypt = API::api_decrypt($f3->get('PARAMS.ticket'), API::API_WIDE_KEY);
			if ($ticket_decrypt == null)
				throw new \Exception("Invalid ticket", 2);
			
			$ticket_json = json_decode($ticket_decrypt);
			
			//TODO: finish the rest
			
		} catch (\Exception $e) {
			
		}
	}
}