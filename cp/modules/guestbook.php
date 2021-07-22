<?php
chdir('../');
require_once('init/init.php');

class CpGuestbookPage extends CPModulePage
{
	/**
	 * @var Guestbook
	 */
	private $ModuleGuestbook;
	
	/**
	 * @var CMSList
	 */
	private $List;
	
	/**
	 * @var CMSAdd
	 */
	private $CmsAdd;
	
	/**
	 * @var CMSEdit
	 */
	private $CmsEdit;
	
	/**
	 * @var Data
	 */
	private $DI;

	function __construct()
	{
		parent::__construct(true);
		
		$this->App->Load('guestbook', 'mod');

		$this->ModuleGuestbook = new Guestbook($this->iid);

		$this->List = new CMSList($this->ModuleGuestbook->confDIName);
		$this->List->Init();
		$this->CmsAdd  = new CMSAdd($this->ModuleGuestbook->confDIName);
		$this->CmsEdit = new CMSEdit($this->ModuleGuestbook->confDIName);
		$this->DI = Data::Create($this->ModuleGuestbook->confDIName);

		$this->AddBookmark('Guestbook', '?action=list', 'img/modules/guestbook/gstbook.gif');
		$this->AddBookmark('Settings',  '?action=settings', 'img/modules/guestbook/gstsett.gif');

		$this->SetPostAction('delete_selected', 'DeleteSelected');

		$this->SetGetAction('list', 'ListGuestbook');

		$this->SetGetPostAction('settings', 'submit', 'SettingsPost');
		$this->SetGetAction('settings', 'SettingsForm');

		$this->SetGetPostAction('add', 'submit', 'AddPost');
		$this->SetGetPostAction('edit', 'submit', 'EditPost');
		$this->SetGetAction('add', 'AddForm');
		$this->SetGetAction('edit', 'EditForm');
		$this->SetGetAction('delete', 'Delete');
		$this->SetGetAction('view', 'View');
		$this->SetGetAction('approve', 'ApproveMessage');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');
	}

	function Page()
	{
		$this->ListGuestbook();
	}


	function ListGuestbook()
	{
		$this->currentBookmark = 'Guestbook';
		$this->SetTitle('Guestbook', 'modules/guestbook/gsttop.gif');

		Navigation::SetBack($this->back);
		$this->ParseBack();

		$this->List->SetCond(@$_GET['cond']);
		$this->List->buttonAdd = false;
		$this->AddLink('Add Record', "?action=add&back={$this->setBack}"
			,'img/ico/links/add.gif'
			,'Add new record to the list.');
		$this->List->MakeLinkButtons($this);

		$this->List->buttonView = false;
		$this->List->buttonEdit = false;
		$this->List->buttonDelete = false;
		
		$listParams = '';
		if ($this->ModuleGuestbook->confUseApprove)
		{
			$this->List->AddButton('Approve','?action=approve&id=[id]&back=[back]', 'Approve this message.', '<img src="img/modules/guestbook/i_approve.gif" width="10" height="10" border="0" align="absmiddle">');
			$this->List->RemoveButton('Approve', '[is_approved]==1');
		}
		$this->List->AddButton('View', '?action=view&id=[id]&back=[back]'.$listParams,'View current record.', '<img src="img/cms/list/view.gif" width="8" height="14" border="0" align="absmiddle">');
		$this->List->AddButton('Edit', '?action=edit&id=[id]&back=[back]', 'Edit current record.', '<img src="img/cms/list/edit.gif" width="8" height="14" border="0" align="absmiddle">');
		$this->List->AddButton('Delete', "#delete\" class=\"delete\" rel=\"module_guestbook.php?action=delete&id=[id]&back=[back]\" onclick=\"return false", 'Delete current record.', '<img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle">');

		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $this->List->Render();

	}

	function AddForm()
	{
		$this->currentBookmark = 'Guestbook';
		$this->SetTitle('Guestbook: Add', 'modules/guestbook/gsttop.gif');

		$this->pageContent .= $this->CmsAdd->Render();

		$this->ParseBack();
	}

	function AddPost()
	{
		$this->CmsAdd->Insert();
		if (@$_POST['post_action'] == 2) Navigation::Jump(Navigation::Referer());
		else Navigation::JumpBack($this->back);
	}

	function EditForm()
	{
		$this->currentBookmark = 'Guestbook';
		$this->SetTitle('Guestbook: Edit', 'modules/guestbook/gsttop.gif');

		$this->CmsEdit->cond = "id='{$_GET['id']}'";
		$this->pageContent .= $this->CmsEdit->Render();

		$this->ParseBack();
	}

	function EditPost()
	{
		$this->CmsEdit->cond = "id='{$_GET['id']}'";
		$this->CmsEdit->Update();
		Navigation::JumpBack($this->back);
	}

	function SettingsForm()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->currentBookmark = 'Settings';
		$this->SetTitle('Guestbook: Settings', 'modules/guestbook/gsttop.gif');

		$this->settingsFields = array (
			'antibot'	=> array(
				'label'	=> 'Anti-bot protection',
				'type'	=> LiskType::TYPE_FLAG,
				'hint'	=> 'Prevent message flood'
			),
			'approve'	=> array(
				'label'	=> 'Approve messages',
				'type'	=> LiskType::TYPE_FLAG,
				'hint'	=> 'Messages should be approved by administrator before published'
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
		$this->settingsFieldsValues = array(
			'antibot' => $this->ModuleGuestbook->confUseAntiBot,
			'approve' => $this->ModuleGuestbook->confUseApprove,
			'items_per_page' => $this->ModuleGuestbook->confPagingItemsPerPage,
			'pages_per_page' => $this->ModuleGuestbook->confPagingPagesPerPage,
		);
		$this->customizableDI = array($this->ModuleGuestbook->confDIName);

		$this->pageContent .= $this->RenderSettingsPage($this->ModuleGuestbook);
	}

	function SettingsPost()
	{
		$settings = $_POST;

		$this->ModuleGuestbook->confUseAntiBot = @$settings['antibot_checked'] ? true : false;
		$this->ModuleGuestbook->confUseApprove = @$settings['approve_checked'] ? true : false;
		$this->ModuleGuestbook->confPagingItemsPerPage = $settings['items_per_page'];
		$this->ModuleGuestbook->confPagingPagesPerPage = $settings['pages_per_page'];

		$this->ModuleGuestbook->SaveSettings();

		Navigation::Jump('?action=settings');
	}

	function Delete()
	{
		$this->DI->Delete('id='.$_GET['id']);
		Navigation::JumpBack($this->back);
	}

	function DeleteSelected()
	{
		$this->List->DeleteSelected();
		Navigation::JumpBack($this->back);
	}

	function View()
	{
		$this->currentBookmark = 'Guestbook';
		$this->SetTitle('Guestbook: View', 'modules/guestbook/gsttop.gif');

		$cond = "id = {$_GET['id']}";
		$this->DI->Select($cond);
		$this->pageContent .= $this->Parser->MakeDynamicView($this->DI, 'cms/view');
		$this->ParseBack();
	}

	function ApproveMessage()
	{
		$id = $_REQUEST['id'];
		$this->DI->Update(
			'id='.$id,
			array(
				'is_approved' => '1'
			)
		);

		Navigation::JumpBack($this->back);
	}

	function GetSnippet()
	{
		$code = $this->ModuleGuestbook->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}
}

$Page = new CpGuestbookPage();
$Page->Render();

?>