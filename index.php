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

$base->config('app/conf/globals.ini');
$base->config('app/conf/routes.ini');
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

$base->route('GET /test', 
    function($f3, $args) {
		$md = \Markdown::instance();  
        echo Markdown::instance()->convert("[Example](javascript:alert%28%22xss%22%29)"); // <strong>Bold text</strong>
    }
);

$base->run();
