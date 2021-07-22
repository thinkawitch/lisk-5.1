<?php
chdir('../');
require_once('init/init.php');

class CpPollPage extends CPModulePage
{
    /**
     * @var Poll
     */
	private $Poll;

	function __construct()
	{
		parent::__construct(true);
		$this->App->Load('poll', 'mod');

		$this->Poll = new Poll($this->iid);

		$this->AddBookmark('Polls', '?action=poll_list', 'img/modules/poll/poll.gif');
		$this->AddBookmark('Settings', '?action=settings', 'img/modules/poll/settings.gif');

		$this->SetPostAction('delete_selected', 'DeleteSelected');

		$this->SetGetAction('poll_list', 'ListPolls');
		$this->SetGetPostAction('poll_add', 'submit', 'PollAddSubmit');
		$this->SetGetAction('poll_add', 'PollAddForm');
		$this->SetGetPostAction('poll_edit', 'submit', 'PollEditSubmit');
		$this->SetGetAction('poll_edit', 'PollEditForm');
		$this->SetGetAction('poll_delete', 'PollDelete');

		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');
		$this->SetGetAction('settings', 'Settings');

		$this->SetGetAction('answer_list', 'ListAnswers');

		$this->SetGetAction('poll_result', 'PollResult');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');

		$this->titlePicture = 'modules/poll/ico_poll.gif';
	}

	function Page()
	{
		$this->ListPolls();
	}

	function ListPolls()
	{
		$this->currentBookmark = 'Polls';
		$this->SetTitle('Polls', $this->titlePicture);

		Navigation::SetBack($this->back);
		$this->ParseBack();

		$Poll = new PollDI($this->Poll->confDIPollName);
		$Poll->ReSet('list');
		$PollList = new CMSList($Poll);
		$PollList->SetCond(@$_GET['cond']);
		$PollList->Init();
		$PollList->buttonView = false;
		$PollList->buttonAdd = false;
		$PollList->buttonEdit = false;
		$PollList->buttonDelete = false;
		$PollList->buttonAdd = false;

		$this->AddLink('Add '.$Poll->label, '?action=poll_add&back='.$this->setBack, 'img/ico/links/add.gif', 'Add '.$Poll->label);
//		$this->AddLink('Order '.$Poll->label, '?action=poll_order&back='.$this->setBack, 'img/ico/links/order.gif', 'Order '.$Poll->label);
		

		$PollList->SetFieldLink('name', '?action=answer_list&poll_id=[id]&back=[back]');
		$PollList->AddButton(
			'Results',
			"?action=poll_result&poll_id=[id]&back=[back]",
			'View results of current poll',
			'<img src="img/modules/poll/result.gif" width="14" height="11" border="0" align="absmiddle">'
		);
		$PollList->AddButton('Edit', '?action=poll_edit&id=[id]&back=[back]', '', '<img src="img/cms/list/edit.gif" width="8" height="14" border="0" align="absmiddle"> ' );
		$PollList->AddButton('Delete', "#delete\" class=\"delete\" rel=\"module_poll.php?action=poll_delete&id=[id]&back=[back]\" onclick=\"return false", '', '<img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle">' );

		$PollList->MakeLinkButtons();

		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $PollList->Render();
	}


	function PollAddForm()
	{
		$this->currentBookmark = 'Polls';
		$this->SetTitle('Add Poll', $this->titlePicture);
		$this->ParseBack();

		$CmsAdd = new CMSAdd(new PollDI($this->Poll->confDIPollName));
		$this->pageContent .= $CmsAdd->Render();
	}

	function PollAddSubmit()
	{
		$CmsAdd = new CMSAdd(new PollDI($this->Poll->confDIPollName));
		$CmsAdd->Insert();
		if (@$_POST['post_action'] == 2) Navigation::Jump(Navigation::Referer());
		else Navigation::JumpBack($this->back);
	}

	function PollEditForm()
	{
		$this->currentBookmark = 'Polls';
		$this->SetTitle('Edit Poll', $this->titlePicture);
		$this->ParseBack();

		$CmsEdit = new CMSEdit(new PollDI($this->Poll->confDIPollName));
		$CmsEdit->cond = "id='{$_GET['id']}'";
		$this->pageContent .= $CmsEdit->Render();
	}

	function PollEditSubmit()
	{
		$CmsEdit = new CMSEdit(new PollDI($this->Poll->confDIPollName));
		$CmsEdit->cond = "id='{$_GET['id']}'";
		$CmsEdit->Update();
		Navigation::JumpBack($this->back);
	}

	function PollDelete()
	{
		$DI = new PollDI($this->Poll->confDIPollName);
		$DI->Delete("id='{$_GET['id']}'");
		Navigation::JumpBack($this->back);
	}

	function DeleteSelected()
	{
		if (@$_GET['action'] == 'answers')
		{
			$CMSList = new CMSList(new PollDI($this->Poll->confDIAnswerName));
		}
		else
		{
			$CMSList = new CMSList(new PollDI($this->Poll->confDIPollName));
		}
		$CMSList->Init();
		$CMSList->DeleteSelected();
	}

	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('Poll Settings', $this->titlePicture);
		$this->currentBookmark = 'Settings';

		$this->settingsFields = array (
			'vote_frequency'	=> array(
				'label'	=> 'Vote Frequency',
				'type'	=> LiskType::TYPE_LIST,
				'object'=> 'def_poll_vote_frequency',
				'hint'	=> 'How often is it allowed to vote'
			),
			'vote_mode' => array(
				'label' => 'Mode',
				'type'  => LiskType::TYPE_LIST,
				'object'=> 'def_poll_vote_mode',
				'hint'  => 'Working mode',
			),
		);
		$this->settingsFieldsValues=array(
			'vote_frequency'	=> $this->Poll->confVoteFrequency,
			'vote_mode'			=> $this->Poll->confVoteMode,
		);
		$this->customizableDI = array($this->Poll->confDIPollName, $this->Poll->confDIAnswerName);

		$this->pageContent .= $this->RenderSettingsPage($this->Poll);
	}

	function SaveSettings()
	{
		$settings = $_POST;

		$this->Poll->confVoteFrequency = $settings['vote_frequency'];
		$this->Poll->confVoteMode = $settings['vote_mode'];

		$this->Poll->SaveSettings();

		Navigation::JumpBack($this->setBack);
	}


	function ListAnswers()
	{
        $this->SetBack();
		$this->ParseBack();

		$this->currentBookmark = 'Polls';
		$this->SetTitle('Poll Answers', $this->titlePicture);

		$CMSList = new CMSList($this->Poll->confDIAnswerName);

		$CMSList->SetCond("poll_id={$_GET['poll_id']}");
        $CMSList->Init();
		$CMSList->MakeLinkButtons();

		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $CMSList->Render();
	}

	function PollResult ()
	{
		$this->currentBookmark = 'Polls';
		$this->SetTitle('Poll Result', $this->titlePicture);

		$this->SetBack();
		$this->ParseBack();
        
		$this->Poll->currentPollId = $_GET['poll_id'];
		
		$cwd = getcwd();
		chdir('../');
		
		$this->pageContent .= $this->Poll->RenderPollResult();
		
		chdir($cwd);
	}

	function GetSnippet()
	{
		$code = $this->Poll->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}
}

$Page = new CpPollPage();
$Page->Render();
?>