<?php
chdir('../');
require_once('init/init.php');

class CpNewsPage extends CPModulePage
{
    /**
     * @var News
     */
	private $News;
	
	/**
	 * @var CMSCrossList
	 */
	private $CrossList;

	function __construct()
	{
		parent::__construct(true);

		$this->App->Load('news', 'mod');
		$this->titlePicture = 'modules/news/uho.gif';
		$this->News = new News($this->iid);

		$this->App->ReadDI($this->News->confDINewsName);
		$GLOBALS['CROSS_LIST_HOT_NEWS_'.$this->News->iid] = $this->App->ReadDI($this->News->confCrossListName);

		$this->CrossList = new CMSCrossList('hot_news_'.$this->News->iid, @$_GET['cond']);

		$this->AddBookmark('News', '?action=list', 'img/modules/news/news.gif');
		if ($this->News->confTypeNews == 1)
		{
			$this->AddBookmark('Latest News', '?action=latestnews', 'img/modules/news/latest.gif');
		}
		$this->AddBookmark('Settings', '?action=settings', 'img/modules/news/settings.gif');

		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');

		$this->SetGetAction('latestnews', 'LatestNews');
		$this->SetGetAction('add', 'Add');
		$this->SetGetAction('remove', 'Remove');

		$this->SetPostAction('delete_selected', 'DeleteSelected');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');
	}

	function Page()
	{
		$this->News();
	}

	function News()
	{
		$this->SetBack();
		$this->ParseBack();
		$this->currentBookmark = 'News';
		$this->ListNews();
	}

	function DeleteSelected()
	{
		$List = new CMSList($this->News->confDINewsName);
		$List->Init();
		$List->DeleteSelected();
	}

	function ListNews()
	{
		$this->SetTitle('News');

		$List = new CMSList($this->News->confDINewsName);
		$List->SetCond(@$_GET['cond']);
		$List->Init();
		$List->MakeLinkButtons();
		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $List->Render();
	}

	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('News Settings');
		$this->currentBookmark = 'Settings';

		$this->settingsFields = array (
			'type_news'	=> array (
				'type'	=>	LiskType::TYPE_RADIO,
				'object'=>	'def_type_news',
				'label' =>	'Latest News',
			),
			'last_news_count'	=>	array (
				'type'	=> LiskType::TYPE_INPUT,
				'label'	=> 'Latest news quantity'
			),
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
			'type_news'		  => $this->News->confTypeNews,
			'last_news_count' => $this->News->confLastNewsCount,
			'items_per_page'  => $this->News->confPagingItemsPerPage,
			'pages_per_page'  => $this->News->confPagingPagesPerPage,
		);
		$this->customizableDI = array($this->News->confDINewsName/*,$this->News->confDIHotNewsName*/);

		$this->pageContent .= $this->RenderSettingsPage($this->News);
	}

	function SaveSettings()
	{
		$this->News->confTypeNews = $_POST['type_news'];
		$this->News->confLastNewsCount = $_POST['last_news_count'];
		$this->News->confPagingItemsPerPage = is_numeric(@$_POST['items_per_page']) ? $_POST['items_per_page'] : 0;
		$this->News->confPagingPagesPerPage = is_numeric(@$_POST['pages_per_page']) ? $_POST['pages_per_page'] : 1;

		$this->News->SaveSettings();

		Navigation::Jump('?');
	}

	function LatestNews ()
	{
		$this->currentBookmark = 'Latest News';
		$this->SetTitle('Latest News', 'modules/news/uho.gif');

		$this->SetBack();
		$this->ParseBack();

		$this->CrossList->MakeLinkButtons($this);

		Navigation::SetBack($this->setBack);

		$this->pageContent .= $this->CrossList->Render();
	}

	function Add()
	{
		$this->CrossList->Add($_GET['id']);
		Navigation::Jump('?');
	}

	function Remove()
	{
		$this->CrossList->Remove($_GET['id']);
		Navigation::Jump('?');
	}

	function GetSnippet()
	{
		$code = $this->News->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}
}

$Page = new CpNewsPage();
$Page->Render();

?>