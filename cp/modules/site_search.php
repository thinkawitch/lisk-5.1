<?php
chdir('../');
require_once 'init/init.php';

class CpSiteSearchPage extends CPModulePage
{
	function __construct()
	{
		parent::__construct(false);
		
		//load module
		$this->App->Load('site_search', 'mod');

		$this->AddBookmark('Settings', '?action=settings', 'img/modules/site_search/settings.gif');

		//Get snippet
		$this->SetGetAction('get_snippet', 'GetSnippet');

		// Settings
		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SettingsSave');
	}

	function GetSnippet()
	{
		$SiteSearch = new SiteSearch($this->GetIID());
		$code = $SiteSearch->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}

	private function GetIID()
	{
		GLOBAL $Db;
		$iid = $Db->Get("name='site_search'", 'id', 'sys_modules');
		return $iid;
	}

	function Page()
	{
		Navigation::Jump('?action=settings');
	}

	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('Settings');
		$this->currentBookmark = 'Settings';

		$Module = new SiteSearch($this->GetIID());

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
			'items_per_page'  => $Module->confPagingItemsPerPage,
			'pages_per_page'  => $Module->confPagingPagesPerPage,
		);

		$this->customizableDI = array();

		$this->pageContent .= $this->RenderSettingsPage($Module);
	}

	function SettingsSave()
	{
		$Module = new SiteSearch($this->GetIID());

		$Module->confPagingItemsPerPage =  is_numeric($_POST['items_per_page']) ? $_POST['items_per_page'] : 0;
		$Module->confPagingPagesPerPage =  is_numeric($_POST['pages_per_page']) ? $_POST['pages_per_page'] : 5;

		$Module->SaveSettings();

		Navigation::Jump('?action=settings');
	}

}

$Page = new CpSiteSearchPage();
$Page->Render();
?>