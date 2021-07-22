<?php

function installNewsModule($instanceId, $path)
{
	GLOBAL $App, $Db;
	
	$sql1 = "CREATE TABLE `mod_news_{IID}` (
		  `id` int(11) NOT NULL auto_increment,
		  `date` datetime NOT NULL default '0000-00-00 00:00:00',
		  `name` varchar(255) NOT NULL default '',
		  `short_description` text NOT NULL,
		  `content` text NOT NULL,
		  `is_hot_news` int(11) NOT NULL default '0',
		  `picture` text NOT NULL,
		  PRIMARY KEY  (`id`)
		)
	";

	$news_di = array (
		'table' => 'mod_news_',
		'order' => 'date DESC',
		'label'	=>	'News',
		'list_fields' => 'date,name',
		'fields' => array (
			'id' => array (
				'type' => LiskType::TYPE_HIDDEN,
			),
			'date' => array(
				'type' => LiskType::TYPE_DATETIME,
				'label'  => 'Date',
				'format' => 'd M Y',
			),
			'name' => array (
				'type' => LiskType::TYPE_INPUT,
				'label' => 'Title'
			),
			'short_description' => array (
				'type' => LiskType::TYPE_TEXT,
				'label' => 'Short Description'
			),
			'content' => array (
				'type' => LiskType::TYPE_HTML,
				'label' => 'Content'
			),
			'picture'	=> array(
				'type'		=> LiskType::TYPE_IMAGE,
				'object'	=> array(
					'thumbnails'	=> array (
						0	=> array (
							'name'		=> 'small',
							'height'	=> 220,
							'width'		=> 220,
						),
					),
					'no_image'	=> false,
					'path'		=> 'PATH',
				)
			),
			'is_hot_news'	=>	LiskType::TYPE_HIDDEN,
		)
	);

	$hot_news_di = array (
		'table' => 'mod_news_',
		'order' => 'date DESC',
		'label'	=>	'Latest News',
		'list_fields' => 'name',
		'fields' => array (
			'id' => array (
				'type' => LiskType::TYPE_HIDDEN,
			),
			'date' => array(
				'type' => LiskType::TYPE_DATETIME,
				'label'  => 'Date',
				'format' => 'm/d/Y g:i a',
			),
			'name' => array (
				'type' =>LiskType::TYPE_INPUT,
				'label' => 'Title'
			),
			'short_description' => array (
				'type' => LiskType::TYPE_TEXT,
				'label' => 'Short Description'
			),
			'content' => array (
				'type' => LiskType::TYPE_HTML,
				'label' => 'Content'
			),
			'oder'	=>	LiskType::TYPE_HIDDEN,
			'is_hot_news'	=>	LiskType::TYPE_HIDDEN,
		)
	);

	$cross_list_hot_news = array(
		'name'			=> 'Latest News',
		'list'			=> 'news',
		'cross_field'   => 'is_hot_news',
	);

	$newsDIName	    = 'dyn_news_'.$instanceId;
	$hotNewsDIName	= 'dyn_hot_news_'.$instanceId;
	$crossListName	= 'dyn_cross_list_hot_news_'.$instanceId;

	$config = array(
		'base_url'		=> $path,
		'news_di'		=> $newsDIName,
		'hot_news_di'	=> $hotNewsDIName,
		'cross_list'    => $crossListName,

		'type_news'		=> 1,
		'last_news_count' => 3,
		'items_per_page' => 10,
		'pages_per_page' => 5,
	);

	// update config
	$Db->Update("id=$instanceId",array(
		'config' => serialize($config)
	),'sys_modules');

	// install news DI
	$news_di['table'] .= $instanceId;
	$news_di['fields']['picture']['object']['path'] = 'modules/news_'.$instanceId;
	$App->InstallDI($newsDIName, $news_di);

	// install hot_news DI
	$hot_news_di['table'] .= $instanceId;
	$App->InstallDI($hotNewsDIName, $hot_news_di);

	// cross
	$cross_list_hot_news['list'] = $newsDIName;
	$cross_list_hot_news['cross_list'] = $hotNewsDIName;
	$App->InstallDI($crossListName, $cross_list_hot_news);

	//install tables sql
	$sql = str_replace('{IID}', $instanceId, $sql1);
	$Db->Query($sql);

	$installPath = $App->sysRoot.'init/modules/news/install/';

	//copy templates
	$FileSystem = new FileSystem();
	$FileSystem->CopyDir($installPath.'tpl/', $App->sysRoot.'tpl/modules/news_'.$instanceId.'/');
	
	//create image directory
	$FileSystem->CreateDir($App->sysRoot.$App->filePath."modules/news_$instanceId");
}

function UninstallNewsModule($instanceId)
{
	GLOBAL $Db, $App, $FileSystem;
	
	//drop table sql
	$sql = "DROP TABLE `mod_news_[iid]`";

	//drop tables
	$sql = Format::String($sql, array(
		'iid'	=> $instanceId
	));
	$Db->Query($sql);

	//data items
	$newsDIName	    = 'dyn_news_'.$instanceId;
	$hotNewsDIName	= 'dyn_hot_news_'.$instanceId;
	$crossListName	= 'dyn_cross_list_hot_news_'.$instanceId;

	//uninstall dataitems
	$App->UninstallDI($newsDIName);
	$App->UninstallDI($hotNewsDIName);
	$App->UninstallDI($crossListName);

	//delete tpl files
	$FileSystem->DeleteDir($App->sysRoot.'tpl/modules/news_'.$instanceId.'/');
	
	//delete image files
	$FileSystem->DeleteDir($App->sysRoot.$App->filePath."modules/news_$instanceId/");
}

?>