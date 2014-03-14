<?php
/**
 * Object model for the OAuth callback data sent from native apps.
 *
 * @author	Xiangyu Bu <xybu92@live.com>
 * @version	0.1
 */

namespace models;

class OAuthObject {
	private $data = array(
		"auth" => array(
			"provider" => "@required",
			"uid" => "@required",
			"info" => array(
				"name" => "@required",
				"email" => "@required",
				"nickname" => "",
				"first_name" => "@required",
				"last_name" => "@required",
				"location" => "",
				"description" => "",
				"image" => "",
				"phone" => "",
				"urls" => array(
					"website" => ""
				)
			),
			"credentials" => array(
				"token" => "",
				"secret" => "",
			)
		),
		"timestamp" => "@required",
		"signature" => "@required"
	);
	
	
}