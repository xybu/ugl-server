<?php
/**
 * index.php
 *
 * The main routing engine. Requires PCRE > 7.9.
 *
 * @author	Xiangyu Bu
 * @date	Feb 25, 2014
 */

$f3=require('app/lib/base.php');
$f3->config('app/conf/settings.ini');
//new \Session();
//new \Cache();
$f3->run();
