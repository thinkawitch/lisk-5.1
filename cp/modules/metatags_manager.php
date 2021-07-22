<?php
chdir('../');
require_once('init/init.php');

class CpMetatagsPage extends CPPage
{
	function __construct()
	{
		parent::__construct();

		$this->App->Load('metatags_manager','mod');

		$this->AddBookmark('Site pages', '?', 'img/modules/metatags_manager/foldl.gif');
		$this->AddBookmark('Presets', '?action=presets', 'img/modules/metatags_manager/foldr.gif');
		$this->AddBookmark('Site Structure', '?action=check_ss', 'img/modules/metatags_manager/foldl.gif');

		$this->SetGetAction('presets', 'Presets');
		$this->SetPostAction('delete_selected', 'DeleteSelectedPages');

		$this->SetGetAction('add', 'Add');
		$this->SetGetPostAction('add', 'submit', 'AddSubmit');

		$this->SetGetAction('check_ss', 'CheckSiteStructure');
	}


	function Page()
	{
		GLOBAL $Parser;
		$this->currentBookmark = 'Site pages';
		$this->SetTitle('Site pages', 'modules/metatags_manager/uho.gif');

		$this->App->Load('list', 'type');
		$Preset = new T_list(array(
			'object'	=> 'data_metatags_preset',
			'add_values'=> array(0 => 'Empty'),
			'name'		=> 'preset'
		));
		$Preset->value = 0;

		$addBlock	= $Parser->MakeView(array(
			'presets'	=> $Preset->RenderFormView()
		), 'modules/metatags_manager/metatags', 'add_block');

		$this->customLine = $addBlock;

		$List = new CMSList('metatags_page');
		$List->Init();
		$List->Load('metatags_manager', 'mod');
	
		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $List->Render();

		$this->SetBack();
	}

	function CheckSiteStructure()
	{
		GLOBAL $Parser,$Db;

		$this->currentBookmark = 'Site Structure';
		$this->SetTitle('Site Structure', 'modules/metatags_manager/uho.gif');
		$this->SetBack();

		$rows = $Db->Select(null, 'oder', 'id,parent_id,parents,name,url', 'sys_ss');

		$GLOBALS['siteStructureFormatted'] = array();

		$rows = $this->FormatSiteStructure(0, $rows);
		$rows = $this->CheckSiteStructureUrls($rows);

		$Parser->SetListDecoration('ListTD1', 'ListTD2');
		$this->pageContent .= $Parser->MakeList($rows, 'modules/metatags_manager/ss_check', 'list');

	}

	function FormatSiteStructure($parentId,$rows)
	{
		STATIC $rez = null;
		foreach ($rows as $row)
		{
			if ($row['parent_id'] == $parentId)
			{
				$rez[] = array(
					'id'	=> $row['id'],
					'name'	=> $row['name'],
					'level'	=> str_repeat("&nbsp;", Utils::TreeLevel($row['parents'])*6),
					'url'	=> '/'.$row['url']
				);
				$this->FormatSiteStructure($row['id'], $rows);
			}

		}
		return $rez;
	}

	function CheckSiteStructureUrls($rows)
	{
		$Metatags = Data::Create('metatags_page');

		foreach ($rows as $key=>$row)
		{
			$url = $row['url'];

			$metaInfo = $Metatags->GetValue("name='$url'", 'id,title');
			if (Utils::IsArray($metaInfo))
			{
				$rows[$key]['meta'] = '<span style="color:#009900">defined</span>';
				$rows[$key]['meta_id'] = $metaInfo['id'];
			}
			else
			{
				$rows[$key]['meta'] = '<span style="color:#FF0000">undefined</span>';
			}
		}

		return $rows;
	}

	function DeleteSelectedPages()
	{
		$List = new CMSList('metatags_page');
		$List->Init();
		$List->DeleteSelected();
		Navigation::JumpBack($this->back);
	}

	function Presets()
	{
		$this->currentBookmark = 'Presets';

		$this->SetTitle('Metatags Presets', 'modules/metatags_manager/uho.gif');

		$this->SetBack();

		$List = new CMSList('metatags_preset');
        $List->Init();
		$List->Load('metatags_manager', 'mod');
		$List->MakeLinkButtons();

		// initialize paging
		$this->Paging->SwitchOn('cp');

		$List->RemoveButton('Delete', '[id]==1');
		$List->RemoveButton('<input type="checkbox" name="ids[]" value="[id]" />', '[id]==1');

		$this->pageContent .= $List->Render();
	}

	function Add()
	{
		$this->bookmarks = array();

		$Add = new CMSAdd('metatags_page');
		$this->SetTitle('Add Metatags', 'cms/add/uho.gif');

		if ($_GET['preset'] != 0)
		{
			$Preset = Data::Create('metatags_preset');
			$Preset->Get("id={$_GET['preset']}");
			$Add->dataItem->value = $Preset->value;
			$Add->dataItem->value['name'] = '';
			$Add->dataItem->value['id'] = '';
		}

		$this->pageContent .= $Add->Render();
		$this->ParseBack();
	}

	function AddSubmit()
	{
		$Add = new CMSAdd('metatags_page');
		$Add->Insert();
		Navigation::JumpBack($this->back);
	}
}

$Page = new CpMetatagsPage();
$Page->Render();
?>