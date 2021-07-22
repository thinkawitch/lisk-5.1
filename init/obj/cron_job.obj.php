<?php

$GLOBALS['DATA_CRON_JOBS'] = array(
	'table'	=> 'sys_cron_jobs',
	'order'	=> 'id DESC',
	'fields'	=> array(
		'id'		=> LiskType::TYPE_HIDDEN,
		'name'		=> array(
            'type' => LiskType::TYPE_INPUT,
            'check' => 'empty',
        ),
		'path' => LiskType::TYPE_INPUT,
		'last_run' => LiskType::TYPE_DATETIME,
		'periodicity' => LiskType::TYPE_INPUT,
		'object' => LiskType::TYPE_INPUT,
		'method' => LiskType::TYPE_INPUT,
		'status' => array(
			'type' => LiskType::TYPE_LIST,
			'object' => array(
				CronJob::STATUS_ENABLED	=> 'enabled',
				CronJob::STATUS_DISABLED => 'disabled'
        	)
	    )
	),
	'list_fields' => 'name,path,last_run,periodicity,object,method,status'
);

class CronJob
{
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
}

?>