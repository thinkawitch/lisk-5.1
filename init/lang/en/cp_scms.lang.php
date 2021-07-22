<?php
//SCMS.PHP
	define('LANG_CP_SCMS_DEVMODE',			'Developer Mode');
	define('LANG_CP_SCMS_ADMMODE',			'Administrator Mode');
	define('LANG_CP_SCMS_SSTRUCT',			'Site Structure');
	define('LANG_CP_SCMS_SEARCH',			'Search');
	define('LANG_CP_SCMS_TREEVIEW',			'Tree View');
	define('LANG_CP_SCMS_ADD',				'Add ');
	define('LANG_CP_SCMS_EDIT',				'Edit ');

	//ERROR MESSAGES
	define('LANG_CP_SCMS_ERRINCPWD',		'Error. Incorrent password. Please try again.');
	define('LANG_CP_SCMS_ERRCDELPS',		'Error. You can not delete this Page Set. Please delete sub pages firstly.');

//SCMS.CMS.PHP
	define('LANG_CP_SCMS_ADDPAGE',			'Add Page');
	define('LANG_CP_SCMS_ADDMOD',			'Add Module');
	define('LANG_CP_SCMS_ORDER',			' Order ');
	define('LANG_CP_SCMS_ECONT',			'Edit content');
	define('LANG_CP_SCMS_DELETE',			'Delete');
	define('LANG_CP_SCMS_ENTER',			'Enter');
	define('LANG_CP_SCMS_FLINK',			'Follow link');
	define('LANG_CP_SCMS_MANAGE',			'Manage');
	define('LANG_CP_SCMS_NTEMPLATE',		'No template');



	//HINTS
	define('LANG_CP_SCMS_ADDNPAGE',			'Add new page');
	define('LANG_CP_SCMS_ADDNMOD',			'Add new module');
	define('LANG_CP_SCMS_CORDER',			'Change pages order');
	define('LANG_CP_SCMS_EPCONT',			'Edit page content');
	define('LANG_CP_SCMS_DPAGE',			'Delete the page');
	define('LANG_CP_SCMS_ESECT',			'Enter the section');
	define('LANG_CP_SCMS_ONWIN',			'Open link in a new window');
	define('LANG_CP_SCMS_MCPAGE',			'Manage custom page');
	define('LANG_CP_SCMS_MMSETS',			'Manage Module settings');


	//MESSAGES
	define('LANG_CP_SCMS_CDEL',			'Are you sure that you want to delete this page?');



$GLOBALS['LANGUAGE_CP_SCMS'] = array(

//ADD PAGE
	'parent'							=> 'Parent ',
	'name'								=> 'Name ',
	'access_level'						=> 'Access Level ',
	'page_type'							=> 'Page Type ',
	'is_locked'							=> 'Is Locked',
	'overview_page'						=> 'Overview Page',
	'scms_integrated'					=> 'SCMS Integrated ',
	'url'								=> 'URL',
	'title'								=> 'Title',
	'section_tpl'                       => 'Section template',
	'page_tpl'                       => 'Page template',
	'site_handler'						=> 'Site Handler',
	'cp_handler'						=> 'CP Handler',
	'hide_from_menu'					=> 'Don\'t display in menu',
	//hints
	'save_rec'							=> 'Save the record.',
	'dnotsave_return'					=> 'Do not save the record and return to the previous page.',

//LIST
	//message boxes
	'wanna_del_all'						=> 'Are you sure that you want to delete all selected records?',

	//hints
	'del_selected'						=> 'Delete selected pages.',
	'sel_dissel_all'					=> 'Select/Deselect all records in the list.',
	'edit_pprop'						=> 'Edit Page properties',

//DEVELOPER MODE
	'devmode_enter_pwd'					=> 'To gain access to developer mode please enter password',

	//hints
	'not_save_return'					=> 'Do not save the record and return to the previous page.',
	'save_record'						=> 'Save the record.'
);

?>