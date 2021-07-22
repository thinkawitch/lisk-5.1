<?php

define('LANG_BACKUP_DATABASE',			'Database');
define('LANG_BACKUP_FILESIMAGES',		'Files & Images');
define('LANG_BACKUP_SOURCECODE',		'Source Code');
define('LANG_BACKUP_BACKUPOPTIONS',		'Backup options:');
define('LANG_BACKUP_BACKUPWHAT',		'Backup What');
define('LANG_BACKUP_UPLOADARCHIVE',		'Upload archive file or install from list below');
define('LANG_BACKUP_BACKUP',			'Backup');
define('LANG_BACKUP_RESTORE',			'Restore');

$GLOBALS['LANGUAGE_BACKUP'] = array(

// BACKUP LIST
	'download'						=> 'Download',
	'delete'						=> 'Delete',
	'no_backups'					=> 'No backups.',
	'install'						=> 'Install',
	'date'							=> 'Date',
	'options'						=> 'Options',
	'size'							=> 'Size',
	'backup'						=> 'Backup',
	'upload'						=> 'Upload',
	
	//hints
	'dld_backup_file'				=> 'Download backup file',
	'del_backup_file'				=> 'Delete backup file',
	'install_backup'				=> 'Install backup file (Restore)',
	//msgboxes
	'del_file_confirm'				=> 'Are you sure that you want to delete this file?',
	'install_backup_confirm'		=> 'You are about to install the backup.\nAll current files, images & databases will be replaced with the data from the backup file.\nAre you sure?',
	
// BACKUP
	'upload_backup_file'			=> 'Upload backup file to the server :',
	//hints
	'c_new_backup'					=> 'Create new backup with selected options'
	
);

?>