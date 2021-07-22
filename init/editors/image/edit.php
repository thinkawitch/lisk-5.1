<?php

define ('ROOT_PATH',	'../../../');
require_once("../../../init/init.php");

// lisk3 code here for now
//if (!empty($filename)) {
//	$filename = ROOT_PATH.urldecode($filename);
//} else if ($type!='' and $id!='') {
//	if ($key=='') { $key = 0; }
//	eval('$IMAGE = $GLOBALS[IMAGE_'.strtoupper($type).'];');
//	$params = $IMAGE[images][$key];
//	$path = ROOT_PATH.FILE_PATH.$IMAGE[path].$params[path];
//	$src = $id.'_'.$key;
//	$filename = $path.$src;
//}

class EditPage extends Page
{
	function __construct()
	{
		parent::__construct();
		$this->App->debug = false;
		$this->SetGlobalTemplate('0');
		$this->LoadTemplate('edit');
	}

	function Page()
	{
		$filename = ROOT_PATH.urldecode($_REQUEST['filename']);
		
		$this->Tpl->SetVariable(
			array(
				'FILENAME'	=> $filename,
				'SAVENAME'	=> urlencode($filename)
			)
		);
	}
}

$EditPage = new EditPage();
$EditPage->Render();
?>