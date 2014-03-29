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

$base->route('GET /minify/@type', 
    function($f3, $args) {
        $f3->set('UI', "assets/".$args['type'].'/'); 
        echo Web::instance()->minify($_GET['files']);
    }, 3600*24 
);

$base->run();
