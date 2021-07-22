<?php
require_once('init/init.php');

class CpMailDispatcherPage extends CPPage
{
    private $selfUri = 'mail_dispatcher.php?1=1';
    
	function __construct()
	{
		parent::__construct();
		$this->App->Load('mail_dispatcher', 'obj');
		
		$this->AddBookmark('Emails To Send', $this->selfUri.'&action=new', 'img/modules/newsletter/subscribers_list.gif');
		$this->AddBookmark('History', $this->selfUri.'&action=history', 'img/modules/newsletter/archive.gif');
		$this->AddBookmark('Settings', $this->selfUri.'&action=settings', 'img/modules/newsletter/settings.gif');
		
		$this->SetGetAction('new', 'EmailsToSend');
		$this->SetGetAction('history', 'EmailsHistory');
		$this->SetGetAction('clear_history', 'ClearHistory');
		
		$this->SetGetPostAction('settings', 'submit', 'SettingsSubmit');
		$this->SetGetAction('settings', 'SettingsForm');
	}

	function Page()
	{
		$this->EmailsToSend();
	}
	
	function EmailsToSend()
	{
	    GLOBAL $Paging;
	    $this->title = 'Mail Dispatcher';
		$this->currentBookmark = 'Emails To Send';
		
		$list = new CMSList('Obj_email_queue');
		$list->Init();
		$list->Load('mail_dispatcher', 'obj');
		$list->buttonAdd = false;
		$list->dataItem->ReSet('cp_list');
		
		$column = new CMSListColumn('recipients');
		$column->isSortable = false;
		$list->columns[] = $column;
		
		$list->MakeLinkButtons();
		
		$list->handlerPostProcess = array($this, 'RenderQueueRecipients');
		
		$this->SetBack();
		
		$Paging->SwitchOn('cp');
		
		$this->pageContent.= $list->Render();
	}
	
    function RenderQueueRecipients($row)
	{
	    $recipients = '';
	    $rows = $this->Db->Query('SELECT * FROM sys_email_queue_recipients WHERE parent_id='.$row['id'].' LIMIT 5');
	    if (Utils::IsArray($rows))
	    {
	        foreach ($rows as $one)
	        {
	            $recipients .= $one['email'].',';
	        }
	        $recipients = substr($recipients, 0, -1);
	    }
	    $row['recipients'] = Format::StrSpaces($recipients, 50, ',');
	    return $row;
	}

    function EmailsHistory()
	{
	    GLOBAL $Paging;
	    $this->title = 'Mail History';
		$this->currentBookmark = 'History';
		
		$this->AddLink('Clear all', $this->selfUri.'&action=clear_history" onclick="return ShowConfirm(\'Clear all mail history?\', this)', 'img/cms/list/delete.gif');
		
		$list = new CMSList('Obj_email_history');
		$list->Init();
		$list->Load('mail_dispatcher', 'obj');
		$list->buttonAdd = false;
		$list->dataItem->ReSet('cp_list');
		
		$column = new CMSListColumn('recipients');
		$column->isSortable = false;
		$list->columns[] = $column;
		
		$list->MakeLinkButtons();
		
		$list->handlerPostProcess = array($this, 'RenderHistorysRecipients');
		
		$this->SetBack();
		
		$Paging->SwitchOn('cp');
		$this->pageContent.= $list->Render();
	}
	
    function RenderHistorysRecipients($row)
	{
	    $recipients = '';
	    $rows = $this->Db->Query('SELECT * FROM sys_email_history_recipients WHERE parent_id='.$row['id'].' LIMIT 5');
	    if (Utils::IsArray($rows))
	    {
	        foreach ($rows as $one)
	        {
	            $recipients .= $one['email'].',';
	        }
	        $recipients = substr($recipients, 0, -1);
	    }
	    $row['recipients'] = Format::StrSpaces($recipients, 50, ',');
	    return $row;
	}
	
	function ClearHistory()
	{
	    $di = Data::Create('Obj_email_history');
	    $di->Delete('1');
	    Navigation::Jump($this->selfUri.'&action=history');
	}
	
	function SettingsForm()
	{
	    $this->title = 'Mail Dispatcher Settings';
		$this->currentBookmark = 'Settings';
		
		$this->SetBack();
		if ($this->setBack>0) $this->ParseBack();
		
		$this->App->Load('cpmodules', 'lang');
		$this->App->Load('settings', 'class');
		$settings = Settings::Get('mail_dispatcher');
		
		$di = Data::Create('dispatcher_settings');
		$di->value = $settings;
		
		$this->pageContent .= $this->Parser->MakeDynamicForm($di, 'cms/edit');
	}
	
	function SettingsSubmit()
	{
	    $settings = array(
	        'mailer_type' => $_POST['mailer_type']
	    );
	    
	    $this->App->Load('settings', 'class');
	    Settings::Set('mail_dispatcher', $settings);
	    
	    Navigation::Jump($this->selfUri.'&action=settings');
	}
	
}

$Page = new CpMailDispatcherPage();
$Page->Render();
?>