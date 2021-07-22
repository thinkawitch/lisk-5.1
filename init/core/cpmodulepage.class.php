<?php
/**
 * CLASS CPModulePage
 * @package lisk
 *
 */
class CPModulePage extends CPPage
{

	public $instanceIdRequired;

	public $iid;

	public $settingsFields;
	public $settingsFieldsValues;
	public $customizableDI;


	function __construct($instanceIdRequired=false)
	{
		parent::__construct();

		$this->instanceIdRequired = $instanceIdRequired;
		$this->InitInstanceId();

		$this->back = isset($_SESSION['SYS_NAV_cms_module_back']) ? $_SESSION['SYS_NAV_cms_module_back'] : 0;
		$this->setBack = $this->back + 1;
		
		//TODO, peredelat'
		if (@$_GET['action'] != 'get_snippet') $this->SetBack();
	}

	private function InitInstanceId()
	{
		if ($this->instanceIdRequired)
		{
			if (isset($_GET['iid']))
			{
				$_SESSION['sys_iid'] = $_GET['iid'];
				$_SESSION['SYS_NAV_cms_module_back'] = isset($_GET['back']) ? $_GET['back'] : 0;
			}

			$this->iid = $_SESSION['sys_iid'];

			if ($this->iid < 1)
			{
				GLOBAL $App;
				$App->RaiseError('Instance ID is required but not has been passed');
			}
		}
	}

	public function RenderSystemInfoBlock(LiskModule $module)
	{
		GLOBAL $Parser;
		return $Parser->MakeView(array(
			'iid'		=> $module->iid,
			'version'	=> $module->version,
			'tpl_path'	=> $module->tplPath
		), 'cms/modules/system_info');
	}

	public function RenderSystemConfigBlock(LiskModule $module)
	{
		GLOBAL $Parser;
		$rez = array();
		if (Utils::IsArray($module->config))
		foreach ($module->config as $key=>$value)
		{
			$rez[] = array(
				'key'	=> $key,
				'value'	=> nl2br(print_r($value, true))
			);
		}
		return $Parser->MakeList($rez, 'cms/modules/system_config');
	}

	public function RenderSystemCustomizationBlock()
	{
		GLOBAL $Parser;
		$diArr = $this->customizableDI;
		$rez = array();
		if (Utils::IsArray($diArr))
		{
			foreach ($diArr as $diName)
			{
				$obj = Data::Create($diName, false);
				$rez[] = array(
					'name'	=> $diName,
					'label'	=> $obj->label,
					'back'	=> $this->setBack
				);
			}
		}
		return $Parser->MakeList($rez, 'cms/modules/system_customization');
	}

	private function RenderSnippetsBlock(LiskModule $module)
	{
		GLOBAL $Parser;
		$snippets = $module->GetAvailableSnippets();
		return $Parser->MakeList($snippets, 'cms/modules/module_snippets', 'list');
	}

	public function RenderSettingsPage(LiskModule $module)
	{
		GLOBAL $Parser;

		if (Utils::IsArray($this->settingsFields))
		{
			$GLOBALS['DATA_TEMP'] = array(
				'fields'	=> $this->settingsFields
			);
			$obj = Data::Create('temp');
			$obj->value = $this->settingsFieldsValues;

			$Parser->SetCaptionVariables(array(
				'sys_info'	=> $this->RenderSystemInfoBlock($module),
				'sys_conf'	=> $this->RenderSystemConfigBlock($module),
				'sys_custom'=> $this->RenderSystemCustomizationBlock(),
				'snippets'	=> $this->RenderSnippetsBlock($module)
			));
			return $Parser->MakeDynamicForm($obj, 'cms/modules/settings');
		}
		else
		{
			// no public settings
			return $Parser->MakeView(array(
				'sys_info'	=> $this->RenderSystemInfoBlock($module),
				'sys_conf'	=> $this->RenderSystemConfigBlock($module),
				'sys_custom'=> $this->RenderSystemCustomizationBlock(),
				'snippets'	=> $this->RenderSnippetsBlock($module)
			), 'cms/modules/settings_empty', 'dynamic_form');
		}
	}

	public function ParseModuleBack()
	{
		$this->backLink = $this->Parser->MakeView(array(
			'url' => $this->moduleBack
		), 'cms/blocks', 'back_link');
	}

	public function ShowSnippetCode($code)
	{
		GLOBAL $Parser,$App;
		$this->SetGlobalTemplate(0);
		$App->debug = false;
		
		echo $Parser->MakeView(array(
			'code' => htmlspecialchars($code),
		), 'cms/modules/get_snippet_code', 'view');
	}
}

?>