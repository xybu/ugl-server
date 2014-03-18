<?php
/**
 * index.php
 *
 * The main routing engine. Requires PCRE > 7.9.
 *
 * @author	Xiangyu Bu
 * @version	0.3
 */

$base = require('app/lib/base.php');

$base->config('app/conf/settings.ini');
/*$base->set('ONERROR', function($base){
	echo \Template::instance()->render('error.html');
});
*/
$base->run();
