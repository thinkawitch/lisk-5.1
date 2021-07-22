<?php

function installMediaGalleryModule($instanceId, $path, $pageName)
{
	GLOBAL $Db,$App,$FileSystem;

	$sql = "CREATE TABLE `mod_media_gallery_categories_{$instanceId}` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `parent_id` int(11) NOT NULL default '0',
		  `parents` varchar(255) NOT NULL default '',
		  `oder` int(11) NOT NULL default '0',
		  `name` varchar(255) NOT NULL default '',
		  `description` text NOT NULL,
		  `items_count` int(10) unsigned NOT NULL default '0',
		  `url` text NOT NULL,
		  `category_type` tinyint(3) unsigned NOT NULL default '0',
		  `image_preview` text NOT NULL,
		  PRIMARY KEY  (`id`)
		)";
	$Db->Query($sql);

	$sql = "CREATE TABLE `mod_media_gallery_items_{$instanceId}` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `parent_id` int(11) NOT NULL default '0',
		  `oder` int(11) NOT NULL default '0',
		  `parents` varchar(255) NOT NULL default '',
		  `name` varchar(255) NOT NULL default '',
		  `file_media` varchar(255) NOT NULL default '',
		  `file_image` text NOT NULL,
		  `description` text NOT NULL,
		  `url` text NOT NULL,
		  PRIMARY KEY  (`id`)
		)";
	$Db->Query($sql);

	$qPageName = Database::Escape($pageName);
	$sql = "INSERT INTO `mod_media_gallery_categories_{$instanceId}` VALUES (1, 0, '', 0, $qPageName, '', 0, '{$path}', 1, '');";
	$Db->Query($sql);

	$media_gallery_category_di = array(
		'table' => "mod_media_gallery_categories_{$instanceId}",
		'order' => 'oder',
		'fields' => array(
			'id' => LiskType::TYPE_HIDDEN,
			'oder' => LiskType::TYPE_HIDDEN,
			'parent_id'  => array(
				'label'  => 'Category',
				'type'   => LiskType::TYPE_CATEGORY,
				'object' => '',
				'check' => 'empty|not:0',
			),
			'parents' => LiskType::TYPE_HIDDEN,
			'url' => LiskType::TYPE_HIDDEN,
			'name' => array(
				'type'  => LiskType::TYPE_INPUT,
				'check' => 'empty',
			),
			'items_count' => array(
				'type'  => LiskType::TYPE_HIDDEN,
				'label' => 'Items',
			),
			'category_type' => array(
				'type'   => LiskType::TYPE_LIST,
				'label'  => 'Type',
				'object' => 'def_media_gallery_category',
				'is_system' => true,
			),
			/*'image_preview' => array(
				'type' => 'image',
				'object' => 'media_gallery_category_image',
				'label'  => 'Image',
			),*/
			'image_preview' => array(
				'type' => LiskType::TYPE_HIDDEN,
			),
		),

		'list_fields' => 'name,items_count,category_type',
		'label' => 'Category',
	);

	$media_gallery_item_di = array(
		'table'		=> "mod_media_gallery_items_{$instanceId}",
		'order'		=> 'oder',
		'fields'	=> array(
			'id' => LiskType::TYPE_HIDDEN,
			'parent_id' => array(
				'label' => 'Category',
				'type' => LiskType::TYPE_CATEGORY,
				'object' => '',
				'check' => 'empty|not:0',
			),
			'parents' => LiskType::TYPE_HIDDEN,
			'url'		  => LiskType::TYPE_HIDDEN,
			'oder' => LiskType::TYPE_HIDDEN,
			'[mgImageThumbnailSmall]' => array(
				'type'  => 'void',
				'label' => 'Thumbnail',
			),
			'name'			=> array(
				'type'  => LiskType::TYPE_INPUT,
				'check' => 'empty',
			),
			'file_media' => array(
				'label' => 'Media File',
				'type' => LiskType::TYPE_FILE,
				'path' => 'modules/media_gallery_'.$instanceId.'/',
			),
			'file_image' => array(
				'label' => 'Image File',
				'type' => LiskType::TYPE_IMAGE,
				'object' => array(
					'path' => 'modules/media_gallery_'.$instanceId.'/',
					'thumbnails' => array(
						1 => array(
							'name' => 'medium',
							'width'  => 300,
							'height' => 300,
						),
						2 => array(
							'name' => 'small',
							'width'  => 100,
							'height' => 100,
						),
					),
					'no_image' => false,
				),
			),
			'description' => LiskType::TYPE_TEXT,
		),

		'redefine_list_image_gallery' => array(
			'[mgImageThumbnailSmall]' => array(
				'type'  => LiskType::TYPE_INPUT,
				'label' => 'Thumbnail',
			),
		),

		'redefine_media_gallery' => array(
			'file_image' => LiskType::TYPE_HIDDEN,
		),

		'redefine_image_gallery' => array(
			'file_media' => LiskType::TYPE_HIDDEN,
		),

		'list_fields' => 'name',
		'label' => 'Item',
	);

	$media_gallery_tree = array(
		'name'		=> 'Media Gallery',
		'max_level'	=> 4,
		'node'		=> '',
		'point'		=> ''
	);

	$categoriesDIName = 'dyn_media_gallery_category_'.$instanceId;
	$itemsDIName = 'dyn_media_gallery_item_'.$instanceId;
	$mediaGalleryTreeName = 'dyn_tree_media_gallery_'.$instanceId;

	$config = array(
		'base_url'		=> $path,
		'categories_di'	=> $categoriesDIName,
		'items_di'	    => $itemsDIName,
		'tree_di'		=> $mediaGalleryTreeName,
		'ext_image'		=> array(
							'jpg',  'jpe',
							'jpeg', 'gif',
							'png',  'bmp',
							'tiff', 'tif',
							'psd',  'emf',
							'pcx',  'wbmp',
						),
		'ext_flash'		=> array('swf', 'fla'),
		'ext_media'		=> array(
							'avi',  'mov',
							'qt',   'asf',
							'mpg',  'mpeg',
							'vob',  'mp4',
							'3gp',  'wav',
							'mp3',  'mp2',
							'wma',  'au',
							'mid',  'midi',
							'mpc',  'aac',
							'wmv',  'asx',
						),
		'preview_in_popup'     => true,
		'items_per_page' => 10,
		'pages_per_page' => 5,
		'columns_per_table' => 3,
	);

	// update config
	$Db->Update("id=$instanceId", array(
		'config' => serialize($config)
	),'sys_modules');

	// install categories DI
	$media_gallery_category_di['fields']['parent_id']['object'] = $mediaGalleryTreeName;
	$App->InstallDI($categoriesDIName, $media_gallery_category_di);

	// install items DI
	$media_gallery_item_di['fields']['parent_id']['object'] = $mediaGalleryTreeName;
	$App->InstallDI($itemsDIName, $media_gallery_item_di);

	//install tree DI
	$media_gallery_tree['node'] = $categoriesDIName;
	$media_gallery_tree['point'] = $itemsDIName;
	$App->InstallDI($mediaGalleryTreeName, $media_gallery_tree);

	$installPath 		= $App->sysRoot.'init/modules/media_gallery/install/';

	// copy templates files
	$sourceFolder		= $installPath.'tpl/';
	$destinationFolder	= $App->sysRoot.'tpl/modules/media_gallery_'.$instanceId;
	$FileSystem->CopyDir($sourceFolder, $destinationFolder);

	//create files directory
	$FileSystem->CreateDir($App->sysRoot.$App->filePath.'modules/media_gallery_'.$instanceId.'/');

	//create temp directory, for image archive upload
	$FileSystem->CreateDir($App->sysRoot.$App->filePath.'temp/');


	//Copy CMS file to installed folder
	$FileSystem->CreateDir($App->sysRoot.'init/installed/media_gallery/');
	$FileSystem->CopyFile($installPath.'init/cms/media_gallery.cms.php', $App->sysRoot.'init/installed/media_gallery/media_gallery.cms.php', '0755');


}


function uninstallMediaGalleryModule($instanceId, $isLastInstance)
{
	GLOBAL $Db,$App,$FileSystem;

	// drop tables sql
	$Db->Query("DROP TABLE `mod_media_gallery_categories_{$instanceId}`");
	$Db->Query("DROP TABLE `mod_media_gallery_items_{$instanceId}`");

	//remove items
	$categoriesDIName = 'dyn_media_gallery_category_'.$instanceId;
	$itemsDIName = 'dyn_media_gallery_item_'.$instanceId;
	$mediaGalleryTreeName = 'dyn_tree_media_gallery_'.$instanceId;
	$App->UninstallDI($categoriesDIName);
	$App->UninstallDI($itemsDIName);
	$App->UninstallDI($mediaGalleryTreeName);

	//delete templates folder
	$tplFolder	= $App->sysRoot.'tpl/modules/media_gallery_'.$instanceId;
	$FileSystem->DeleteDir($tplFolder);

	//delete files folder
	$FileSystem->DeleteDir($App->sysRoot.$App->filePath.'modules/media_gallery_'.$instanceId.'/');

	//remove cp files
	if ($isLastInstance)
	{
		//delete installed folder and files
		$FileSystem->DeleteDir($App->sysRoot.'init/installed/media_gallery/');
	}
}

?>