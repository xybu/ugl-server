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
	
	function showHomepage($base) {
		$base->set('page_title','Unified Group Life');
		$base->set('header','header.html');
		$base->set('footer','footer.html');
		$this->setView('homepage.html');
	}
	
	function resetPassword_callBack($base){
		try {
			if (!$base->exists('GET.t'))
				throw new \Exception("Ticket not set", 1);
			
			$ticket_decrypt = API::api_decrypt(base64_decode(urldecode($base->get('GET.t'))), API::API_WIDE_KEY);
			if ($ticket_decrypt == null)
				throw new \Exception("Invalid ticket", 2);
			
			$ticket_json = json_decode($ticket_decrypt);
			
			$user = new \models\User();
			
			$email = $ticket_json->email;
			if (!$user->isValidEmail($email))
				throw new \Exception("Invalid email address", 3);
			
			$user_info = $user->findByEmail($email);
			if (!$user_info)
				throw new \Exception("Email not registered", 4);
			
			if ($ticket_json->old_pass != $user_info["password"])
				throw new \Exception("The password reset ticket has closed.", 5);
			
			$today = new \DateTime(date("c"));
			$timeDiff = $today->diff(new \DateTime($ticket_json->time));
			if ($timeDiff->h > API::RSTPWD_REQ_EXPIRATION)
				throw new \Exception("The password reset ticket has expired.", 6);
			
			$new_pass = $user->updatePassword($email);
			
			$first_name = $user_info["first_name"];
			$last_name = $user_info["last_name"];
			
			$mail = new \models\Mail();
			$mail->addTo($email, $first_name . ' ' . $last_name);
			$mail->setFrom($this->base->get("EMAIL_SENDER_ADDR"), "UGL Team");
			$mail->setSubject("Your New Password");
			$mail->setMessage("Hello " . $first_name . ' ' . $last_name . ",\n\n" .
								"Thanks for using Ugl. Your password for account \"" . $email . "\" " .
								"is set to \"" . $new_pass . "\" (without quotes). Please log in with this " .
								"password and change it to your own one.\n\n" .
								"Thanks for using our service.\n\n" .
								"Best,\nUGL Team");
			$mail->send();
			
			$base->set("rt_notification_modal", array(
				"type" => "success", 
				"title" => "Resetting your Password", 
				"message" => "An email containing your new password " .
				"has been sent to your email address. Please check.")
			);
			
		} catch (\InvalidArgumentException $e){
			if (static::ENABLE_LOG)
				$this->logger->write($e->__toString());
			throw new \Exception("Email did not send due to server error", 7);
		} catch (\RuntimeException $e){
			if (static::ENABLE_LOG)
				$this->logger->write($e->__toString());
			throw new \Exception("Email did not send due to server runtime error", 8);
		} catch (\Exception $e) {
			$base->set("rt_notification_modal", array(
				"type" => "warning", 
				"title" => "Resetting your Password", 
				"message" => $e->getMessage())
			);
		}
		
		$base->set('page_title','Unified Group Life');
		$this->setView('homepage.html');
	}
}