<?php
chdir('../');
require_once('init/init.php');

class CpSupportPage extends CPModulePage
{
    /**
     * @var SupportForm
     */
	private $Support;
	
	private $urlPrefix = 'module_support_form.php?z=x';

	function __construct()
	{
		parent::__construct(true);

		$this->App->Load(LiskModule::MODULE_SUPPORT, 'mod');

		$this->titlePicture = 'modules/support/uho.gif';
		$this->Support = new SupportForm($this->iid);

		$this->AddBookmark('Overview', $this->urlPrefix.'&action=overview', 'img/modules/support/archive.gif');
		//$this->AddBookmark('Calendar', $this->urlPrefix.'&action=calendar', 'img/modules/support/archive.gif');
		$this->AddBookmark('Search', 	$this->urlPrefix.'&action=search', 'img/modules/support/archive.gif');
		$this->AddBookmark('Templates', $this->urlPrefix.'&action=templates', 'img/modules/support/archive.gif');

		$this->AddBookmark('Departments', $this->urlPrefix.'&action=departments', 'img/modules/support/departments.gif');

		$this->AddBookmark('E-Mail Template', $this->urlPrefix.'&action=email', 'img/modules/support/mailtemplate.gif');
		$this->AddBookmark('E-Mail Notification', $this->urlPrefix.'&action=email_notification', 'img/modules/support/mailtemplate.gif');
		$this->AddBookmark('Settings', $this->urlPrefix.'&action=settings', 'img/modules/support/settings.gif');

		$this->SetGetAction('overview', 'Overview');
		$this->SetGetAction('departments', 'Departments');

		//Search
		$this->SetGetAction('search', 'Search');

		// Email Template
		$this->SetGetAction('email', 'EmailTemplate');
		$this->SetGetAction('email_notification', 'EmailNotification');
		$this->SetGetPostAction('email', 'submit', 'SubmitEmailTemplate');

		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');

		//View & reply
		$this->SetGetAction('view', 'View');
		$this->SetGetPostAction('view', 'reply', 'ReplySubmit');

		//Reply template
		$this->SetGetAction('templates', 'Templates');
	}

	function Page() 
	{
		Navigation::Jump($this->urlPrefix.'&action=overview');
	}

	function Overview()
	{
		GLOBAL $Parser,$Db,$App;

		$this->SetBack();
		$this->currentBookmark = 'Overview';

		$Ticket = new Data($this->Support->ticketDIName);
		$Message = new Data($this->Support->messageDIName);

		$departmentsTable = 'mod_support_departments_'.$this->iid;

		$rows = $Db->Query("SELECT t.*, COUNT(m.id) as messages, d.name as department
		FROM {$Ticket->table} t
		LEFT JOIN {$Message->table} m ON m.ticket_id=t.ticket_id
		LEFT JOIN {$departmentsTable} d ON d.id=t.department
		WHERE t.require_reply=1
		GROUP BY t.ticket_id");

		if (Utils::IsArray($rows)) foreach ($rows as $key=>$row) {
			$rows[$key]['date'] = Format::DateTime($row['date'],'Y-m-d h:i');
		}

		$Parser->SetListDecoration('ListTD1', 'ListTD2');
		$Parser->SetAddVariables(array(
			'back'	=> $this->setBack
		));
		$list = $Parser->MakeList($rows, 'modules/support/overview', 'list');

		//calendar
		$App->Load('calendar', 'class');
		$Calendar = new Calendar($this->urlPrefix.'&action=archive', $Ticket->table);
		$Calendar->SetTplName('modules/support/calendar');


		$this->pageContent.=$Parser->MakeView(array(
			'calendar'	=> $Calendar->Render(),
			'list'		=> $list
		), 'modules/support/overview', 'view');
	}

	function Templates() {
		$this->SetBack();
		$this->currentBookmark = 'Templates';

		$List = new CMSList($this->Support->replyTemplateDIName);
        $List->Init();
		$List->MakeLinkButtons();

		$this->pageContent .= $List->Render();
	}

	function View() 
	{
		GLOBAL $Parser,$App;
		$this->ParseBack();

		$Ticket = new Data($this->Support->ticketDIName);
		$Message = new Data($this->Support->messageDIName);

		$Ticket->ReSet('department');

		$Ticket->Get("id={$_GET['id']}");

		$clientName = $Ticket->value['from_first_name'].' '.$Ticket->value['from_last_name'];

		$Parser->SetAddVariables(array(
			'reply_required'	=> ($Ticket->value['require_reply'] == 1) ? 'Yes' : 'No'
		));
		
		$this->pageContent .= $Parser->MakeView($Ticket, 'modules/support/view','ticket');

		$Message->Select("ticket_id='{$Ticket->value['ticket_id']}'", null, 'date DESC');

		if (Utils::IsArray($Message->values)) foreach ($Message->values as $row) {
			$messages[] = array(
				'name'		=> $row['name'],
				'date'		=> Format::DateTime($row['date'], 'Y-m-d H:i'),
				'message'	=> nl2br($row['message'])
			);
		}

		$messages[] = array(
			'name'		=> $clientName,
			'date'		=> Format::DateTime($Ticket->value['date'], 'Y-m-d H:i'),
			'message'	=> nl2br($Ticket->value['message'])
		);

		$this->pageContent .= $Parser->MakeList($messages, 'modules/support/view', 'messages');

		$Template = new Data($this->Support->replyTemplateDIName);
		$rows = $Template->SelectValues();
		$values = array();
		$valuesParagraph = array();
		if (Utils::IsArray($rows)) foreach ($rows as $row) {
			$content = addslashes($row['content']);
			$content = str_replace('%FROM_FIRST_NAME%', $Ticket->value['from_first_name'], $content);
			$content = str_replace('%FROM_LAST_NAME%', $Ticket->value['from_last_name'], $content);
			if ($row['template_type'] == 0)
			{
				$values[$content] = $row['name'];
			}
			else
			{
				$valuesParagraph[$content] = $row['name'];
			}
		}

		$App->Load('list', 'type');
		$Field = new T_list(array(
			'name'			=> 'template',
			'object'		=> 'arr',
			'values'		=> $values,
			'add_values'	=> array(
				0	=> '-- Please select --'
			)
		));
		$Field->AddFormParam('id', 'templatesList');

		$FieldParagraph = new T_list(array(
			'name'			=> 'template_paragraph',
			'object'		=> 'arr',
			'values'		=> $valuesParagraph,
			'add_values'	=> array(
				0	=> '-- Please select --'
			)
		));
		$FieldParagraph->AddFormParam('id', 'template_paragraph');

		$this->pageContent .= $Parser->MakeView(array(
			'template_field'	=> $Field->RenderFormView(),
			'paragraph_field'	=> $FieldParagraph->RenderFormView()
		), 'modules/support/view', 'reply');

	}

	function ReplySubmit()
	{
		GLOBAL $Auth;

		$Ticket = new Data($this->Support->ticketDIName);
		$Message = new Data($this->Support->messageDIName);
		$Message->ReSet('date');
		
		$id = intval($_GET['id']);
		
		$ticketInfo = $Ticket->GetValue('id='.$id);

		//Junk
		if ($_POST['Submit'] == 'Junk')
		{
			$Ticket->Update('id='.$id, array(
				'require_reply'	=> 0
			));
			Navigation::Jump($this->urlPrefix.'&action=view&id='.$id);
		}

		$_POST['is_client'] = 0;
		$_POST['name'] = $Auth->user['login'];
		$_POST['ticket_id']	= $Ticket->GetValue('id='.$id, 'ticket_id');

		$Message->Insert($_POST);

		$this->__SendEmailToClient($ticketInfo['ticket_id'], $ticketInfo['from_email'], $_POST['message']);
		
		
		$Ticket->Update('id='.$id, array(
			'require_reply'	=>  ($_POST['replied'] == 1) ? 1 : 0
		));
		

		Navigation::Jump($this->urlPrefix.'&action=view&id='.$_GET['id']);
	}

	function __SendEmailToClient($ticketId, $email, $message)
	{
		GLOBAL $App;
		$App->Load('mail', 'utils');

		$Email = new EMail('support_'.$this->iid);

		$ticketLink = 'http://'.$_SERVER['HTTP_HOST'].$App->httpRoot.$this->Support->confInstalledPath.'ticket/'.$ticketId.'/';

		$Email->ParseVariables(array(
			'message'		=> $message,
			'ticket_link'	=> $ticketLink,
			'email_to'		=> $email
		));

		$Email->Send();
	}

	function Departments()
	{
		$this->SetBack();
		$this->currentBookmark = 'Departments';
		$this->SetTitle('Support Departments');

		$List = new CMSList($this->Support->departmentDIName);
		$List->Init();
		$List->MakeLinkButtons();
		$List->buttonView = false;

		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $List->Render();
	}

	function Search()
	{
		GLOBAL $Db, $Parser;

		$this->SetBack();
		$this->currentBookmark = 'Search';

		$Ticket = new Data($this->Support->ticketDIName);
		$Message = new Data($this->Support->messageDIName);
		$departmentsTable = 'mod_support_departments_'.$this->iid;

		$Ticket->SetFields(array(
			'ticket_id'	=> LiskType::TYPE_INPUT
		));
		$Ticket->value = $_GET;
		$this->pageContent .= $Parser->MakeForm($Ticket, 'modules/support/search', 'form');


		//create condition
		$cond = "1=1";

		$fields = array('from_last_name', 'from_email', 'ticket_id');
		foreach ($fields as $field)
		{
			if (isset($_GET[$field]) && strlen($_GET[$field]))
			{
				$cond.=" AND t.$field LIKE '%{$_GET[$field]}%'";
			}
		}

		$rows = $Db->Query("SELECT t.*, COUNT(m.id) as messages, d.name as department
		FROM {$Ticket->table} t
		LEFT JOIN {$Message->table} m ON m.ticket_id=t.ticket_id
		LEFT JOIN {$departmentsTable} d ON d.id=t.department
		WHERE {$cond}
		GROUP BY t.ticket_id");

		if (Utils::IsArray($rows)) foreach ($rows as $key=>$row)
		{
			$rows[$key]['date'] = Format::DateTime($row['date'],'Y-m-d h:i');
			$rows[$key]['require_reply'] = ($row['require_reply']==1) ? 'Yes' : 'No';
		}

		$Parser->SetListDecoration('ListTD1','ListTD2');
		$Parser->SetAddVariables(array(
			'back'	=> $this->setBack
		));
		$list = $Parser->MakeList($rows,'modules/support/search','list');

		$this->pageContent .= $list;

	}

	/*function Archive() {
		GLOBAL $Parser,$App;
		$App->Load('calendar','class');

		$DI = new Data($this->Support->confDIName);

		$Calendar = new Calendar("?action=archive",$DI->table);
		$Calendar->SetTplName('modules/support/calendar');

		$this->SetBack();
		$this->currentBookmark = 'Archive';
		$this->SetTitle('Archive');

		$DI->Select("SUBSTRING(date, 1, 10)='{$Calendar->currentDate}'");

		$DIView = new Data($this->Support->confDIName);
		if ($this->Support->confUseDepartments) $DIView->ReSet('department');


		if (Utils::IsArray($DI->values)) {
			foreach ($DI->values as $row) {
				$DI->Get('id='.$row['id']);
				$date = $DI->value['date'];
				$date = Format::DateTime($date,"M d, H:i");
				$Parser->SetCaptionVariables(array(
					'date'	=> $date,
					'id'	=> $row['id'],
					'back'	=> $this->setBack,
					'diname'=> $this->Support->confDIName
				));

				$DIView->values = $DI->value;
				$listHtml .= '<br />'.$Parser->MakeDynamicView($DIView,'modules/support/archive','view_message');
			}
		} else {
			$listHtml = $Parser->GetHtml('modules/support/archive','empty');
		}

		$this->pageContent.=$Parser->MakeView(array(
			'calendar'	=> $Calendar->Render(),
			'list'		=> $listHtml
		),'modules/support/archive','view');
	}*/

	function EmailTemplate()
	{
		$this->SetTitle('E-Mail template');
		$this->currentBookmark = 'E-Mail Template';
		$this->SetBack();
		$EditEmail = new CMSEditEmail('support_'.$this->iid);
		$this->pageContent .= $EditEmail->Render();
	}

	function EmailNotification()
	{

		$EditEmail = new CMSEditEmail('support_notification_'.$this->iid);

		if (isset($_POST['action']) && $_POST['action'] == 'submit')
		{
			$EditEmail->Update();
			Navigation::Jump($this->urlPrefix.'&action=email_notification');
		}
		else
		{
			$this->SetTitle('E-Mail notification');
			$this->currentBookmark = 'E-Mail Notification';
			$this->SetBack();
			$this->pageContent .= $EditEmail->Render();
		}
	}

	function SubmitEmailTemplate()
	{
		$EditEmail=new CMSEditEmail('support_'.$this->iid);
		$EditEmail->Update();
		Navigation::Jump($this->urlPrefix.'&action=email');
	}

	function Settings()
	{
		GLOBAL $App,$Parser;
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('Support Form Settings');
		$this->currentBookmark = 'Settings';

		$this->settingsFields = array(
			'render'			=> array(
				'type'				=> LiskType::TYPE_LIST,
				'object'			=> 'def_support_render_type'
			),
			'new_contact_notify'	=> LiskType::TYPE_FLAG,
			'new_reply_notify'		=> LiskType::TYPE_FLAG
		);
		$this->settingsFieldsValues = array(
			'render'				=> $this->Support->confRender,
			'new_contact_notify'	=> $this->Support->confNewContactNotify ? 1 : 0,
			'new_reply_notify'		=> $this->Support->confNewReplyNotify ? 1 : 0,
		);

		//$this->customizableDI=array($this->Support->confDIName);

		$this->pageContent .= $this->RenderSettingsPage($this->Support);
	}

	function SaveSettings()
	{
		GLOBAL $Db,$App;

		$this->Support->confRender				= $_POST['render'];
		$this->Support->confNewContactNotify 	= ($_POST['new_contact_notify_checked'] == 1);
		$this->Support->confNewReplyNotify		= ($_POST['new_reply_notify_checked'] == 1);

		$this->Support->SaveSettings();
		Navigation::Jump($this->urlPrefix.'&action=settings');
	}

}

$Page = new CpSupportPage();
$Page->Render();

?>