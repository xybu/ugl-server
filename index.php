<?php
/**
 * index.php
 *
 * The main routing engine. Requires PCRE > 7.9.
 *
 * @author	Xiangyu Bu
 * @date	Feb 25, 2014
 */

ini_set("error_log", "./data/log/php_error.log");
$f3=require('app/lib/base.php');
$f3->config('app/conf/settings.ini');
$f3->set('ONERROR', function($f3){
	echo \Template::instance()->render('error.html');
});
$f3->run();
