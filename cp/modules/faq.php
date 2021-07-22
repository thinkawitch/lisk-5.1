<?php
chdir('../');
require_once('init/init.php');

class CpFaqPage extends CPModulePage
{

	/**
	 * @var Faq
	 */
	private $Faq;

	function __construct()
	{
		parent::__construct(true);

		$this->App->Load('faq', 'mod');
		$this->titlePicture = 'modules/faq/uho.gif';
		$this->Faq = new Faq($this->iid);

		$this->AddBookmark('FAQ', '?action=list', 'img/modules/faq/list.gif');
		$this->AddBookmark('Settings', '?action=settings', 'img/modules/faq/settings.gif');

		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');

		$this->SetPostAction('delete_selected', 'DeleteSelected');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');
	}

	public function Page()
	{
		$this->Faq();
	}

	public function Faq()
	{
		$this->SetBack();
		$this->ParseBack();
		$this->currentBookmark = 'FAQ';
		if ($this->Faq->confTreeMode) $this->TreeMode();
		else $this->ListMode();
	}

	public function DeleteSelected()
	{
		$Tree = new CMSTree('faq');
		$Tree->DeleteSelected();
	}

	public function TreeMode()
	{
		$this->SetTitle('FAQ', 'modules/faq/uho.gif');
		$Tree = new CMSTree($this->Faq->confDITreeName);

		$this->pageContent .= $Tree->Render();
		$Tree->MakeLinkButtons();
		$Tree->AdditionalNavigation();

	}

	public function ListMode()
	{
		$this->SetTitle('FAQ');

		$DI = new Data($this->Faq->confDIQuestionsName);
		$DI->ReSet('list');
		$List = new CMSList($DI);
        $List->Init();
		$List->MakeLinkButtons();
		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $List->Render();
	}

	public function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('FAQ Settings');
		$this->currentBookmark = 'Settings';

		$this->settingsFields = array (
			'tree_mode'	=> array(
				'label'	=> 'FAQ Mode',
				'type'	=> LiskType::TYPE_LIST,
				'object'=> 'def_faq_mode',
				'hint'	=> 'Please note that if you switch FAQ mode from Tree to List, all questions you currently have will be saved. If you switch from List to Tree, all questions will be saved in \'General\' category.'
			)
		);
		$this->settingsFieldsValues=array(
			'tree_mode'	=> ($this->Faq->confTreeMode) ? 1 : 0
		);
		$this->customizableDI = array($this->Faq->confDICategoriesName, $this->Faq->confDIQuestionsName);

		$this->pageContent .= $this->RenderSettingsPage($this->Faq);
	}

	public function SaveSettings()
	{
		GLOBAL $Db,$App;

		$currentTreeMode = ($this->Faq->confTreeMode) ? 1 : 0;

		if ($_POST['tree_mode'] != $currentTreeMode)
		{
			switch ($_POST['tree_mode'])
			{
				case 0:
					// move to list mode
					$this->Faq->confTreeMode = false;
					$this->Faq->SaveSettings();

					$conf = $App->ReadDI($this->Faq->confDIQuestionsName);
					$conf['fields']['parent_id']['type'] = LiskType::TYPE_HIDDEN;
					$App->SaveDI($this->Faq->confDIQuestionsName, $conf);
					break;

				case 1:
					$this->Faq->confTreeMode = true;
					$this->Faq->SaveSettings();

					$conf = $App->ReadDI($this->Faq->confDIQuestionsName);
					$conf['fields']['parent_id']['type'] = LiskType::TYPE_CATEGORY;
					$App->SaveDI($this->Faq->confDIQuestionsName, $conf);

					// move to tree mode
					$FaqCategory = new Data($this->Faq->confDICategoriesName);
					$Db->Delete(null, $FaqCategory->table);

					$Db->Insert(array(
						'id'	=> 1,
						'name'	=> 'Faq',
						'url'	=> $this->Faq->confBaseUrl
					), $FaqCategory->table);

					$FaqCategory->Insert(array(
						'id'		=> 2,
						'name'		=> 'General',
						'parent_id'	=> 1,
					));

					$FaqQuestion = new Data($this->Faq->confDIQuestionsName);
					$FaqQuestion->Select();
					if (Utils::IsArray($FaqQuestion->values))
					{
						foreach ($FaqQuestion->values as $row)
						{
							$FaqQuestion->Update("id={$row['id']}", array(
								'parent_id'	=> 2,
								'id'		=> $row['id'],
								'name'		=> $row['name']
							));
						}
					}

					break;
			}
		}
		Navigation::Jump('?');
	}

	function GetSnippet()
	{
		$code = $this->Faq->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}
}

$Page = new CpFaqPage();
$Page->Render();

?>