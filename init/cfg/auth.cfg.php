<?php

$AUTH_MAIN = array(
    'table'					=> 'users',
    'md5_crypt'				=> false,
    'default_level'			=> 0,
    'login_page'			=> 'login/',
    'logged_page'			=> 'my_account/',
    'auto_jump'				=> true,
    'sid_name'				=> 'sid_main',
    'pages'				=> array(
 		//'members_area/'			=> 1
    )
);

$AUTH_CP = array(
    'table'					=> 'sys_cp_users',
    'md5_crypt'				=> true,
    'default_level'			=> 1,
    'login_page'			=> 'login.php',
    'logged_page'			=> 'index.htm',
    'auto_jump'				=> false,
    'sid_name'				=> 'sid_cp',
    'pages'					=> array(
    	'top.php' => 0,
    	'menu.php' => 0,
        'suggest_list.php' => 0,
    )
);
?>