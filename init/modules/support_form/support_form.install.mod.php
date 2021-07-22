<?php

function installSupportModule($instanceId,$path)
{
	GLOBAL $Db,$App,$FileSystem;

	$sql1 = "CREATE TABLE `mod_support_tickets_[iid]` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `ticket_id` varchar(50) NOT NULL default '',
  `department` int(3) unsigned NOT NULL default '0',
  `require_reply` tinyint(3) unsigned NOT NULL default '0',
  `from_first_name` varchar(255) NOT NULL default '',
  `from_last_name` varchar(255) NOT NULL default '',
  `from_email` varchar(255) NOT NULL default '',
  `message` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ticket_id` (`ticket_id`)
	)";

	$sql6 = "CREATE TABLE `mod_support_messages_[iid]` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `ticket_id` varchar(50) NOT NULL default '',
  `is_client` tinyint(3) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `message` text NOT NULL,
  PRIMARY KEY  (`id`)
);";

	$sql7 = "CREATE TABLE `mod_support_reply_templates_[iid]` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `template_type` tinyint(3) unsigned NOT NULL default '0',
  `oder` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `content` text NOT NULL,
  PRIMARY KEY  (`id`)
);";

	$sql8 = "INSERT INTO `mod_support_reply_templates_[iid]` VALUES (1, 0, 0, 'test', 'Hello %FROM_FIRST_NAME% %FROM_LAST_NAME%\r\n\r\n\r\nRegards, \r\nDavid');";
	$sql10 = "INSERT INTO `mod_support_reply_templates_[iid]` VALUES (1, 1, 0, 'test paragraph', 'Test Paragraph here');";

	$sql9	= "INSERT INTO `sys_email` VALUES ('support_notification_[iid]', 'name@company.com', 'New reply notification', '%FROM_FIRST_NAME% %FROM_LAST_NAME% \r\n %FROM_EMAIL% %MESSAGE%\r\n', 'noreply@company.com', 0);";

	$sql2	= "INSERT INTO `sys_email` VALUES ('support_[iid]', '%EMAIL_TO%', 'Re: CompanyName enquiry', '%MESSAGE%\r\n\r\n***Important***\r\nPlease do not reply to this email address. To reply this message or view messages history please follow the link below:\r\n\r\n%TICKET_LINK%\r\n\r\nIf you can not click the link above, please copy and paste it to your browser navigation line.', 'noreply@company.com', 0);";


	$sql3="CREATE TABLE `mod_support_departments_[iid]` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `oder` int(10) unsigned NOT NULL default '0',
	  `name` varchar(50) NOT NULL default '',
	  `email` varchar(50) NOT NULL default '',
	  PRIMARY KEY  (`id`)
	)";

	$sql4 = "INSERT INTO `mod_support_departments_[iid]` VALUES ('', 1, 'Sales', 'sales@company.com');";
	$sql5 = "INSERT INTO `mod_support_departments_[iid]` VALUES ('', 2, 'Support', 'support@company.com');";

	$DISupportTicketName		= 'dyn_support_ticket_'.$instanceId;
	$DISupportMessageName		= 'dyn_support_message_'.$instanceId;
	$DISupportReplyTemplateName	= 'dyn_support_reply_template_'.$instanceId;
	$DINameDepartments			= 'dyn_support_departments_'.$instanceId;

	$SupportTicketStructure = array (
		'table'	=> 'mod_support_tickets_',
		'label'	=> 'Support Tickets',
		'order'	=> 'date',
		'fields'	=> array(
			'id'				=> LiskType::TYPE_HIDDEN,
			'date'				=> LiskType::TYPE_HIDDEN,
			'ticket_id'			=> LiskType::TYPE_HIDDEN,
			'department'		=> LiskType::TYPE_HIDDEN,
			'require_reply'		=> array(
				'type'				=> LiskType::TYPE_HIDDEN,
				'def_value'			=> 1
			),
			'from_first_name'	=> LiskType::TYPE_INPUT,
			'from_last_name'	=> LiskType::TYPE_INPUT,
			'from_email'		=> LiskType::TYPE_INPUT,
			'message'			=> LiskType::TYPE_TEXT
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

	$SupportMessageStructure = array (
		'table'	=> 'mod_support_messages_',
		'label'	=> 'Support Messages',
		'order'	=> 'date',
		'fields'	=> array(
			'id'				=> LiskType::TYPE_HIDDEN,
			'date'				=> LiskType::TYPE_HIDDEN,
			'ticket_id'			=> LiskType::TYPE_HIDDEN,
			'is_client'			=> LiskType::TYPE_HIDDEN,
			'name'				=> LiskType::TYPE_INPUT,
			'message'			=> LiskType::TYPE_TEXT,
		),
		'redefine_date'	=> array(
			'date'			=> array(
				'type'			=> LiskType::TYPE_DATETIME,
				'def_value'		=> 'sql:NOW()'
			)
		)
	);

	$ReplyTemplateStructure = array(
		'table'  => 'mod_support_reply_templates_',
		'order'  => 'oder',

		'fields' => array(
			'id'		 	=> LiskType::TYPE_HIDDEN,
			'template_type'	=> array(
				'type'			=> LiskType::TYPE_LIST,
				'object'		=> array(
									0	=> 'Replay',
									1	=> 'Paragraph'
								)
			),
			'oder'			=> LiskType::TYPE_HIDDEN,
			'name' 			=> array(
				'type'  		=> LiskType::TYPE_INPUT,
				'label' 		=> 'Name',
				'check' 		=> 'pre:empty',
			),
			'content'		=> array(
				'type'			=> LiskType::TYPE_TEXT,
				'label'			=> 'Tempalte content'
			)
		),
		'label'			=> 'Reply Template',
		'list_fields'	=> 'name,template_type'
	);

	$DepartmentStructure = array(
		'table'	=> 'mod_support_departments_',
		'order'	=> 'oder',
		'fields'	=> array(
			'id' => 'hidden',
			'oder' => 'hidden',
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
		'render'				=> 1,
		'installed_path'		=> $path,
		'new_contact_notify'	=> 1,
		'new_reply_notify'		=> 1
	);

	// update config
	$Db->Update("id=$instanceId",array(
		'config'	=>serialize($config)
	),'sys_modules');


	// install ticket DI
	$SupportTicketStructure['table'].=$instanceId;
	$App->InstallDI($DISupportTicketName,$SupportTicketStructure);

	// install messages DI
	$SupportMessageStructure['table'].=$instanceId;
	$App->InstallDI($DISupportMessageName,$SupportMessageStructure);

	// install reply tempalte DI
	$ReplyTemplateStructure['table'].=$instanceId;
	$App->InstallDI($DISupportReplyTemplateName,$ReplyTemplateStructure);

	//install detpartments DI
	$DepartmentStructure['table'] = 'mod_support_departments_'.$instanceId;
	$App->InstallDI($DINameDepartments,$DepartmentStructure);

	//install SQL
	$sqlToInstall = array($sql1,$sql2,$sql3,$sql4,$sql5,$sql6,$sql7,$sql8,$sql9,$sql10);
	foreach ($sqlToInstall as $sql) {
		$sql = Format::String($sql,array(
			'iid'	=> $instanceId,
			'path'	=> $path
		));
		$Db->Query($sql);
	}

	$installPath 		= $App->sysRoot.'init/modules/support_form/install/';

	// copy templates files
	$sourceFolder		= $installPath.'tpl/';
	$destinationFolder	= $App->sysRoot.'tpl/modules/support_'.$instanceId;
	$FileSystem->CopyDir($sourceFolder,$destinationFolder);

}

function uninstallSupportModule($instanceId)
{
	GLOBAL $Db,$App,$FileSystem;

	// drop tables sql
	$sql1 = "DROP TABLE `mod_support_tickets_[iid]` ";
	$sql4 = "DROP TABLE `mod_support_messages_[iid]` ";
	$sql2 = "DELETE FROM sys_email WHERE id='support_[iid]'";
	$sql6 = "DELETE FROM sys_email WHERE id='support_notification_[iid]'";
	$sql3 = "DROP TABLE `mod_support_departments_[iid]` ";
	$sql5 = "DROP TABLE `mod_support_reply_templates_[iid]` ";

	//install SQL
	$sqlToInstall = array($sql1,$sql2,$sql3,$sql4,$sql5,$sql6);
	foreach ($sqlToInstall as $sql) {
		$sql = Format::String($sql,array(
			'iid'	=> $instanceId
		));
		$Db->Query($sql);
	}

	// data items
	$DISupportTicketName	= 'dyn_support_ticket_'.$instanceId;
	$DISupportMessageName	= 'dyn_support_message_'.$instanceId;
	$DINameDepartments		= 'dyn_support_departments_'.$instanceId;
	$DISupportReplyTemplateName	= 'dyn_support_reply_template_'.$instanceId;

	//uninstall dataitems
	$App->UninstallDI($DISupportMessageName);
	$App->UninstallDI($DISupportTicketName);
	$App->UninstallDI($DISupportReplyTemplateName);
	$App->UninstallDI($DINameDepartments);

	//delete templates folder
	$tplFolder	= $App->sysRoot.'tpl/modules/support_'.$instanceId;
	$FileSystem->DeleteDir($tplFolder);
}
?>