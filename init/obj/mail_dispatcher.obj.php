<?php

$DATA_EMAIL_QUEUE = array(
    'table' => 'sys_email_queue',
    'label' => 'Message',
	'order' => 'id ASC',
    'fields' => array(
        'id' => LiskType::TYPE_HIDDEN,
        'date' => array(
            'type' => LiskType::TYPE_DATETIME,
			'min_year' => 2011,
			'max_year' => 1 + date('Y'),
        ),
        'subject' => array(
            'type' => LiskType::TYPE_INPUT,
        ),
        'message' => array(
            'type' => LiskType::TYPE_TEXT,
        ),
    ),
    'redefine_cp_list' => array(
        'recipients' => LiskType::TYPE_INPUT,
    ),
    'list_fields' => 'date,subject'
);

$DATA_EMAIL_QUEUE_RECIPIENT = array(
    'table' => 'sys_email_queue_recipients',
    'label' => 'Message',
	'order' => 'id ASC',
    'fields' => array(
        'id' => LiskType::TYPE_HIDDEN,
		'parent_id' => LiskType::TYPE_HIDDEN,
        'email' => array(
            'type' => LiskType::TYPE_INPUT,
        ),
    ),
    'list_fields' => 'recipient'
);

class Email_Queue extends Data
{
    function __construct($initFields=true)
    {
        parent::__construct('email_queue', $initFields, 'Obj_email_queue');
    }
    
    public function TgerAfterDelete($cond, $values)
	{
	    if (!Utils::IsArray($values)) return true;
	    
	    $di = Data::Create('email_queue_recipient');
	    foreach ($values as $row)
	    {
	        $di->Delete('parent_id='.$row['id']);
	    }
	}
}


$DATA_EMAIL_HISTORY = array(
    'table' => 'sys_email_history',
    'label' => 'Message',
    'order' => 'id DESC',
    'fields' => array(
        'id' => LiskType::TYPE_HIDDEN,
        'date' => array(
            'type' => LiskType::TYPE_DATETIME,
        ),
        'subject' => array(
            'type' => LiskType::TYPE_INPUT,
        ),
        'message' => array(
            'type' => LiskType::TYPE_TEXT,
        ),
    ),
    'redefine_cp_list' => array(
        'recipients' => LiskType::TYPE_INPUT,
    ),
    'list_fields' => 'date,subject'
);

$DATA_EMAIL_HISTORY_RECIPIENT = array(
    'table' => 'sys_email_history_recipients',
    'label' => 'Message',
	'order' => 'id ASC',
    'fields' => array(
        'id' => LiskType::TYPE_HIDDEN,
		'parent_id' => LiskType::TYPE_HIDDEN,
        'email' => array(
            'type' => LiskType::TYPE_INPUT,
        ),
    ),
    'list_fields' => 'recipient'
);

class Email_History extends Data
{
    function __construct($initFields=true)
    {
        parent::__construct('email_history', $initFields, 'Obj_email_history');
    }
    
    public function TgerAfterDelete($cond, $values)
	{
	    if (!Utils::IsArray($values)) return true;
	    
	    $di = Data::Create('email_history_recipient');
	    foreach ($values as $row)
	    {
	        $di->Delete('parent_id='.$row['id']);
	    }
	}
}

$DATA_DISPATCHER_SETTINGS = array(
    'fields' => array(
        'mailer_type' => array(
            'type' => LiskType::TYPE_LIST,
            'object' => 'def_dispatcher_mailer_type',
            'label' => 'Send via'
        ),
    ),
);

$LIST_DISPATCHER_MAILER_TYPE = array(
    'phpmail' => 'php mail()',
    'sendmail' => 'MTA, sendmail',
);
?>