<?php

function installSitemapModule($instanceId,$path)
{
	GLOBAL $Db,$App,$FileSystem;
	
	$config = array(
		'base_url' => $path,
	);

	// update config
	$Db->Update("id=$instanceId",array(
		'config'	=>serialize($config)
	),'sys_modules');

	$installPath = $App->sysRoot.'init/modules/sitemap/install/';

	// copy templates
	$FileSystem->CopyDir($installPath.'tpl/', $App->sysRoot.'tpl/modules/sitemap_'.$instanceId.'/');
	
}

function uninstallSitemapModule($instanceId)
{
	GLOBAL $App,$FileSystem;

	//delete tpl files
	$FileSystem->DeleteDir($App->sysRoot.'tpl/modules/sitemap_'.$instanceId.'/');
}
?>