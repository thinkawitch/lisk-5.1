<?php

$GLOBALS['SUPPORT_FORM_MODULE_INFO'] = array(
	'name'			=> 'Support Form',
	'sys_name'		=> LiskModule::MODULE_SUPPORT,
	'version'		=> '5.0',
	'description'	=> 'Support Form',
	'object_name'	=> 'SupportForm',
	'multiinstance'	=> true,
	'ss_integrated'	=> true
);

$GLOBALS['LIST_SUPPORT_RENDER_TYPE'] = array(
	1	=> 'Auto Form',
	2	=> 'Custom'
);

class SupportForm extends LiskModule
{
	/**
	 * Render type autoform/custom
	 *
	 * @var int
	 */
	public $confRender;


	public $confInstalledPath;

	public $confNewContactNotify;
	public $confNewReplyNotify;

	public $tplPath = 'modules/support_';

	public $ticketDIName;
	public $messageDIName;
	public $departmentDIName;
	public $replyTemplateDIName;

	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_SUPPORT;
		if ($instanceId != null) $this->Init($instanceId);
	}

	function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->version = $GLOBALS['SUPPORT_FORM_MODULE_INFO']['version'];
		$this->tplPath.=$instanceId.'/';

		$this->confRender				= $this->config['render'];
		$this->confInstalledPath		= $this->config['installed_path'];
		$this->confNewContactNotify		= ($this->config['new_contact_notify'] == 1);
		$this->confNewReplyNotify		= ($this->config['new_reply_notify'] == 1);

		//init DI
		$this->ticketDIName = 'dyn_support_ticket_'.$instanceId;
		$this->messageDIName = 'dyn_support_message_'.$instanceId;
		$this->departmentDIName = 'dyn_support_departments_'.$instanceId;
		$this->replyTemplateDIName = 'dyn_support_reply_template_'.$instanceId;
	}

	function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['render']					= $this->confRender;
		$this->config['new_contact_notify']		= $this->confNewContactNotify ? 1 : 0;
		$this->config['new_reply_notify']		= $this->confNewReplyNotify ? 1 : 0;


		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	function InstallConfigure($instanceId,$params)
	{
		$GLOBALS['App']->LoadModule('modules/support_form/support_form.install.mod.php',1);
		installSupportModule($instanceId,$params['path']);
	}

	function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/support_form/support_form.install.mod.php',1);
		uninstallSupportModule($this->iid, $this->IsLastInstance());
		parent::Uninstall();
	}

	function Render()
	{
		GLOBAL $Parser,$Page;

		if (isset($_POST['action']) && $_POST['action']=='submit')
		{
			$this->__SubmitNewMessage($_POST);
			Navigation::Jump(Navigation::Jump('confirmation/'));
		}

		if (isset($Page->parameters[0]) && $Page->parameters[0] == 'confirmation')
		{
			return $this->RenderConfirmationPage();
		}

		if (isset($Page->parameters[0]) && $Page->parameters[0] == 'ticket')
		{
			return $this->__RenderTicketPage($Page->parameters[1]);
		}

		$SupportTicket = new Data($this->ticketDIName);

		//add departments
		$SupportTicket->ReSet('department');

		switch ($this->confRender)
		{
			case 1:
				return $Parser->MakeDynamicForm($SupportTicket, $this->tplPath.'dynamic_form', 'form');
				break;
				
			case 2:
				return $Parser->MakeForm($SupportTicket, $this->tplPath.'custom_form', 'form');
				break;
		}
	}

	function __RenderTicketPage($ticketId)
	{
		GLOBAL $Parser,$App;

		$Ticket = new Data($this->ticketDIName);
		$Message = new Data($this->messageDIName);

		$Ticket->Get("ticket_id='{$ticketId}'");
		$clientName = $Ticket->value['from_first_name'].' '.$Ticket->value['from_last_name'];

		if (!Utils::IsArray($Ticket->value)) $App->RaiseError("Incorrect ticket ID");

		if (@$_POST['action']=='reply')
		{
			$_POST['ticket_id'] = $ticketId;
			$_POST['is_client'] = 1;
			$_POST['name']	=  $clientName;
			$Message->ReSet('date');
			$Message->Insert($_POST);

			if ($this->confNewReplyNotify)
			{
				$App->Load('mail', 'utils');
				$Email = new EMail('support_notification_'.$this->iid);
				$Email->ParseVariables(array_merge($Ticket->value, $_POST));
				$Email->Send();
			}

			$Ticket->Update("ticket_id='{$ticketId}'", array(
				'require_reply'	=> 1
			));

			Navigation::Jump(Navigation::Referer());
		}

		$Message->Select("ticket_id='{$Ticket->value['ticket_id']}'", null, 'date DESC');

		if (Utils::IsArray($Message->values))
		{
		    foreach ($Message->values as $row)
		    {
    			$messages[]=array(
    				'name'		=> $row['name'],
    				'date'		=> Format::DateTime($row['date'], 'Y-m-d H:i'),
    				'message'	=> nl2br($row['message'])
    			);
		    }
		}

		$messages[] = array(
			'name'		=> $clientName,
			'date'		=> Format::DateTime($Ticket->value['date'], 'Y-m-d H:i'),
			'message'	=> nl2br($Ticket->value['message'])
		);

		$Parser->SetAddVariables(array(
			'messages'	=> sizeof($messages)
		));
		$rez = $Parser->MakeView($Ticket,$this->tplPath.'ticket', 'ticket');

		$rez .= $Parser->GetHtml($this->tplPath.'ticket', 'reply');

		$rez .= $Parser->MakeList($messages, $this->tplPath.'ticket', 'messages');

		return $rez;
	}

	function __SubmitNewMessage($values)
	{
		GLOBAL $App;
		$App->Load('mail', 'utils');

		//check if ticket_id and if it's empty generate new ticket id
		if (!isset($values['ticket_id']) || !strlen($values['ticket_id']))
		{
			$values['ticket_id'] = $this->__GenerateTicketId();
		}

		//insert message into support tickets table
		$SupportTicket = new Data($this->ticketDIName);

		$SupportTicket->ReSet('date');
		$SupportTicket->Insert($values);

		if ($this->confNewContactNotify)
		{
			$App->Load('mail', 'utils');
			$Email = new EMail('support_notification_'.$this->iid);
			$Email->ParseVariables($values);
			$Email->Send();
		}
	}

	function RenderConfirmationPage()
	{
		GLOBAL $Parser;
		return $Parser->GetHtml($this->tplPath.'confirmation', 'view');
	}

	function __GenerateTicketId()
	{
		return uniqid('fc');
	}
}
?>