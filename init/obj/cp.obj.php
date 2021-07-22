<?php

$GLOBALS['App']->Load('cp_settings', 'lang');

$GLOBALS['DATA_CP_LOGIN_HISTORY'] = array(
	'table'	=> 'sys_cp_logins',
	'order'	=> 'date DESC',
	'fields'	=> array(
		'id'		=> LiskType::TYPE_HIDDEN,
		'date'		=> LiskType::TYPE_DATETIME,
		'login'		=> LiskType::TYPE_INPUT,
		'ip'		=> LiskType::TYPE_INPUT,
		'level'	=> array(
            'type'		=> LiskType::TYPE_LIST,
			'object'    => 'data_usergroup_cp',
            'label' => 'CP Group',
        ),
	),
	'list_fields'	=> 'date,login,level'
);

$DATA_SYS_CP_MENU = array(
	'table'  => 'sys_cp_menu',
	'order'  => 'oder',
	'fields' => array(
		'id' 	=> LiskType::TYPE_HIDDEN,
		'oder'	=> LiskType::TYPE_HIDDEN,
		'parent_id' => array(
			'type'  		=> LiskType::TYPE_TREE,
			'object'		=> 'sys_cp_menu',
			'label' 		=> 'Parent',
			'category_cond'	=> "[is_category]==1",
		),
		'parents' => LiskType::TYPE_HIDDEN,

		'is_category' => array(
			'type'  => LiskType::TYPE_HIDDEN,
		),

		'name' => array(
			'type'  => LiskType::TYPE_INPUT,
			'label' => 'Name',
			'check' => 'pre:empty',
		),
		'url'	=> array(
			'type'	=> LiskType::TYPE_INPUT,
			'label'	=> 'Menu href'
		),
		'hint'	=> array(
			'type'	=> LiskType::TYPE_INPUT,
			'label'	=> 'Hint message'
		),
	),
	'label' => 'CP Menu',
	'redefine_category'	=> array(
		'is_category' 		=> array(
			'type'  			=> LiskType::TYPE_HIDDEN,
			'def_value'			=> 1
		),
		'url'				=> LiskType::TYPE_HIDDEN,
		'hint'				=> LiskType::TYPE_HIDDEN,
	),
	'redefine_record'	=> array(
		'is_category' 		=> array(
			'type'  			=> LiskType::TYPE_HIDDEN,
			'def_value'			=> 0
		),
	),
);

$DATA_USERGROUP_CP = array(
	'table'		=> 'sys_cp_groups',
	'order'		=> 'name',
	'fields'	=> array(
		'id' => LiskType::TYPE_HIDDEN,
		'name' => array(
			'type'	=> LiskType::TYPE_INPUT,
			'check' => 'empty',
			'label' => 'Name',
		),
	),
	'label' => 'Group'
);

$GLOBALS['DATA_USER_CP']	= array(
	'table'	=> 'sys_cp_users',
	'label'	=> LANG_USER_CP,
	'order'	=> 'login',
	'fields'	=> array (
		'id'		=> LiskType::TYPE_HIDDEN,
		'login'		=> array(
			'type' => LiskType::TYPE_INPUT,
			'check' => 'empty',
		),
		'password'	=> array(
			'type'		=> LiskType::TYPE_PASSWORD,
			'view'		=> '***',
			'check' => 'empty|min:5',
		    'md5_crypt' => true,
		),
		'email'		=> array(
			'type' => LiskType::TYPE_INPUT,
		),
		'sid'		=> LiskType::TYPE_HIDDEN,
		'level'		=> array (
			'type'		=> LiskType::TYPE_LIST,
			'object'    => 'data_usergroup_cp',
			'def_value'	=> 1,
			'label'     => LANG_CP_GROUP,
		),
		'lastdate'	=> LiskType::TYPE_HIDDEN,
		'lastlogin'	=> array (
				'type'	=> LiskType::TYPE_DATETIME,
				'label'	=> 'Last login time'
		),
	),
	'redefine_edit'	=> array(
		'password'	=> array(
			'type'		=> LiskType::TYPE_PASSWORD,
			'view'		=> '***',
	        'md5_crypt' => true,
		),
	),
	'redefine_custom_links' => array(
		'custom_links' => array(
			'type' => LiskType::TYPE_TEXT,
		),
	),
	'list_fields'	=> 'login,email,lastlogin,level',
);

$GLOBALS['DATA_CP_CUSTOM_LINK'] = array(
	'label' => 'Custom Link',
	'fields' => array(
		'name' => array(
			'type' => LiskType::TYPE_INPUT,
			'label' => 'Name',
			'check' => 'pre:empty',
		),
		'link' => array(
			'type' => LiskType::TYPE_INPUT,
			'label' => 'Link',
			'check' => 'pre:empty',
		),
	)
);

$GLOBALS['DATA_CP_MESSAGE'] = array(
	'label' => 'Message',
	'order' => 'date DESC',
	'table' => 'sys_cp_messages',
	'fields' => array(
		'id' => LiskType::TYPE_HIDDEN,
		'date' => array(
			'type' => LiskType::TYPE_DATETIME,
			'def_value'	=> 'sql:NOW()'
		),
		'id_from' => array(
			'type' => LiskType::TYPE_LIST,
			'label' => 'From',
			'object' => 'data_user_cp',
			'cross_field' => 'login',
		),
		'id_to' => array(
			'type'			=> LiskType::TYPE_LIST,
			'label'			=> 'To',
			'object'		=> 'data_user_cp',
			'cross_field' 	=> 'login',
			'form'			=> 'style="width:180px;"'
		),
		'subject' => array(
			'type' => LiskType::TYPE_INPUT,
			'check' => 'pre:empty',
		),
		'message' => array(
			'type' => LiskType::TYPE_WIKI,
			'form'	=> 'style="height:200px; width:400px;"'
		),
		'is_deleted_from' => LiskType::TYPE_HIDDEN,
		'is_deleted_to' => LiskType::TYPE_HIDDEN,
		'is_read'		=> LiskType::TYPE_HIDDEN,
	),

	'list_fields' => 'id_from,id_to,subject,date',

	'redefine_add' => array(
		'id_from' => LiskType::TYPE_HIDDEN,
		'id_to' => array(
			'type' => LiskType::TYPE_LIST,
			'object' => 'data_user_cp',
			'cross_field' => 'login',
			//'cond' => 'id!='.$GLOBALS['Auth']->user['id'],
			'label' => 'Send To',
			'form'			=> 'style="width:180px;"'
		),
		'date'	=> array(
			'type'		=> LiskType::TYPE_HIDDEN,
			'def_value'	=> 'sql:NOW()'
		)
	),
);

$GLOBALS['DATA_SYS_CACHE'] = array(
	'label' => 'Cached Url',
	'table' => 'sys_cache',
	'fields' => array(
		'id'	=> LiskType::TYPE_HIDDEN,
		'url'	=> array(
			'type'	=> LiskType::TYPE_INPUT,
			'label'	=> 'Url',
			'check'	=> 'empty'
		),
		'cache_time' => array(
			'type'	=> LiskType::TYPE_INPUT,
			'label'	=> 'Time in minutes',
			'def_value' => '0',
			'check' => 'empty'
		),
		'cache_level' => array(
			'type'	=> LiskType::TYPE_INPUT,
			'label'	=> 'Cache level',
			'check' => 'empty'
		),
	),
	'list_fields' => 'url,cache_time,cache_level'
);

$GLOBALS['DATA_SYS_PROFILER'] = array(
	'label' => 'Profiler',
	'table' => 'sys_profiler',
	'fields' => array(
		'id'			=> LiskType::TYPE_HIDDEN,
		'date'			=> LiskType::TYPE_DATETIME,
		'pageurl'		=> array(
			'type' => LiskType::TYPE_INPUT,
			'label' => 'Page Url'
		),
		'total_time'	=> LiskType::TYPE_INPUT,
		'render_time'	=> LiskType::TYPE_INPUT,
		'sql_time'		=> LiskType::TYPE_INPUT,
		'user_id'		=> LiskType::TYPE_INPUT,
		'sql_log'		=> LiskType::TYPE_HTML,
		'page_cached'	=> LiskType::TYPE_INPUT,
	),
	'list_fields' => 'date,pageurl,total_time,render_time,sql_time'
);
?>