<?php

$GLOBALS['NEWSLETTER_MODULE_INFO'] = array(
	'name'			=> 'Newsletter',
	'sys_name'		=> LiskModule::MODULE_NEWSLETTER,
	'version'		=> '5.0',
	'description'	=> 'Newsletter Module',
	'object_name'	=> 'Newsletter',
	'multiinstance'	=> false,
	'ss_integrated'	=> false
);

//this group can't be deleted
define('NEWSLETTER_GENERAL_GROUP_ID', 2);

define('NEWSLETTER_SEND_NOW',   0);
define('NEWSLETTER_SEND_CROND', 1);
define('NEWSLETTER_SEND_METHOD', NEWSLETTER_SEND_CROND);


/**
 * Module Newsletter main class
 *
 */
class Newsletter extends LiskModule
{
	
	/**
	 * Tree name
	 *
	 * @var string
	 */
	public $confTreeName;
	
	/**
	 * Group DataItem name
	 *
	 * @var string
	 */
	public $confDIGroupName;

	/**
	 * Subscriber DataItem name
	 *
	 * @var string
	 */
	public $confDISubscriberName;
	
	/**
	 * History DataItem name
	 *
	 * @var string
	 */
	public $confDIHistoryName;

	/**
	 * Guestbook base url
	 *
	 * @var string
	 */
	public $confBaseUrl;

	/**
	 * Templates path
	 *
	 * @var string
	 */
	public $tplPath = 'modules/newsletter_';
	
	/**
	 * Path for email templates
	 *
	 * @var string
	 */
	public $confTemplatesPath;
	
	/**
	 * Path for email attachments
	 *
	 * @var string
	 */
	public $confAttachmentsPath;
	
	/**
	 * Reply/From email address
	 *
	 * @var string
	 */
	public $confFromAddress;
	
	/**
	 * Attach html email body images
	 *
	 * @var boolean
	 */
	public $confAttachImages;
	
	/**
	 * Email send object
	 *
	 * @var EMail
	 */
	public $EMail;

	/**
	 * Constructor
	 *
	 * @param integer $instanceId
	 * @return Newsletter
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_NEWSLETTER;
		if ($instanceId!=null) $this->Init($instanceId);
	}

	/**
	 * Initialize module
	 *
	 */
	function Init($instanceId)
	{
		parent::Init($instanceId);
		
		$this->tplPath .= $instanceId.'/';
		
		$this->version = $GLOBALS['NEWSLETTER_MODULE_INFO']['version'];

		$this->confBaseUrl              = $this->config['base_url'];
		$this->confTreeName				= $this->config['tree_name'];
		$this->confDIGroupName 			= $this->config['di_name_group'];
		$this->confDISubscriberName		= $this->config['di_name_subscriber'];
		$this->confDIHistoryName		= $this->config['di_name_history'];
		$this->confTemplatesPath		= $this->config['email_tpl_path'];
		$this->confAttachmentsPath		= $this->config['attach_tpl_path'];
		$this->confFromAddress			= $this->config['from_address'];
		$this->confAttachImages			= $this->config['attach_images'];

		$this->Debug('tree_name', $this->confTreeName);
		$this->Debug('di_name_group', $this->confDIGroupName);
		$this->Debug('di_name_subscriber', $this->confDISubscriberName);
		$this->Debug('di_name_history', $this->confDIHistoryName);
		$this->Debug('from_address', $this->confFromAddress);
		$this->Debug('attach_images', $this->confAttachImages);
		
		
	}
	
	function InitEMail($templateName,$objectId)
	{
		GLOBAL $App;
		$App->Load('mail', 'utils');
		$this->EMail = new EMail($templateName,$objectId);
		$this->EMail->pathAttachmentFiles = $App->sysRoot.$App->filePath.$this->confAttachmentsPath;
		$this->EMail->attachBodyImages = $this->confAttachImages;
	}

	/**
	 * Save settings
	 *
	 */
	function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['base_url'] 			= $this->confBaseUrl;
		$this->config['tree_name']			= $this->confTreeName;
		$this->config['di_name_group']		= $this->confDIGroupName;
		$this->config['di_name_subscriber'] = $this->confDISubscriberName;
		$this->config['di_name_history']	= $this->confDIHistoryName;
		$this->config['email_tpl_path']		= $this->confTemplatesPath;
		$this->config['attach_tpl_path']	= $this->confAttachmentsPath;
		$this->config['from_address']		= $this->confFromAddress;
		$this->config['attach_images']		= $this->confAttachImages;
		
		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	/**
	 * Install module
	 *
	 * @param integer $instanceId
	 * @param array $params
	 */
	function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/newsletter/newsletter.install.mod.php', 1);
		installNewsletterModule($instanceId, $params['path']);
	}

	/**
	 * Uninstall module
	 *
	 */
	function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/newsletter/newsletter.install.mod.php', 1);
		uninstallNewsletterModule($this->iid);
		parent::Uninstall();
	}
	
	/**
	 * Render subscribe/unsubscribe form
	 *
	 * @return string
	 */
	function RenderSubscribeForm($params)
	{
		GLOBAL $Parser;
		if (!isset($params['group'])) $params['group'] = NEWSLETTER_GENERAL_GROUP_ID;
		$DI = new Data($this->confDISubscriberName);
		$Parser->SetCaptionVariables(array('group'=>$params['group']));
		return $Parser->MakeForm($DI, $this->tplPath.'newsletter', 'newsletter_subscribe');
	}
	
	/**
	 * General render method
	 * empty for now
	 *
	 */
	function Render()
	{
		
	}
	
	/**
	 * Render snippet subscribe
	 *
	 * @return string
	 */
	function SnippetSubscribe($params)
	{
		GLOBAL $App;

		if (@$_POST['action'] == 'subscribe')
		{
			$this->Subscribe($_POST);
			$App->SetError('You are successfully subscribed!');
			
			StatActionHandler::Set('STAT_OBJECT_NEWSLETTER', 'STAT_OBJECT_NEWSLETTER_SUBSCRIBE');
			
			Navigation::Jump(Navigation::Referer());
		}
		elseif (@$_POST['action']=='unsubscribe')
		{
			$this->Unsubscribe($_POST['email']);
			$App->SetError('You are successfully unsubscribed!');
			
			StatActionHandler::Set('STAT_OBJECT_NEWSLETTER', 'STAT_OBJECT_NEWSLETTER_UNSUBSCRIBE');
			
			Navigation::Jump(Navigation::Referer());
		}
		
		return $this->RenderSubscribeForm($params);
	}
	
	/**
	 * Subscribe new one
	 *
	 * @param array $values
	 */
	function Subscribe($values)
	{
		if (!isset($values['parent_id'])) $values['parent_id'] = NEWSLETTER_GENERAL_GROUP_ID;
		$values['name'] = '';
		$DI = new Data($this->confDISubscriberName);

		if (!$this->IsEmailExistsInGroup($values['email'], $values['parent_id']))
		{
			$DI->Insert($values);
		}
		else
		{
			// nothing to do
		}
	}
	
	/**
	 * Unsubscribe
	 *
	 * @param string $email
	 */
	function Unsubscribe($email)
	{
		$DI = new Data($this->confDISubscriberName);
		$DI->Delete('email='.Database::Escape($email));
	}
	
	/**
	 * Check if this email already exists in group
	 *
	 * @param string $email
	 * @param integer $groupId
	 * @return boolean
	 */
	function IsEmailExistsInGroup($email, $groupId)
	{
		$DI = new Data($this->confDISubscriberName);
		return (boolean) $DI->GetValue(
			'parent_id=' . Database::Escape($groupId) . ' AND email=' . Database::Escape($email)
		);
	}
	
	/**
	 * Run snippet method
	 *
	 * @param array $params
	 * @return string
	 */
	function Snippet($params)
	{
		switch (strtolower($params['name']))
		{
			case 'subscribe':
				return $this->SnippetSubscribe($params);
				break;
		}
		return '';
	}
	
	/**
	 * Get all available snippets of module
	 *
	 * @return array
	 */
	function AvailableSnippets()
	{
		$DI = new Data($this->confDIGroupName);
		$groups = $DI->SelectValues('parent_id=1');
		
		$snippets = array();
		
		if (!Utils::IsArray($groups)) return $snippets;
		
		foreach ($groups as $group)
		{
			$snippets['subscribe_'.Format::ToUrl($group['name'])] = array(
				'description' => 'Subscribe to `'.$group['name'].'` snippet',
				'code' => '<lisk:snippet src="module" instanceId="[iid]" name="subscribe" group="'.$group['id'].'" />',
			);
		}
		
		return $snippets;
	}
}

?>