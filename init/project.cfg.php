<?php
/* hostMode - different host settings */
$hostMode = 1;
$appName = 'Lisk CMS';

//module metatags, define constant not to call excessive query
define('MODULE_METATAGS_INSTALLED', false);


// developer host
$host1 = array(
	'rootHttpPath'		=> '/lisk-5.1/',

	'sqlHost'         	=> 'localhost',
	'sqlDbname'       	=> 'lisk_5_1',
	'sqlUser'        	=> 'root',
	'sqlPassword'    	=> '',

	'imageLibType'		=> 2,
	'imageMagickPath'	=> 'D:\Work\projects\!imagemagick\\',

	'errorLevel'		=> E_ALL | E_STRICT,
	'debug'				=> true,
    'cache'				=> false,
    'mailDispatcher'	=> 'dispatcher', //instant, //dispatcher

);

/* /hostMode */

?>