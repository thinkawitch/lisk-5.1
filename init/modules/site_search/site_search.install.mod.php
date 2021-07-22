<?php

function installSiteSearchModule($instanceId, $path)
{
	GLOBAL $Db,$App,$FileSystem;
	
	$config = array(
		'items_per_page' => 20,
		'pages_per_page' => 10,
	);

	// update config
	$Db->Update("id=$instanceId",array(
		'config'	=>serialize($config)
	),'sys_modules');

	$installPath = $App->sysRoot.'init/modules/site_search/install/';

	//copy templates
	$FileSystem->CopyDir($installPath.'tpl/', $App->sysRoot.'tpl/modules/site_search/');

}

function uninstallSiteSearchModule($instanceId)
{
	GLOBAL $App,$FileSystem;
	
	//delete tpl files
	$FileSystem->DeleteDir($App->sysRoot.'tpl/modules/site_search/');
}

?>