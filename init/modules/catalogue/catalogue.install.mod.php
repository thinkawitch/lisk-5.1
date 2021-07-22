<?php

function installCatalogueModule($instanceId, $path, $pageName)
{
	GLOBAL $Db,$App,$FileSystem;

	$sql1 = "CREATE TABLE `mod_catalogue_categories_[iid]` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `oder` int(10) unsigned NOT NULL default '0',
	  `parent_id` int(10) unsigned NOT NULL default '0',
	  `parents` varchar(255) NOT NULL default '',
	  `url` varchar(255) NOT NULL default '',
	  `name` varchar(255) NOT NULL default '',
	  PRIMARY KEY  (`id`)
	)";
	$qPageName = Database::Escape($pageName);
	$sql2 = "INSERT INTO `mod_catalogue_categories_[iid]` VALUES (1, 0, 0, '', '[path]', $qPageName);";

	$sql3 = "CREATE TABLE `mod_catalogue_items_[iid]` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `oder` int(10) unsigned NOT NULL default '0',
	  `parent_id` int(10) unsigned NOT NULL default '0',
	  `parents` varchar(255) NOT NULL default '',
	  `url` varchar(255) NOT NULL default '',
	  `hot` tinyint(3) unsigned NOT NULL default '0',
	  `name` varchar(255) NOT NULL default '',
	  `description_small` text NOT NULL,
	  `description` text NOT NULL,
	  `image` text NOT NULL,
	  `price` decimal(10,2) NOT NULL default '0.00',
	  PRIMARY KEY  (`id`)
	)";

	$catalogueItemDI = array (
		'table' => 'mod_catalogue_items_',
		'label'	=> 'Product',
		'order' => 'oder',
		'fields'	=> array(
			'id'				=> LiskType::TYPE_HIDDEN,
			'oder'				=> LiskType::TYPE_HIDDEN,
			'parent_id'			=> array(
				'type'				=> LiskType::TYPE_CATEGORY,
				'object'			=> 'TREE_NAME',
				'label'				=> 'Category',
			),
			'parents'			=> LiskType::TYPE_HIDDEN,
			'url'				=> LiskType::TYPE_HIDDEN,
			'hot'				=> LiskType::TYPE_HIDDEN,
			'name'				=> LiskType::TYPE_INPUT,
			'description_small' => LiskType::TYPE_TEXT,
			'description' 		=> LiskType::TYPE_TEXT,
			'image' 			=> array(
				'type'				=> LiskType::TYPE_IMAGE,
				'object'			=> array(
					'thumbnails'	=> array(
						0	=> array(
							'name'		=> 'big',
							'height'	=> 750,
							'width'		=> 550,
						),
						1	=> array(
							'name'		=> 'medium',
							'height'	=> 300,
							'width'		=> 300,
						),
						2	=> array(
							'name'		=> 'small',
							'height'	=> 100,
							'width'		=> 100
						),
					),
					'no_image'	=> false,
					'path'		=> 'PATH',
				)
			),
			'price' 			=> array(
	            'type' => LiskType::TYPE_INPUT,
	            'label' => 'Price',
	        ),
		),
		'list_fields' => 'name,price'
	);

	$catalogueCategoryDI = array (
		'table'		=> 'mod_catalogue_categories_',
		'label'		=> 'Category',
		'order'		=> 'oder',
		'fields'	=> array (
			'id'		=> LiskType::TYPE_HIDDEN,
			'oder'		=> LiskType::TYPE_HIDDEN,
			'parent_id'	=> array(
				'type'		=> LiskType::TYPE_CATEGORY,
				'object'	=> 'TREE_NAME',
				'label'		=> 'Category',
			),
			'parents'	=> LiskType::TYPE_HIDDEN,
			'url'		=> LiskType::TYPE_HIDDEN,
			'name'		=> LiskType::TYPE_INPUT,
		),
		'list_fields'	=> 'name'
	);

	$catalogueTree = array(
		'name'		=> 'Products Catalogue',
		'max_level'	=> 3,

		'node'		=> 'NODE',
		'point'		=> 'POINT',
	);

	$catalogueCrossTree = array (
		'name'				=> 'Hot Products',
		'tree'				=> 'TREENAME',
		'cross_field'		=> 'hot'
	);


	$categoryDIName		= 'dyn_catalogue_category_'.$instanceId;
	$itemDIName			= 'dyn_catalogue_item_'.$instanceId;
	$treeDIName			= 'dyn_tree_catalogue_'.$instanceId;
	$crossTreeDIName	= 'dyn_cross_tree_catalogue_'.$instanceId;

	$config = array(
		'base_url'		=> $path,
		'categories_di'	=> $categoryDIName,
		'items_di'		=> $itemDIName,
		'tree_di'		=> $treeDIName,
		'cross_tree_di'	=> $crossTreeDIName,
		'items_per_page'=> 10,
		'pages_per_page'=> 5,
	);

	// update config
	$Db->Update("id=$instanceId", array(
		'config'	=>serialize($config)
	),'sys_modules');


	// install categories DI
	$catalogueCategoryDI['table'] = $catalogueCategoryDI['table'].$instanceId;
	$catalogueCategoryDI['fields']['parent_id']['object'] = $treeDIName;
	$App->InstallDI($categoryDIName, $catalogueCategoryDI);

	//install items DI
	$catalogueItemDI['table'] = $catalogueItemDI['table'].$instanceId;
	$catalogueItemDI['fields']['parent_id']['object'] = $treeDIName;
	$catalogueItemDI['fields']['image']['object']['path'] = "modules/catalogue_$instanceId/items/";
	$App->InstallDI($itemDIName, $catalogueItemDI);

	//install tree DI
	$catalogueTree['node'] = $categoryDIName;
	$catalogueTree['point'] = $itemDIName;
	$App->InstallDI($treeDIName, $catalogueTree);

	//install cross tree DI
	$catalogueCrossTree['tree'] = $treeDIName;
	$App->InstallDI($crossTreeDIName, $catalogueCrossTree);

	//install categories table sql
	$sql1=Format::String($sql1, array(
		'iid'	=> $instanceId,
	));
	$sql2=Format::String($sql2, array(
		'iid'	=> $instanceId,
		'path'	=> $path
	));
	$sql3=Format::String($sql3, array(
		'iid'	=> $instanceId,
	));

	$Db->Query($sql1);
	$Db->Query($sql2);
	$Db->Query($sql3);

	$installPath = $App->sysRoot.'init/modules/catalogue/install/';

	// copy templates files
	$sourceFolder		= $installPath.'tpl/';
	$destinationFolder	= $App->sysRoot.'tpl/modules/catalogue_'.$instanceId;
	$FileSystem->CopyDir($sourceFolder, $destinationFolder);

	//create files directory
	$FileSystem->CreateDir($App->sysRoot.$App->filePath."modules/catalogue_$instanceId/items/");
}

function uninstallCatalogueModule($instanceId)
{
	GLOBAL $Db,$App,$FileSystem;

	// drop tables sql
	$sql1 = "DROP TABLE `mod_catalogue_categories_[iid]`";
	$sql2 = "DROP TABLE `mod_catalogue_items_[iid]`";

	// drop tables
	$sql1 = Format::String($sql1, array(
		'iid' => $instanceId
	));
	$Db->Query($sql1);

	$sql2 = Format::String($sql2, array(
		'iid' => $instanceId
	));
	$Db->Query($sql2);

	// data items
	$categoryDIName	 = 'dyn_catalogue_category_'.$instanceId;
	$itemDIName		 = 'dyn_catalogue_item_'.$instanceId;
	$treeDIName		 = 'dyn_tree_catalogue_'.$instanceId;
	$crossTreeDIName = 'dyn_cross_tree_catalogue_'.$instanceId;

	//uninstall dataitems
	$App->UninstallDI($categoryDIName);
	$App->UninstallDI($itemDIName);
	$App->UninstallDI($treeDIName);
	$App->UninstallDI($crossTreeDIName);

	//delete templates folder
	$tplFolder = $App->sysRoot.'tpl/modules/catalogue_'.$instanceId;
	$FileSystem->DeleteDir($tplFolder);

	//remove files
	$FileSystem->DeleteDir($App->sysRoot.$App->filePath."modules/catalogue_$instanceId/");
}

?>