<?php
//SCS.PHP
	define('LANG_CP_SCMS_DEVMODE',		'����� ������������');
	define('LANG_CP_SCMS_ADMMODE',		'����� ��������������');
	define('LANG_CP_SCMS_SSTRUCT',		'��������� �����');
	define('LANG_CP_SCMS_ADD',			'���������� ');
	define('LANG_CP_SCMS_EDIT',			'�������������� ');

	//ERROR MESSAGES
	define('LANG_CP_SCMS_ERRINCPWD',	'������. ����������� ������. ���������� �� ���.');
	define('LANG_CP_SCMS_ERRCDELPS',	'������. �� �� ������ ������� ��� ������ �������. ���������� ������� ������� �����������.');

//SCMS.CSM.PHP
	define('LANG_CP_SCMS_ADDPAGE',		'�������� ��������');
	define('LANG_CP_SCMS_ADDMOD',		'�������� ������');
	define('LANG_CP_SCMS_ORDER',		' ���������� ');
	define('LANG_CP_SCMS_ECONT',		'�������������');
	define('LANG_CP_SCMS_DELETE',		'�������');
	define('LANG_CP_SCMS_ENTER',		'����');
	define('LANG_CP_SCMS_FLINK',		'������� ������');
	define('LANG_CP_SCMS_MANAGE',		'����������');
	define('LANG_CP_SCMS_NTEMPLATE',	'��� �������');



	//HINTS
	define('LANG_CP_SCMS_ADDNPAGE',		'�������� ����� ��������');
	define('LANG_CP_SCMS_ADDNMOD',		'�������� ����� ������');
	define('LANG_CP_SCMS_CORDER',		'�������� ������� ������');
	define('LANG_CP_SCMS_EPCONT',		'������������� ���������� ��������');
	define('LANG_CP_SCMS_DPAGE',		'������� ��������');
	define('LANG_CP_SCMS_ESECT',		'����� � ������');
	define('LANG_CP_SCMS_ONWIN',		'������� ������ � ����� ����');
	define('LANG_CP_SCMS_MCPAGE',		'���������� ����������������� ���������');
	define('LANG_CP_SCMS_MMSETS',		'���������� ����������� ������');


	//MESSAGES
	define('LANG_CP_SCMS_CDEL',			'�� �������, ��� ������ ������� ��� ��������?');



$GLOBALS['LANGUAGE_CP_SCMS'] = array(

//ADD PAGE
	'parent'							=> '������ ',
	'name'								=> '��� ',
	'access_level'						=> '������� ������� ',
	'page_type'							=> '��� �������� ',
	'is_locked'							=> '�������',
	'overview_page'						=> '�������� ��������',
	'scms_integrated'					=> 'SCMS �������� ',
	'url'								=> 'URL',
	'title'								=> '��������',
	'section_tpl'						=> '������ ������',
	'page_tpl'						    => '������ ��������',
	'site_handler'						=> '���� ���������� ������',
	'cp_handler'						=> '���������� ��',
	'hide_from_menu'					=> '�� ���������� � ����',
	//hints
	'save_rec'							=> '��������� ������.',
	'dnotsave_return'					=> '�� ��������� ������ � ��������� �� ���������� ��������.',

//LIST
	//message boxes
	'wanna_del_all'						=> '�� ������� ��� ������ ������� ��� ��������� ������?',

	//hints
	'del_selected'						=> '������� ��������� ��������.',
	'sel_dissel_all'					=> '��������/����� ��������� �� ���� ������� � ������.',
	'edit_pprop'						=> '������������� �������� ��������',

//DEVELOPER MODE
	'devmode_enter_pwd'					=> '��� ��������� ������� � ������ ������������ ������� ������',

	//hints
	'not_save_return'					=> '�������� ���� � ��������� �� ���������� ��������.',
	'save_record'						=> '����������� ������.'
);

?>