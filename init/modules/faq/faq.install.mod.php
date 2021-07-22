<?php

function installFaqModule($instanceId, $path, $pageName)
{
	GLOBAL $Db,$App,$FileSystem;

	$faq_module_sql1 = "CREATE TABLE `mod_faq_categories_{IID}` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `oder` int(10) unsigned NOT NULL default '0',
	  `parent_id` int(10) unsigned NOT NULL default '0',
	  `parents` varchar(255) NOT NULL default '',
	  `url` varchar(255) NOT NULL default '',
	  `name` varchar(255) NOT NULL default '',
	  PRIMARY KEY  (`id`)
	)";

	$faq_module_sql2 = "CREATE TABLE `mod_faq_questions_{IID}` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `oder` int(10) unsigned NOT NULL default '0',
	  `parent_id` int(10) unsigned NOT NULL default '0',
	  `parents` varchar(255) NOT NULL default '',
	  `url` varchar(255) NOT NULL default '',
	  `name` varchar(255) NOT NULL default '',
	  `answer` text NOT NULL,
	  PRIMARY KEY  (`id`)
	)";

	$qPageName = Database::Escape($pageName);
	$sql3 = "INSERT INTO `mod_faq_categories_[iid]` VALUES (1, 0, 0, '', '[path]', $qPageName);";

	$faq_categories_di = array (
		'table'	=> 'mod_faq_categories_',
		'label'	=> 'Category',
		'order'	=> 'oder',
		'fields'	=> array(
			'id'		=> LiskType::TYPE_HIDDEN,
			'oder'		=> LiskType::TYPE_HIDDEN,
			'parent_id'	=> array(
				'type'		=> LiskType::TYPE_CATEGORY,
				'object'	=> '',
				'label'     => 'Category',
			),
			'url'		=> LiskType::TYPE_HIDDEN,
			'parents'	=> LiskType::TYPE_HIDDEN,
			'name'		=> LiskType::TYPE_INPUT,
		)
	);

	$faq_question_di = array (
		'table'	=> 'mod_faq_questions_',
		'label'	=> 'Question',
		'order'	=> 'oder',
		'fields'	=> array(
			'id'		=> LiskType::TYPE_HIDDEN,
			'oder'		=> LiskType::TYPE_HIDDEN,
			'parent_id'	=> array(
				'type'		=> LiskType::TYPE_CATEGORY,
				'object'	=> '',
				'label'     => 'Category',
			),
			'parents'	=> LiskType::TYPE_HIDDEN,
			'url'		=> LiskType::TYPE_HIDDEN,
			'name'		=> array(
				'type'		=> LiskType::TYPE_INPUT,
				'label'		=> 'Question',
			),
			'answer'	=> array(
				'type'		=> LiskType::TYPE_HTML,
			),
		),
		'redefine_list'	=> array(
			'parent_id'	=> array(
				'type'		=> LiskType::TYPE_HIDDEN,
			),
		)
	);

	$faq_tree = array(
		'name'		=> 'FAQ',
		'max_level'	=> 1,

		'node'		=> '',
		'point'		=> ''
	);

	$questionsDIName	= 'dyn_faq_question_'.$instanceId;
	$categoriesDIName	= 'dyn_faq_category_'.$instanceId;
	$faqTreeName		= 'dyn_tree_faq_'.$instanceId;

	$config = array(
		'tree_mode'		=> true,
		'base_url'		=> $path,
		'categories_di'	=> $categoriesDIName,
		'questions_di'	=> $questionsDIName,
		'tree_di'		=> $faqTreeName
	);

	// update config
	$Db->Update("id=$instanceId", array(
		'config'	=> serialize($config)
	),'sys_modules');


	// install categories DI
	$faq_categories_di['table'] .= $instanceId;
	$faq_categories_di['fields']['parent_id']['object'] = $faqTreeName;
	$App->InstallDI($categoriesDIName, $faq_categories_di);

	//install question DI
	$faq_question_di['table'] .= $instanceId;
	$faq_question_di['fields']['parent_id']['object'] = $faqTreeName;
	$App->InstallDI($questionsDIName, $faq_question_di);

	//install tree DI
	$faq_tree['node'] = $categoriesDIName;
	$faq_tree['point'] = $questionsDIName;
	$App->InstallDI($faqTreeName,$faq_tree);

	//install categories table sql
	$faq_module_sql1 = str_replace('{IID}', $instanceId, $faq_module_sql1);
	$Db->Query($faq_module_sql1);

	//install question table sql
	$faq_module_sql2 = str_replace('{IID}', $instanceId, $faq_module_sql2);
	$Db->Query($faq_module_sql2);

	$sql3 = Format::String($sql3, array(
		'iid'	=> $instanceId,
		'path'	=> $path
	));
	$Db->Query($sql3);

	$installPath = $App->sysRoot.'init/modules/faq/install/';

	//copy templates files
	$sourceFolder		= $installPath.'tpl/';
	$destinationFolder	= $App->sysRoot.'tpl/modules/faq_'.$instanceId;
	$FileSystem->CopyDir($sourceFolder, $destinationFolder);

}

function uninstallFaqModule($instanceId)
{
	GLOBAL $Db,$App,$FileSystem;

	// drop tables sql
	$sql1 = "DROP TABLE `mod_faq_categories_[iid]` ";
	$sql2 = "DROP TABLE `mod_faq_questions_[iid]` ";

	// drop tables
	$sql1 = Format::String($sql1,array(
		'iid'	=> $instanceId
	));
	$Db->Query($sql1);

	$sql2 = Format::String($sql2,array(
		'iid'	=> $instanceId
	));
	$Db->Query($sql2);

	// data items
	$questionsDIName	= 'dyn_faq_question_'.$instanceId;
	$categoriesDIName	= 'dyn_faq_category_'.$instanceId;
	$faqTreeName		= 'dyn_tree_faq_'.$instanceId;

	//uninstall dataitems
	$App->UninstallDI($questionsDIName);
	$App->UninstallDI($categoriesDIName);
	$App->UninstallDI($faqTreeName);

	//delete templates folder
	$tplFolder	= $App->sysRoot.'tpl/modules/faq_'.$instanceId;
	$FileSystem->DeleteDir($tplFolder);

}
?>