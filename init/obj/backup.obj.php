<?php

define('BACKUP_FILES',  1);
define('BACKUP_DB', 	2);
define('BACKUP_SOURCE', 3);

define('BACKUP_TYPE_BACKUP',  1);  //regular backup
define('BACKUP_TYPE_PUBLISH', 2);  //publishing archive, can be used as backup too

$LIST_BACKUP = array(
	BACKUP_DB 		=> LANG_BACKUP_DATABASE,
	BACKUP_FILES	=> LANG_BACKUP_FILESIMAGES,
	BACKUP_SOURCE	=> LANG_BACKUP_SOURCECODE,
);

$DATA_BACKUP_OPTIONS = array(
	'fields'	=> array(
		'option' => array(
			'label'		=> LANG_BACKUP_BACKUPOPTIONS,
			'type'		=> LiskType::TYPE_PROP,
			'object'	=> 'def_backup',
		),
	)
);


$DATA_BACKUP = array(
	'fields'	=> array(
		'Backup' => array(
			'label' => LANG_BACKUP_BACKUPWHAT,
			'type' => LiskType::TYPE_RADIO,
			'object' => 'def_backup',
		),
	)
);

$DATA_INSTALL = array(
	'fields'	=> array(
		'file' => array(
			'label' => LANG_BACKUP_UPLOADARCHIVE,
			'type' => LiskType::TYPE_FILE,
			'path' => $GLOBALS['App']->backupPath,
		),
	)
);



$DATA_BACKUP = array(
    'table' => 'sys_backup',
    'order' => 'id DESC',
    'fields' => array(
        'id' => LiskType::TYPE_HIDDEN,
        'date' => LiskType::TYPE_DATETIME,
        'filename' => LiskType::TYPE_INPUT,
        'type' => array(
        	'type' => LiskType::TYPE_LIST,
            'object' => array(
                BACKUP_TYPE_BACKUP => 'backup',
                BACKUP_TYPE_PUBLISH => 'publish',
            ),
        ),
        'description' => array(
        	'type' => LiskType::TYPE_TEXT,
        )
    ),
);

?>