<?php
chdir('../');
require_once('init/init.php');

class CpStructuredListPage extends CPModulePage
{
    /**
     * @var StructuredList
     */
	private $StructuredList;

	function __construct()
	{
		parent::__construct(true);
		
		$this->App->Load('structured_list','mod');
		$this->titlePicture = 'modules/structured_list/uho.gif';
		$this->StructuredList = new StructuredList($this->iid);

		$this->AddBookmark('Structured List', '?action=list', 'img/modules/structured_list/list.gif');
		$this->AddBookmark('Settings', '?action=settings', 'img/modules/structured_list/settings.gif');

		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');

		$this->SetPostAction('delete_selected', 'DeleteSelected');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');
	}

	function Page()
	{
		$this->StructuredList();
	}

	function StructuredList()
	{
		$this->SetBack();
		$this->ParseBack();
		$this->currentBookmark = 'Structured List';
		$this->ListRecords();
	}

	function DeleteSelected()
	{
		$List = new CMSList($this->StructuredList->confDIName);
		$List->Init();
		$List->DeleteSelected();
	}

	function ListRecords()
	{
		$this->SetTitle('Structured List');

		$List = new CMSList($this->StructuredList->confDIName);
        $List->Init();
		if (isset($List->dataItem->fields['name']))
		{
		    $List->AlphabeticNavigation = true;
		    $List->AlphabeticField = 'name';
		}
        
		$List->SetCond(@$_GET['cond']);
		$List->MakeLinkButtons();
		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $List->Render();
	}

	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('Structured List Settings');
		$this->currentBookmark = 'Settings';

		$this->settingsFields = array(
			'items_per_page' => array(
				'type'  => LiskType::TYPE_INPUT,
				'hint'  => 'Number of entries displayed on one page. Set to zero to display all entries',
				'label' => 'Entries Per Page'
			),
			'pages_per_page' => array(
				'type' => LiskType::TYPE_INPUT,
				'hint' => 'Number of pages displayed on the paging line'
			),
		);
		$this->settingsFieldsValues=array(
			'items_per_page'  => $this->StructuredList->confPagingItemsPerPage,
			'pages_per_page'  => $this->StructuredList->confPagingPagesPerPage,
		);
		$this->customizableDI = array($this->StructuredList->confDIName);

		$this->pageContent .= $this->RenderSettingsPage($this->StructuredList);
	}

	function SaveSettings()
	{
		$this->StructuredList->confPagingItemsPerPage = is_numeric($_POST['items_per_page']) ? $_POST['items_per_page'] : 0;
		$this->StructuredList->confPagingPagesPerPage = is_numeric($_POST['pages_per_page']) ? $_POST['pages_per_page'] : 1;

		$this->StructuredList->SaveSettings();

		Navigation::Jump('?');
	}

	function GetSnippet()
	{
		$code = $this->StructuredList->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}
}

$Page = new CpStructuredListPage();
$Page->Render();

?>