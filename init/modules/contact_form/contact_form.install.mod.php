<?php

function installContactFormModule($instanceId, $path)
{
	GLOBAL $Db,$App,$FileSystem;
	
	$sql1 = "CREATE TABLE `mod_contact_form_[iid]` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `department` int(3) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `message` text NOT NULL,
  PRIMARY KEY  (`id`)
	)";

	$sql2	= "INSERT INTO `sys_email` VALUES ('contact_form_[iid]', '%EMAIL_TO%', 'New contact form message', '<h2>New feedback form message</h2><hr style=\"width: 100%; height: 2px;\" /><br />%CONTACT_FORM%', 'noreply@company.com', 1);";
	$sql6	= "INSERT INTO `sys_email` VALUES ('contact_form_confirmation_[iid]', '%EMAIL%', 'Confirmation', '<h2>Thank you</h2> <p>Your request is accepted.</p>', 'noreply@company.com', 1);";

	$sql3="CREATE TABLE `mod_contact_form_departments_[iid]` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `oder` int(10) unsigned NOT NULL default '0',
	  `name` varchar(50) NOT NULL default '',
	  `email` varchar(50) NOT NULL default '',
	  PRIMARY KEY  (`id`)
	)";

	$sql4 = "INSERT INTO `mod_contact_form_departments_[iid]` VALUES ('', 1, 'Sales', 'sales@company.com');";
	$sql5 = "INSERT INTO `mod_contact_form_departments_[iid]` VALUES ('', 2, 'Support', 'support@company.com');";

	$DIName	= 'dyn_contact_form_'.$instanceId;
	$DINameDepartments = 'dyn_contact_form_departments_'.$instanceId;

	$DIStructure = array (
		'table'	=> 'mod_contact_form_',
		'label'	=> 'Contact Form',
		'order'	=> 'date',
		'fields'	=> array(
			'id'		=> LiskType::TYPE_HIDDEN,
			'date'		=> LiskType::TYPE_HIDDEN,
			'department'=> LiskType::TYPE_HIDDEN,
			'name'		=> LiskType::TYPE_INPUT,
			'email'		=> LiskType::TYPE_INPUT,
			'message'	=> LiskType::TYPE_TEXT
		),
		'redefine_date'	=> array(
			'date'			=> array(
				'type'			=> LiskType::TYPE_DATETIME,
				'def_value'		=> 'sql:NOW()'
			)
		),
		'redefine_department'	=> array(
			'department'	=> array(
				'type'			=> LiskType::TYPE_LIST,
				'object'		=> 'data_'.$DINameDepartments
			)
		)
	);

	$DepartmentStructure = array(
		'table'	=> 'mod_contact_form_departments_',
		'order'	=> 'oder',
		'fields'	=> array(
			'id'   => LiskType::TYPE_HIDDEN,
			'oder' => LiskType::TYPE_HIDDEN,
			'name' => array(
				'type'		=> LiskType::TYPE_INPUT,
				'name'		=> 'Name',
				'check'     => 'empty',
			),
			'email' => array(
				'type'		=> LiskType::TYPE_INPUT,
				'name'      => 'Email',
				'check'     => 'empty',
			),
		),
		'list_fields' => 'name,email',
		'label' => 'Department'
	);

	$config = array(
		'di_name'				=> $DIName,
		'di_departments'		=> $DINameDepartments,
		'use_departments'		=> 1,
		'use_archive'			=> 1,
		'departments_caption'	=> 'Department',
		'default_send_to'		=> 'name@company.com',
		'render'				=> 1,
        'use_confirmation'		=> 0,
        'export_enabled'		=> 0,
        'export_uri'			=> 'http://www.site.com/webservice.php',
        'export_from_site'		=> 'any site identificator here'
	);

	// update config
	$Db->Update("id=$instanceId",array(
		'config'	=>serialize($config)
	),'sys_modules');


	// install DI
	$DIStructure['table'] .= $instanceId;
	$App->InstallDI($DIName, $DIStructure);

	$DepartmentStructure['table'] .= $instanceId;
	$App->InstallDI($DINameDepartments, $DepartmentStructure);

	//install SQL
	$sqlToInstall = array($sql1,$sql2,$sql3,$sql4,$sql5,$sql6);
	foreach ($sqlToInstall as $sql)
	{
		$sql = Format::String($sql,array(
			'iid'	=> $instanceId,
			'path'	=> $path
		));
		$Db->Query($sql);
	}

	$installPath = $App->sysRoot.'init/modules/contact_form/install/';

	// copy templates files
	$sourceFolder		= $installPath.'tpl/';
	$destinationFolder	= $App->sysRoot.'tpl/modules/contact_form_'.$instanceId;
	$FileSystem->CopyDir($sourceFolder, $destinationFolder);
	
}

function uninstallContactFormModule($instanceId)
{
	GLOBAL $Db,$App,$FileSystem;

	// drop tables
	$sqls = array(
	    "DROP TABLE `mod_contact_form_[iid]`",
	    "DROP TABLE `mod_contact_form_departments_[iid]`",
	    "DELETE FROM sys_email WHERE id='contact_form_[iid]'",
	    "DELETE FROM sys_email WHERE id='contact_form_confirmation_[iid]'",
	);
    
	foreach ($sqls as $sql)
	{
	    $sql = Format::String($sql, array(
    		'iid'	=> $instanceId
    	));
    	$Db->Query($sql);
	}

	// data items
	$DIName		= 'dyn_contact_form_'.$instanceId;
	$DIName2	= 'dyn_contact_form_departments_'.$instanceId;

	//uninstall dataitems
	$App->UninstallDI($DIName);
	$App->UninstallDI($DIName2);

	//delete templates folder
	$tplFolder	= $App->sysRoot.'tpl/modules/contact_form_'.$instanceId;
	$FileSystem->DeleteDir($tplFolder);

}
?>