<?php
namespace controllers;

class User extends \Controller {
	
	protected $session;
	protected $user;
	protected $auth;
	protected $isUser = false;
	
	function __construct() {
		parent::__construct();
		//$this->session = new \Session();
		
		//$this->user = new \DB\SQL\Mapper($db, 'Users');
		//$this->auth = new \Auth($user, array('id'=>'userId', 'pw'=>'userPass_hash'));
		
		//$this->isUser = $this->auth->login('admin','secret_pwd');
	}
	
	/**
	 * oauth_getProviderCreds()
	 * 
	 * HybridAuth config implementation
	 */
	function oauth_getProviderCreds(){
		return array(
			"base_url" => "http://localhost/oauth/end", 
			"providers" => array ( 
				"OpenID" => array ("enabled" => true),
				"AOL"  => array ("enabled" => true),
				"Yahoo" => array ("enabled" => false, "keys" => array ( "id" => "", "secret" => "" )),
				"Google" => array (
					"enabled" => true,
					"keys" => array ( "id" => "1066850527889.apps.googleusercontent.com", "secret" => "1066850527889@developer.gserviceaccount.com" )
				),
				"Facebook" => array (
					"enabled" => true,
					"keys" => array ( "id" => "1376194325978420", "secret" => "e4d275c92c9f73fa4924e15deee55d23" )
				),
				"Twitter" => array ( 
					"enabled" => false,
					"keys"    => array ( "key" => "", "secret" => "" ) 
				),
				"Live" => array (
					"enabled" => true,
					"keys"    => array ( "id" => "0000000048114882", "secret" => "vhfhYtlwTUW1KkJdnKHEOSSRlqcjOm7T" ) 
				),
				"MySpace" => array ( 
					"enabled" => false,
					"keys"    => array ( "key" => "", "secret" => "" ) 
				),
				"LinkedIn" => array (
					"enabled" => false,
					"keys"    => array ( "key" => "", "secret" => "" ) 
				),
				"Foursquare" => array (
					"enabled" => false,
					"keys"    => array ( "id" => "", "secret" => "" ) 
				),
			),
			"debug_mode" => false,
			"debug_file" => ""
		);
	}
	
	function oauth_connectProvider($f3) {
		$provider = $f3->get('PARAMS.provider');
		
		try {
			//require_once realpath( dirname( __FILE__ ) )  . "/../hybrid/Auth.php";
			
			// create a hybridauth instance
			$hybridauth = new \hybrid\Hybrid_Auth($this->oauth_getProviderCreds());
			
			// try to authenticate the selected $provider
			$adapter = $hybridauth->authenticate($provider);
			$user_profile = $adapter->getUserProfile();
			
			// load user and authentication models, we will need them...
			$authentication = new \models\Authentication();
			$user = new \models\User();
			
			# 1 - check if user already have authenticated using this provider before
			$authentication_info = $authentication->findByProviderUid($provider, $user_profile->identifier);
			
			// if authentication already exists, reroute to dashboard
			if ($authentication_info){
				$f3->set("SESSION.user", $authentication_info["user_id"]);
				$f3->reroute("/user/dashboard");
			}
			
			$provider_uid  = $user_profile->identifier;
			$email         = $user_profile->email;
			$first_name    = $user_profile->firstName;
			$last_name     = $user_profile->lastName;
			$display_name  = $user_profile->displayName;
			$website_url   = $user_profile->webSiteURL;
			$profile_url   = $user_profile->profileURL;
			
			if ($email){
				$user_info = $user->findByEmail($email);
				$user_id = null;
				//var_dump($user_info);
				if ($user_info) {
					// the user registered the email, but hasn't assoc with his account
					$user_id = $user_info["id"];
					$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $profile_url, $website_url);
				} else {
					// the user hasn't registered the email
					$password = rand();
					$user_id = $user->createUser($email, $password, $first_name, $last_name);
					$authentication->createAuth($user_id, $provider, $provider_uid, $email, $display_name, $first_name, $last_name, $profile_url, $website_url);
					
					//TODO: can send an email later
				}
				
				$f3->set("SESSION.user", $user_id);
				$f3->reroute("/user/dashboard");
			} else
				throw new Exception("No email specified.", 101);
			
		} catch( Exception $e ) {
			// Display the recived error
			switch( $e->getCode() ){ 
				case 0 : $error = "Unspecified error."; break;
				case 1 : $error = "Hybriauth configuration error."; break;
				case 2 : $error = "Provider not properly configured."; break;
				case 3 : $error = "Unknown or disabled provider."; break;
				case 4 : $error = "Missing provider application credentials."; break;
				case 5 : $error = "Authentication failed. The user has canceled the authentication or the provider refused the connection."; break;
				case 6 : $error = "User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again."; 
					     $adapter->logout(); 
					     break;
				case 7 : $error = "User not connected to the provider."; 
					     $adapter->logout(); 
					     break;
				case 101 : $error = "User did not share email address."; break;
			} 
			
			// well, basically your should not display this to the end user, just give him a hint and move on..
			$error .= "<br /><br /><b>Original error message:</b> " . $e->getMessage(); 
			$error .= "<hr /><pre>Trace:<br />" . $e->getTraceAsString() . "</pre>"; 

			// load error view
			$f3->set('responseData', array("error" => $error));
			$this->setView("error.json");
		}
	}
	
	/**
	 * oauth_endPoint
	 *
	 * HybridAuth endpoint implementation
	 * action = {get | hauth_start | hauth_done}
	 */
	function oauth_endPoint($f3){
		// hauth.done=Google
		
		// HybridAuth is able to handle invalid action or params
		
		$request = array(
			"get" => $f3->get('GET.get'),
			"hauth_start" => $f3->get('GET.hauth_start'),
			"hauth_done" => $f3->get('GET.hauth_done'),
			"hauth_time" => $f3->get('GET.hauth_time')
		);
		var_dump($request);
		echo "Query string:" . $f3->get('SERVER.QUERY_STRING') . "<br>";
		var_dump($_REQUEST);
		var_dump($_POST);
		
		\hybrid\Hybrid_Endpoint::process($request);
	}
	
	function showDashboard($f3) {
		$f3->set('page_title','Unified Group Life Demo');
		$f3->set('header','header.html');
		$f3->set('footer','footer.html');
		$this->setView("usercp.html");
	}
	
	function login($f3){
	}
	
	function logout($f3){
	}
	
	function register($f3){
	}
}