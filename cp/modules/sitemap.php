<?php
chdir('../');
require_once('init/init.php');

class CpSitemapPage extends CPModulePage
{
    /**
     * @var Sitemap
     */
	private $Sitemap;
	
	function __construct()
	{
		parent::__construct(true);

		$this->App->Load('sitemap', 'mod');
		$this->titlePicture = 'modules/sitemap/settings.gif';
		$this->Sitemap = new Sitemap($this->iid);

		$this->AddBookmark('Settings', '?action=settings', 'img/modules/sitemap/settings.gif');

		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');
	}

	function Page()
	{
		$this->Settings();
	}

	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('Sitemap Settings');
		$this->currentBookmark = 'Settings';

		$this->settingsFields = array ();
		$this->settingsFieldsValues = array();

		$this->customizableDI = array();

		$this->pageContent .= $this->RenderSettingsPage($this->Sitemap);
	}

	function SaveSettings()
	{
		
		$this->Sitemap->SaveSettings();

		Navigation::Jump('?');
	}

	function GetSnippet()
	{
		$code = $this->News->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}
}

$Page = new CpSitemapPage();
$Page->Render();

?>