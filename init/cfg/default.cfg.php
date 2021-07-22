<?php

/************************ DEFAULT DO NOT REMOVE *******************************/

$DATA_SYSTEM_FOOTER = array(
	'table'	=> 'sys_footer',
	'label'	=> 'Footer Block',
	'order'	=> 'oder',
	'fields' => array (
        'id' => LiskType::TYPE_HIDDEN,
		'oder' => LiskType::TYPE_HIDDEN,
		'name' => array(
			'label' => 'Name',
			'type' => LiskType::TYPE_INPUT,
			'check' => 'empty',
		),
		'content'	=> array(
			'type' => LiskType::TYPE_TEXT,
			'label' => 'Content',
		    'strip_tags' => false,
		),
	)
);

$DATA_EMAIL = array(
	'table' => 'sys_email',
	'label' => 'Email Template',
	'order' => 'id',
	'fields' => array(
		'id'          => LiskType::TYPE_HIDDEN,
		'recipients'  => LiskType::TYPE_TEXT,
		'subject'     => LiskType::TYPE_INPUT,
		'body'        => LiskType::TYPE_TEXT,
		'from_header' => array(
            'type' => LiskType::TYPE_INPUT,
            'strip_tags' => false,
        ),
		'content_type_header' => array(
			'type' => LiskType::TYPE_LIST,
			'object' => 'def_content_type'
		),
	),
	'redefine_edit' => array(
		'body_html' => LiskType::TYPE_HTML,
	),
	'redefine_update' => array(
		'body' => LiskType::TYPE_HTML,
	),
	'redefine_add' => array(
		'id' => array(
	        'type' => LiskType::TYPE_INPUT,
	        'check' => 'reg:^([0-9A-z_]+)$'
	    ),
	),
	'list_fields' => 'id'
);

$TREE_CONTENT = array(
	'name' => 'Content Blocks',
	'node' => 'content_node',
	'point' => 'content',
	'max_level' => 4,
);

$DATA_CONTENT_NODE = array(
	'table'	=> 'sys_content_categories',
	'label'	=> 'Category',
	'order'	=> 'oder',
	'fields' => array(
		'id' => LiskType::TYPE_HIDDEN,
		'parent_id' => array(
			'type' => LiskType::TYPE_CATEGORY,
			'label' => 'Category',
			'object' => 'content',
		),
		'parents' => LiskType::TYPE_HIDDEN,
		'url' => LiskType::TYPE_HIDDEN,
		'oder' => LiskType::TYPE_HIDDEN,
		'name' => array(
			'label' => 'Name',
			'type' => LiskType::TYPE_INPUT,
			'check' => 'empty',
		),
	)
);


$DATA_CONTENT = array (
	'table'	=> 'sys_content',
	'label'	=> 'Content Block',
	'order'	=> 'oder',
	'fields' => array (
		'id' => LiskType::TYPE_HIDDEN,
		'parent_id' => array(
			'type' => LiskType::TYPE_CATEGORY,
			'label' => 'Category',
			'object' => 'content',
		),
		'parents' => LiskType::TYPE_HIDDEN,
		'url' => LiskType::TYPE_HIDDEN,
		'oder' => LiskType::TYPE_HIDDEN,
		'key' => array(
			'type' => LiskType::TYPE_INPUT,
			'label' => 'Key',
			'check' => 'empty',
		),
		'name' => array(
			'label' => 'Label',
			'type' => LiskType::TYPE_INPUT,
			'check' => 'empty',
		),
		'content'	=> array(
			'type' => LiskType::TYPE_HTML,
			'label' => 'Content',
		),
	)
);


$DATA_PAGING = array(
	'table'	=> 'sys_paging',
	'order'	=> 'name',
	'fields' => array(
		'name'        => LiskType::TYPE_HIDDEN,
		'items_per_page' => array(
			'type'  => LiskType::TYPE_INPUT,
			'hint'  => 'Number of entries displayed on one page. Set to zero to display all entries',
			'label' => 'Entries Per Page'
		),
		'pages_per_page' => array(
			'type' => LiskType::TYPE_INPUT,
			'hint' => 'Number of pages displayed on the paging line'
		),
		'paging_type' => array(
			'type' => LiskType::TYPE_LIST,
			'object' => array(
		        Paging::TYPE_STANDART => 'Standart',
		        Paging::TYPE_EXTENDED => 'Extended',
		    ),
		),
	),
	'label' => 'Paging Setting'
);

// content type for "text"
$LIST_CONTENT_TYPE = array(
	'0'	=> 'text',
	'1'	=> 'html'
);

$LIST_HOURS = array(
	0	=> '12 am',
	1	=> '1 am',
	2	=> '2 am',
	3	=> '3 am',
	4	=> '4 am',
	5	=> '5 am',
	6	=> '6 am',
	7	=> '7 am',
	8	=> '8 am',
	9	=> '9 am',
	10	=> '10 am',
	11	=> '11 am',
	12	=> '12 pm',
	13	=> '1 pm',
	14	=> '2 pm',
	15	=> '3 pm',
	16	=> '4 pm',
	17	=> '5 pm',
	18	=> '6 pm',
	19	=> '7 pm',
	20	=> '8 pm',
	21	=> '9 pm',
	22	=> '10 pm',
	23	=> '11 pm'
);

$LIST_HOURS_12 = array(
	1	=> '1',
	2	=> '2',
	3	=> '3',
	4	=> '4',
	5	=> '5',
	6	=> '6',
	7	=> '7',
	8	=> '8',
	9	=> '9',
	10	=> '10',
	11	=> '11',
	0	=> '12',
);

$LIST_HOURS_12_SUFFIX = array(
    'am' => 'am',
    'pm' => 'pm',
);

$LIST_MONTH = array(
	'01' => 'January',
	'02' => 'February',
	'03' => 'March',
	'04' => 'April',
	'05' => 'May',
	'06' => 'June',
	'07' => 'July',
	'08' => 'August',
	'09' => 'September',
	'10' => 'October',
	'11' => 'November',
	'12' => 'December'
);

$DEFAULT_PAGING	= array(
	'system' => array(
		'items_per_page' => 15,
		'pages_per_page' => 5,
		'paging_type' => Paging::TYPE_STANDART
	),
);

?>