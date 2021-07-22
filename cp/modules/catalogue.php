<?php
chdir('../');
require_once('init/init.php');

class CpCataloguePage extends CPModulePage
{
	/**
	 * @var Catalogue
	 */
	private $Catalogue;

	function __construct()
	{
		parent::__construct(true);

		$this->App->Load(LiskModule::MODULE_CATALOGUE, 'mod');
		$this->App->Load('cross_tree', 'cms');

		$this->titlePicture = 'modules/catalogue/uho.gif';
		$this->Catalogue = new Catalogue($this->iid);

		$this->AddBookmark('Catalogue', '?action=list', 'img/modules/catalogue/list.gif');
		$this->AddBookmark('Tree view', '?action=js', 'img/cms/tree/tab_tree_view.gif');
		$this->AddBookmark('Hot Items', '?action=hot', 'img/modules/catalogue/list.gif');
		$this->AddBookmark('Settings', '?action=settings', 'img/modules/catalogue/settings.gif');

		$this->SetGetAction('js', 'JSTree');

		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');

		$this->SetPostAction('delete_selected', 'DeleteSelected');

		$this->SetGetAction('hot', 'HotItems');

		$this->SetGetAction('search', 'Search');
		$this->SetGetAction('advanced', 'Advanced');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');
	}

	function Page()
	{
		$this->Catalogue();
	}

	function Catalogue()
	{
		$this->SetBack();
		$this->ParseBack();
		$this->currentBookmark = 'Catalogue';

		$this->SetTitle('Catalogue ', $this->titlePicture);
		$Tree = new CMSTree($this->Catalogue->confDITreeName);

		$this->pageContent .= $Tree->Render();
		$Tree->MakeLinkButtons();
		$Tree->AdditionalNavigation();

		$this->AddLink('Search', "?action=search", 'img/cms/tree/ico_search.gif', 'Catalogue search');
		$this->AddLink('Adv. Options', "?action=advanced",'img/cms/tree/ico_advanced.gif', 'Catalogue search');

	}

	function Search()
	{
		$this->currentBookmark = 'Catalogue';
		$this->title = 'Catalogue Search';
		$Tree = new CMSTree($this->Catalogue->confDITreeName);
		$this->pageContent .= $Tree->Search();
	}

	function Advanced()
	{
		$this->currentBookmark = 'Catalogue';
		$this->title = 'Advanced Options';
		$Tree = new CMSTree($this->Catalogue->confDITreeName);
		$this->pageContent .= $Tree->Advanced();
	}

	function HotItems()
	{
		$this->SetBack();
		$this->ParseBack();
		$this->currentBookmark = 'Hot Items';
		$this->title = 'Hot Items';
		$CrossTree = new CMSCrossTree($this->Catalogue->confDICrossTreeName);
		$this->pageContent .= $CrossTree->Render();
	}

	function DeleteSelected()
	{
		$Tree = new CMSTree($this->Catalogue->confDITreeName);
		$Tree->DeleteSelected();
	}

	function JSTree()
	{
		$this->SetBack();
		$this->ParseBack();
		$this->SetBack();
		$this->currentBookmark = 'Tree view';

		$Tree = new CMSTree($this->Catalogue->confDITreeName);

		$this->SetTitle('Catalogue Tree View');

		$Tree->MakeJsLinkButtons();

		$this->pageContent .= $Tree->RenderJS();
	}

	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('Catalogue Settings');
		$this->currentBookmark = 'Settings';

		$this->settingsFields = array (
			'items_per_page' => array(
				'type'  => 'input',
				'hint'  => 'Number of entries displayed on one page. Set to zero to display all entries',
				'label' => 'Entries Per Page'
			),
			'pages_per_page' => array(
				'type' => 'input',
				'hint' => 'Number of pages displayed on the paging line'
			),
		);
		$this->settingsFieldsValues=array(
			'items_per_page'  => $this->Catalogue->confPagingItemsPerPage,
			'pages_per_page'  => $this->Catalogue->confPagingPagesPerPage,
		);

		$this->customizableDI = array($this->Catalogue->confDIItemsName, $this->Catalogue->confDICategoriesName);

		$this->pageContent .= $this->RenderSettingsPage($this->Catalogue);
	}

	function SaveSettings()
	{

		$this->Catalogue->confPagingItemsPerPage = is_numeric($_POST['items_per_page']) ? $_POST['items_per_page'] : 0;
		$this->Catalogue->confPagingPagesPerPage = is_numeric($_POST['pages_per_page']) ? $_POST['pages_per_page'] : 1;
		$this->Catalogue->SaveSettings();

		Navigation::Jump('?');
	}

	function GetSnippet()
	{
		$code = $this->Catalogue->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}
}

$Page = new CpCataloguePage();
$Page->Render();

?>