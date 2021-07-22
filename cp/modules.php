<?php
require_once('init/init.php');

$GLOBALS['DATA_TEMP_INSTALLED_MODULES'] = array(
	'table' => 'sys_modules',
	'label' => 'Installed Modules',
	'fields' => array(
		'id' => array(
			'type' => LiskType::TYPE_INPUT,
			'label' => 'InstanceId',
		),
		'object_name' => array(
			'type' => LiskType::TYPE_INPUT,
			'label' => 'Module',
		),
		'name' => array(
			'type' => LiskType::TYPE_INPUT,
		),
	),

	'list_fields' => 'object_name,name',
);

class CpModulesPage extends CPPage
{
    private $selfUri = 'modules.php?z=x';
    
	function __construct()
	{
		parent::__construct();

		$this->titlePicture = 'cms/modules_management/uho.gif';

		$this->AddBookmark('Installed modules', '?action=installed', 'img/cms/modules_management/ico_installed.gif');
		$this->AddBookmark('Available modules', '?action=available', 'img/cms/modules_management/ico_available.gif');

		$this->SetGetAction('available', 'ListAvailable');

		$this->SetGetAction('install', 'Install');
		$this->SetGetAction('uninstall', 'Uninstall');
	}

	function CheckIfDeveloper()
	{
		GLOBAL $Auth;
		if ($Auth->user['level'] != LISK_GROUP_DEVELOPERS) Navigation::Jump('developers_only.php');
	}

	function Page()
	{
		GLOBAL $Parser, $Db, $Paging;
		$this->SetTitle('Installed Modules');
		
		$this->SetBack();
		if ($this->setBack>0) $this->ParseBack();
		
		$this->currentBookmark = 'Installed modules';

		$this->Paging->SwitchOn('cp');
		$list = $Db->Select(null, 'id', null, 'sys_modules');
		$caption['paging'] = $Paging->Render();
		$this->Paging->SwitchOff();

		if (Utils::IsArray($list))
		{
			foreach ($list as $k=>$v)
			{
				$scmsInfo = $Db->Get("instance_id='{$v['id']}'", 'name,url', 'sys_ss');
				$list[$k]['_page_name'] = $scmsInfo['name'];
				$list[$k]['path'] = '/'.$scmsInfo['url'];
			}
		}

		$Parser->SetListDecoration('ListTD1', 'ListTD2');
		$Parser->SetAddVariables(array('back'=>$this->back));
		$Parser->SetCaptionVariables($caption);
		$this->pageContent .= $Parser->MakeList($list, 'cms/modules/list_installed_modules', 'list');
	}

	function ListAvailable()
	{
		GLOBAL $Parser,$Db;

		$this->currentBookmark = 'Available modules';
		$this->SetTitle('Available Modules');

		$CMSSS = new CMSContentTree();
		$modules = $CMSSS->GetAvailableModules();
		
		foreach($modules as $key=>$row)
		{
			$modules[$key]['multiinstance'] = ($row['multiinstance']) ? 'yes' : 'no';
			$modules[$key]['ssintegrated'] = (!$row['ss_integrated']) ? 'no' : 'yes';

			if (!$row['ss_integrated'])
			{
				$moduleId = $Db->Get("name='{$row['sys_name']}'", 'id', 'sys_modules');
				if ($moduleId > 0)
				{
					$modules[$key]['uninstall'] = $moduleId;
				}
				else
				{
					$modules[$key]['install'] = $row['sys_name'];
				}
			}
		}
		$Parser->SetListDecoration('ListTD1', 'ListTD2');
		$this->pageContent .= $Parser->MakeList($modules, 'cms/modules/list_available_modules', 'list');
	}

	function Install()
	{
		GLOBAL $App;

		$this->CheckIfDeveloper();
		
		// check file permissions
		if (!LiskModule::IsInstallPossible())
		{
		    $this->SetError('Module can\'t be installed. Please check file permissions!');
		    Navigation::Jump($this->selfUri.'&action=available');
		}

		$moduleName = $_GET['module'];

		// load module
		$App->Load($moduleName);
		
		//which installation step
		$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
		
		$moduleInfo = $GLOBALS[strtoupper($moduleName).'_MODULE_INFO'];

		// create object instance
		$module = new $moduleInfo['object_name']();

		//install/configurate module
		$module->Install(array(
			'path'	=> '',
			'step'	=> $step,
		));

		Navigation::Jump($this->selfUri.'&action=installed');
	}

	function Uninstall()
	{
		GLOBAL $App;
		
		$this->CheckIfDeveloper();

		$iid = $_GET['module_id'];

		$module = $App->GetModuleInstance($iid);
		$module->Uninstall();

		Navigation::Jump($this->selfUri.'&action=installed');
	}
}

$Page = new CpModulesPage();
$Page->Render();

?>