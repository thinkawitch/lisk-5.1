<?php
chdir('../');
require_once('init/init.php');

class CpDownloadsPage extends CPModulePage
{
	
	/**
	 * @var Downloads
	 */
	private $Downloads;

	function __construct()
	{
		parent::__construct(true);
		GLOBAL $App;

		$App->Load(LiskModule::MODULE_DOWNLOADS, 'mod');
		$this->Downloads = new Downloads($this->iid);
		$this->titlePicture = 'modules/downloads/uho.gif';

		$this->AddBookmark('Downloads', '?action=list&back='.$this->setBack, 'img/modules/downloads/downloads.gif');
		$this->AddBookmark('Settings', '?action=settings&back='.$this->setBack, 'img/modules/downloads/tab_settings.gif');

		$this->SetGetAction('list', 'ListDownloads');

		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');

		$this->SetGetPostAction('download_add', 'submit', 'DownloadAddSubmit');
		$this->SetGetAction('download_add', 'DownloadAddForm');
		$this->SetGetPostAction('download_edit', 'submit', 'DownloadEditSubmit');
		$this->SetGetAction('download_edit', 'DownloadEditForm');
		$this->SetGetAction('download_delete', 'DownloadDelete');

		$this->SetGetPostAction('download_order', 'submit', 'DownloadOrderSubmit');
		$this->SetGetAction('download_order', 'DownloadOrderForm');


		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');
	}

	function Page()
	{
		$this->ListDownloads();
	}

	function ListDownloads()
	{
		$this->currentBookmark = 'Downloads';
		$this->SetTitle('Downloads');

		Navigation::SetBack($this->back);
		$this->ParseBack();

		$DownloadsDI = Data::Create('Obj_DownloadsDI_di_'.$this->Downloads->confDIName, true);

		$List = new CMSList($DownloadsDI);
        $List->Init();
		$List->buttonAdd = false;
		$List->buttonOrder = false;
		$List->buttonEdit = false;
		$List->buttonDelete = false;
		$List->buttonView = false;

		$this->AddLink('Add '.$DownloadsDI->label, "?action=download_add&back={$this->setBack}", 'img/ico/links/add.gif', 'Add new download');
		$this->AddLink('Order '.$DownloadsDI->label, "?action=download_order&back={$this->setBack}", 'img/ico/links/order.gif', 'Order downloads');

		$List->MakeLinkButtons();

		$List->AddButton('Edit', '?action=download_edit&id=[id]&back=[back]','', '<img src="img/cms/list/edit.gif" width="8" height="14" border="0" align="absmiddle">');

		$List->AddButton('Delete', '?action=download_delete&id=[id]&back=[back]','', '<img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle">');

		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $List->Render();
	}


	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('Downloads Settings');
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
			)
		);
		$this->settingsFieldsValues=array(
			'items_per_page'  => $this->Downloads->confPagingItemsPerPage,
			'pages_per_page'  => $this->Downloads->confPagingPagesPerPage,
		);

		$this->customizableDI = array($this->Downloads->confDIName);

		$this->pageContent .= $this->RenderSettingsPage($this->Downloads);
	}

	function SaveSettings()
	{

		$this->Downloads->confPagingItemsPerPage = is_numeric($_POST['items_per_page']) ? $_POST['items_per_page'] : 0;
		$this->Downloads->confPagingPagesPerPage = is_numeric($_POST['pages_per_page']) ? $_POST['pages_per_page'] : 1;
		$this->Downloads->SaveSettings();

		Navigation::Jump('?');
	}

	function GetSnippet()
	{
		$code = $this->Downloads->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}

	function DownloadAddForm()
	{
		$this->currentBookmark = 'Downloads';

		$DownloadsDI = new DownloadsDI($this->Downloads->confDIName);

		$this->SetTitle('Add '.$DownloadsDI->label, $this->titlePicture);
		$this->ParseBack();

		$CmsAdd = new CMSAdd($DownloadsDI);
		$this->pageContent .= $CmsAdd->Render();
	}

	function DownloadAddSubmit()
	{
		$DownloadsDI = new DownloadsDI($this->Downloads->confDIName);
		$CmsAdd = new CMSAdd($DownloadsDI);
		$CmsAdd->Insert();
		if (@$_POST['post_action'] == 2) Navigation::Jump(Navigation::Referer());
		else Navigation::JumpBack($this->back);
	}

	function DownloadEditForm()
	{
		$this->currentBookmark = 'Downloads';
		$DownloadsDI = new DownloadsDI($this->Downloads->confDIName);
		$this->SetTitle('Edit '.$DownloadsDI->label, $this->titlePicture);
		$this->ParseBack();

		$CmsEdit = new CMSEdit($DownloadsDI);
		$CmsEdit->cond = "id='{$_GET['id']}'";
		$this->pageContent .= $CmsEdit->Render();
	}

	function DownloadEditSubmit()
	{
		$DownloadsDI = new DownloadsDI($this->Downloads->confDIName);
		$CmsEdit = new CMSEdit($DownloadsDI);
		$CmsEdit->cond = "id='{$_GET['id']}'";
		$CmsEdit->Update();
		Navigation::JumpBack($this->back);
	}

	function DownloadDelete()
	{
		$DownloadsDI = new DownloadsDI($this->Downloads->confDIName);
		$DownloadsDI->Delete("id='{$_GET['id']}'");
		Navigation::JumpBack($this->back);
	}

	function DownloadOrderForm()
	{
		$this->currentBookmark = 'Downloads';
		$this->SetTitle('Order', $this->titlePicture);

		$DownloadsDI = new DownloadsDI($this->Downloads->confDIName);
		$CMSOrder = new CMSOrder($DownloadsDI);
		$this->pageContent .= $CMSOrder->Render();

		$this->ParseBack();
	}

	function DownloadOrderSubmit()
	{
		$DownloadsDI = new DownloadsDI($this->Downloads->confDIName);
		$CMSOrder = new CMSOrder($DownloadsDI);
		$CMSOrder->Save();
		Navigation::JumpBack($this->back);
	}
}

$Page = new CpDownloadsPage();
$Page->Render();
?>