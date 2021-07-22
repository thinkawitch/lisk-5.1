<?php
require_once('init/init.php');

class CpMessagesPage extends CPPage
{
	private $diNameMessage = 'cp_message';

	private $titleText;
	private $selfUri = 'cp_messages.php?1=1';

	function __construct()
	{
		parent::__construct();
		
		$this->AddBookmark(LANG_CP_MESSAGES_INBOX, $this->selfUri.'&action=inbox', 'img/cp_mail/i_inbox.gif');
		$this->AddBookmark(LANG_CP_MESSAGES_OUTBOX, $this->selfUri.'&action=outbox', 'img/cp_mail/i_outbox.gif');
		$this->AddBookmark('New Message', $this->selfUri.'&action=new', 'img/cp_mail/i_newmail.gif');
		$this->AddBookmark(LANG_CP_MESSAGES_TRASH, $this->selfUri.'&action=trash', 'img/cp_mail/i_trash.gif');

		$this->titlePicture = 'cp_mail/uho_inbox.gif';
		$this->titleText = 'CP Mail';

		$this->SetGetPostAction('new', 'submit', 'AddMessagePost');
		$this->SetGetAction('new', 'AddMessageForm');

		$this->SetGetPostAction('reply', 'submit', 'ReplyMessagePost');
		$this->SetGetAction('reply', 'ReplyMessageForm');
		$this->SetGetAction('delete', 'DeleteMessage');

		$this->SetGetAction('view_inbox', 'ViewInbox');
		$this->SetGetAction('view_outbox', 'ViewOutbox');
		$this->SetGetAction('view_trash', 'ViewTrash');

		$this->SetGetAction('inbox', 'ShowInbox');
		$this->SetGetAction('outbox', 'ShowOutbox');
		$this->SetGetAction('trash', 'ShowTrash');
		
		$this->App->Load('wikiparser', 'utils');

	}

	function Page()
	{
		$this->ShowInbox();
	}

	function ShowInbox()
	{
		GLOBAL $Page, $Auth;
		$this->currentBookmark = LANG_CP_MESSAGES_INBOX;
		$this->SetTitle($this->titleText.': Inbox');
		$this->SetBack();

		$dataItem = Data::Create($this->diNameMessage);
		$List = new CMSList($dataItem);
        $List->Init();
		$List->buttonAdd = false;
		$List->buttonView = false;
		$List->buttonEdit = false;
		$List->buttonDelete = false;
		$List->buttonDeleteAll = false;
		$List->buttonCheckbox = false;
		$List->buttonExport = false;

		$List->AddButton('View', $this->selfUri.'&action=view_inbox&id=[id]&back=[back]', 'View', '<img src="img/cms/list/view.gif" width="8" height="14" border="0" align="absmiddle">');
		$List->AddButton($Page->Message('main','delete'), "#delete\" class=\"delete\" rel=\"{$this->selfUri}&action=delete&id=[id]&back=[back]\" onclick=\"return false", $Page->Message('cpmodules','delete_hint'), '<img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle">');

		$List->MakeLinkButtons();

		//get user messages
		$links = $this->GetUserInbox($Auth->user['id']);

		$this->Paging->SwitchOff();
		$this->pageContent .= $List->Render('cms_list', $links);
	}

	function ShowOutbox()
	{
		GLOBAL $Page, $Auth;
		$this->currentBookmark = LANG_CP_MESSAGES_OUTBOX;
		$this->SetTitle($this->titleText.': Outbox');
		$this->SetBack();

		$dataItem = Data::Create($this->diNameMessage);
		$List = new CMSList($dataItem);
        $List->Init();
		$List->buttonAdd = false;
		$List->buttonView = false;
		$List->buttonEdit = false;
		$List->buttonDelete = false;
		$List->buttonDeleteAll = false;
		$List->buttonCheckbox = false;
		$List->buttonExport = false;

		$List->AddButton('View', $this->selfUri.'&action=view_outbox&id=[id]&back=[back]', 'View', '<img src="img/cms/list/view.gif" width="8" height="14" border="0" align="absmiddle">');
        $List->AddButton($Page->Message('main','delete'), "#delete\" class=\"delete\" rel=\"{$this->selfUri}&action=delete&id=[id]&back=[back]\" onclick=\"return false", $Page->Message('cpmodules','delete_hint'), '<img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle">');

		$List->MakeLinkButtons($this);

		//get user messages
		$messages = $this->GetUserOutbox($Auth->user['id']);

		$this->Paging->SwitchOff();
		$this->pageContent .= $List->Render('cms_list', $messages);
	}

	function ShowTrash()
	{
		GLOBAL $Auth;
		$this->currentBookmark = LANG_CP_MESSAGES_TRASH;
		$this->SetTitle($this->titleText.': Trash');
		$this->SetBack();

		$dataItem = Data::Create($this->diNameMessage);
		$List = new CMSList($dataItem);
        $List->Init();
		$List->buttonAdd = false;
		$List->buttonView = false;
		$List->buttonEdit = false;
		$List->buttonDelete = false;
		$List->buttonDeleteAll = false;
		$List->buttonCheckbox = false;
		$List->buttonExport = false;

		$List->AddButton('View', $this->selfUri.'&action=view_trash&id=[id]&back=[back]', 'View', '<img src="img/cms/list/view.gif" width="8" height="14" border="0" align="absmiddle">');

		$List->MakeLinkButtons($this);

		//get user messages
		$messages = $this->GetUserTrash($Auth->user['id']);

		$this->Paging->SwitchOff();
		$this->pageContent .= $List->Render('cms_list', $messages);
	}

	function AddMessageForm()
	{
		$this->currentBookmark = 'New Message';

		$this->SetTitle($this->titleText.': Send', $this->titlePicture);
		$dataItem = Data::Create($this->diNameMessage);
	
        
		$this->pageContent .= $this->Parser->MakeForm($dataItem, 'cms/cp_mail/new', 'new_message');

		if ($this->setBack>0) $this->ParseBack();
	}

	function AddMessagePost()
	{
		GLOBAL $Auth;

		$dataItem = Data::Create($this->diNameMessage);
		$_POST['id_from'] = $Auth->user['id'];
		$dataItem->Insert($_POST);

		if (@$_POST['post_action'] == 2) Navigation::Jump(Navigation::Referer());
		else Navigation::JumpBack($this->back);
	}

	function ReplyMessageForm()
	{
		$this->SetTitle($this->titleText.': Reply');
        $id = intval($_GET['id']);
        
		$di = Data::Create($this->diNameMessage);

		$di->Get("id='$id'");

		$di->value['id_to'] = $di->value['id_from'];
		$di->value['subject'] = 'RE: '.$di->value['subject'];
		$di->value['message'] =
"



---------------------------------------------
".$di->value['message'];

		$this->pageContent .= $this->Parser->MakeForm($di, 'cms/cp_mail/reply', 'reply');

		if ($this->setBack>0) $this->ParseBack();
	}

	function ReplyMessagePost()
	{
		GLOBAL $Auth;
		
		$dataItem = Data::Create($this->diNameMessage);
		$_POST['id_from'] = $Auth->user['id'];
		$dataItem->Insert($_POST);

		Navigation::JumpBack($this->back);
	}


	function DeleteMessage()
	{
		GLOBAL $Auth;
		$userId = $Auth->user['id'];
		$dataItem = Data::Create($this->diNameMessage);

		//trash inbox
		$dataItem->Update("id='{$_GET['id']}' AND id_to='{$userId}'", array('is_deleted_to'=>1));
		//trash outbox
		$dataItem->Update("id='{$_GET['id']}' AND id_from='{$userId}'", array('is_deleted_from'=>1));

		Navigation::JumpBack($this->back);
	}

	function ViewInbox()
	{
		GLOBAL $Auth;
		$userId = $Auth->user['id'];
        $id = intval($_GET['id']);
        
		$this->currentBookmark = LANG_CP_MESSAGES_INBOX;
		$this->SetTitle($this->titleText.': Inbox');

		$dataItem = Data::Create($this->diNameMessage);
		$dataItem->Get("id=$id AND (is_deleted_to=0 AND id_to=$userId)");
        
		if (!Utils::IsArray($dataItem->value)) Navigation::Jump($this->selfUri);
		
		$this->AddLink('Reply', $this->selfUri.'&action=reply&id='.$id.'&back='.$this->back,'img/cp_mail/i_reply.gif', 'Send Reply');

		$dataItem->Update('id='.$id,array(
			'is_read'	=> 1
		));
		
		$this->pageContent .= $this->Parser->MakeView($dataItem, 'cms/cp_mail/view');

		$this->ParseBack();
	}

	function ViewOutbox()
	{
		GLOBAL $Auth;
		$userId = $Auth->user['id'];
		$id = intval($_GET['id']);

		$this->currentBookmark = LANG_CP_MESSAGES_OUTBOX;
		$this->SetTitle($this->titleText.': Outbox');

		$dataItem = Data::Create($this->diNameMessage);
		$dataItem->Get("id=$id AND (is_deleted_from=0 AND id_from=$userId)");
		
		if (!Utils::IsArray($dataItem->value)) Navigation::Jump($this->selfUri);
        
		$this->pageContent .= $this->Parser->MakeView($dataItem, 'cms/cp_mail/view');

		$this->ParseBack();
	}

	function ViewTrash()
	{
		GLOBAL $Auth;
		$userId = $Auth->user['id'];
		$id = intval($_GET['id']);

		$this->currentBookmark = LANG_CP_MESSAGES_TRASH;
		$this->SetTitle($this->titleText.': Trash');

		$dataItem = Data::Create($this->diNameMessage);
		$dataItem->Get("id=$id AND ((is_deleted_to=1 AND id_to=$userId) OR (is_deleted_from=1 AND id_from=$userId))");
		
		if (!Utils::IsArray($dataItem->value)) Navigation::Jump($this->selfUri.'&action=trash');
		        
		$this->pageContent .= $this->Parser->MakeView($dataItem, 'cms/cp_mail/view');

		$this->ParseBack();
	}

	function GetUserInbox($userId)
	{
		$di = Data::Create('cp_message');
		$cond = "id_to='$userId' AND  is_deleted_to=0";
		return $di->SelectValues($cond);
	}

	function GetUserOutbox($userId)
	{
		$di = Data::Create('cp_message');
		$cond = "id_from='$userId' AND  is_deleted_from=0";
		return $di->SelectValues($cond);
	}

	function GetUserTrash($userId)
	{
		$di = Data::Create('cp_message');
		$cond = "(id_to='$userId' AND  is_deleted_to=1) OR (id_from='$userId' AND  is_deleted_from=1)";
		return $di->SelectValues($cond);
	}
}

$Page = new CpMessagesPage();
$Page->Render();

?>