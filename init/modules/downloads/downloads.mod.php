<?php

$GLOBALS['DOWNLOADS_MODULE_INFO'] = array(
	'name'			=> 'Downloads',
	'sys_name'		=> LiskModule::MODULE_DOWNLOADS,
	'version'		=> '5.0',
	'description'	=> 'Downloads',
	'object_name'	=> 'Downloads',
	'multiinstance'	=> true,
	'ss_integrated'	=> true
);

class DownloadsDI extends Data
{
	function __construct($diName, $initFields=true)
	{
		parent::__construct($diName, $initFields, 'Obj_DownloadsDI_di_'.$diName);
	}

	function TgerBeforeInsert(&$newValues)
	{
		$newValues['file_id'] = strtoupper(md5(uniqid('')));
	}
}

class Downloads extends LiskModule
{
	
	public $confDIName;

	//paging
	public $confPagingItemsPerPage;
	public $confPagingPagesPerPage;

	public $tplPath = 'modules/downloads_';

	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_DOWNLOADS;
		if ($instanceId!=null) $this->Init($instanceId);
	}

	function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->version = $GLOBALS['DOWNLOADS_MODULE_INFO']['version'];

		$this->tplPath .= $instanceId.'/';
		$this->confDIName = $this->config['di_name'];
		$this->confPagingItemsPerPage = $this->config['items_per_page'];
		$this->confPagingPagesPerPage = $this->config['pages_per_page'];
		$this->Debug('confDIName', $this->confDIName);
	}

	function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['di_name'] = $this->confDIName;
		$this->config['items_per_page'] = $this->confPagingItemsPerPage;
		$this->config['pages_per_page'] = $this->confPagingPagesPerPage;

		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/downloads/downloads.install.mod.php', 1);
		installDownloadsModule($instanceId, $params['path']);
	}

	function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/downloads/downloads.install.mod.php', 1);
		uninstallDownloadsModule($this->iid);
		parent::Uninstall();
	}

	function Render()
	{
		GLOBAL $Parser, $Paging,$Page,$App;

		if (preg_match('/^[A-Z0-9]+$/', @$Page->parameters[0]))
		{
			$fileId = $Page->parameters[0];
			$GLOBALS['url'] = str_replace($Page->parameters[0].'/', '', $GLOBALS['url']);
			$this->GetFile($fileId);
			$App->Destroy();
		}

		$Paging->SwitchOn('system');
		$Paging->SetItemsPerPage($this->confPagingItemsPerPage);
		$Paging->pagesPerPage = $this->confPagingPagesPerPage;

		$DI = new DownloadsDI($this->confDIName, true);
		$DI->Select();

		$Parser->SetCaptionVariables(array(
			'paging' => $Paging->Render()
		));

		$Parser->SetListDecoration('down1', 'down2');
		return $Parser->MakeList($DI, $this->tplPath.'downloads', 'list');
	}

	private function GetFile($fileId)
	{
		$DI = new DownloadsDI($this->confDIName, true);
		$DI->Get('file_id='.Database::Escape($fileId));

		if (Utils::IsArray($DI->value))
		{
			$fileName = $DI->value['file'];
			$file = $DI->fields['file']->path.$fileName;
            
			if (strlen($fileName) && file_exists($file))
			{
				// update downloads quantity
				$DI->Update("id=".$DI->value['id'],array(
					'downloads'	=> 'sql:downloads+1'
				));
				
				StatActionHandler::Set('STAT_OBJECT_DOWNLOAD', 'STAT_OBJECT_DOWNLOAD_DOWNLOAD');
				
				// return file
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private", false);
				header("Content-Type: application/octet-stream");
				header('Content-Disposition: attachment; filename="'.basename($file).'";');
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: ".filesize($file));
				readfile($file);
			}
			else
			{
				header('Status: 404 Not Found', true, 404);
				header('HTTP/1.0 404 Not Found', true, 404);
				exit();
			}

		}
		else
		{
			header('HTTP/1.0 404 Not Found', true, 404);
			exit();
		}
	}
	
    public function AvailableSnippets()
	{
		return array(
			'list' => array(
				'description' => 'Display downloads list',
				'code' => '<lisk:snippet src="module" instanceId="[iid]" name="list" />',
			),
		);
	}
	
    public function Snippet($params)
	{
		switch (strtolower($params['name']))
		{
			case 'list':
				return $this->RenderSnippetList();
				break;
		}
		return '';
	}
	
	public function RenderSnippetList()
	{
	    GLOBAL $Parser, $Page, $App;
	    
	    $p1 = isset($Page->parameters[0]) ? $Page->parameters[0] : null;
	    if ($p1!=null && preg_match('/^[A-Z0-9]+$/', $p1))
		{
			$fileId = $Page->parameters[0];
			$GLOBALS['url'] = str_replace($p1.'/', '', $GLOBALS['url']);
			$this->GetFile($fileId);
			$App->Destroy();
		}
	    
	    $di = new DownloadsDI($this->confDIName, true);
		$di->Select();

		$Parser->SetListDecoration('down1', 'down2');
		return $Parser->MakeList($di, $this->tplPath.'snippets', 'list');
	}
}

?>