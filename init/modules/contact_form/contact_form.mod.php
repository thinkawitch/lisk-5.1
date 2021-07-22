<?php

$GLOBALS['CONTACT_FORM_MODULE_INFO'] = array(
	'name'			=> 'Contact Form',
	'sys_name'		=> LiskModule::MODULE_CONTACT_FORM,
	'version'		=> '5.0.1',
	'description'	=> 'Contact Form',
	'object_name'	=> 'ContactForm',
	'multiinstance'	=> true,
	'ss_integrated'	=> true
);

$GLOBALS['LIST_CONTACT_FORM_RENDER_TYPE'] = array(
	1	=> 'Auto Form',
	2	=> 'Custom'
);

class ContactForm extends LiskModule
{

	/**
	 * Enabled/Disabled departments flag
	 *
	 * @var boolean
	 */
	public $confUseDepartments;
	/**
	 * Enabled/Disabled Archive
	 *
	 * @var boolean
	 */
	public $confUseArchive;
	/**
	 * Departments field caption (auto form)
	 *
	 * @var string
	 */
	public $confDepartmentsCaption;
	/**
	 * Main (form) Dyn. DI name
	 *
	 * @var string
	 */
	public $confDIName;
	/**
	 * Default send to (departments==false)
	 *
	 * @var string
	 */
	public $confDefaultSendTo;
	/**
	 * Render type autoform/custom
	 *
	 * @var int
	 */
	public $confRender;
	public $confDIDepartmentsName;

	/**
	 * Enabled/Disabled visitor confirmation on form submit
	 * via email
	 *
	 * @var boolean
	 */
	public $confUseConfirmation;
	
	
	/**
	 * Enabled/Disabled export (cUrl Post) 
	 *
	 * @var boolean
	 */
	public $confExportEnabled;

	/**
	 * Export URI 
	 *
	 * @var boolean
	 */
	public $confExportURI;	
	
	/**
	 * Export 'from_site' identificator 
	 *
	 * @var boolean
	 */
	public $confExportFromSite;		

	public $tplPath = 'modules/contact_form_';

	/**
	 * Constructor
	 *
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_CONTACT_FORM;
		if ($instanceId!=null) $this->Init($instanceId);
	}

	public function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->version  = $GLOBALS['CONTACT_FORM_MODULE_INFO']['version'];
		$this->tplPath .= $instanceId.'/';

		$this->confDIName			  = $this->config['di_name'];
		$this->confUseDepartments	  = ($this->config['use_departments']==1);
		$this->confUseArchive		  = ($this->config['use_archive']==1);
		$this->confDepartmentsCaption = $this->config['departments_caption'];
		$this->confDefaultSendTo	  = $this->config['default_send_to'];
		$this->confRender			  = $this->config['render'];
		$this->confDIDepartmentsName  = $this->config['di_departments'];
		$this->confUseConfirmation    = $this->config['use_confirmation'];
		
		//export settings
		$this->confExportEnabled		= $this->config['export_enabled'];
		$this->confExportURI			= $this->config['export_uri'];
		$this->confExportFromSite		= $this->config['export_from_site'];				

		$this->Debug('confDIName',$this->confDIName);
	}

	public function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['di_name'] 			= $this->confDIName;
		$this->config['use_departments']	= ($this->confUseDepartments)?1:0;
		$this->config['use_archive']		= ($this->confUseArchive)?1:0;
		$this->config['departments_caption']= $this->confDepartmentsCaption;
		$this->config['default_send_to']	= $this->confDefaultSendTo;
		$this->config['render']				= $this->confRender;
		$this->config['use_confirmation']   = ($this->confUseConfirmation) ? 1 : 0;
		
		$this->config['export_enabled']		= ($this->confExportEnabled) ? 1 : 0;
		$this->config['export_uri']			= $this->confExportURI;
		$this->config['export_from_site']	= $this->confExportFromSite;

		$Db->Update("id={$this->iid}",array(
			'config'	=>serialize($this->config)
		),'sys_modules');
	}

	public function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/contact_form/contact_form.install.mod.php',1);
		installContactFormModule($instanceId, $params['path']);
	}

	public function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/contact_form/contact_form.install.mod.php',1);
		uninstallContactFormModule($this->iid);
		parent::Uninstall();
	}

	public function Render()
	{
		GLOBAL $Parser,$Page;

		$postAction = isset($_POST['action']) ? $_POST['action'] : null;
		$param1 = isset($Page->parameters[0]) ? $Page->parameters[0] : null;

		if ($postAction == 'submit')
		{
			$this->SubmitNewMessage($_POST);
			Navigation::Jump('confirmation/');
		}

		if ($param1 == 'confirmation')
		{
			return $this->RenderConfirmationPage();
		}

		$DI = Data::Create($this->confDIName);

		//add departments
		if ($this->confUseDepartments)
		{

			$DI->ReSet('department');
			if (isset($DI->fields['department']) && is_object($DI->fields['department']))
			{
				$DI->fields['department']->label = $this->confDepartmentsCaption;
			}
		}
        
		StatActionHandler::Set('STAT_OBJECT_CONTACT', 'STAT_OBJECT_CONTACT_VIEW');
		
		switch ($this->confRender)
		{
			case 1:
				return $Parser->MakeDynamicForm($DI, $this->tplPath.'dynamic_form', 'form');
				break;
				
			case 2:
				return $Parser->MakeForm($DI, $this->tplPath.'custom_form', 'form');
				break;
		}

		return '';
	}

	public function SubmitNewMessage($values)
	{
		GLOBAL $Parser,$App;
		$App->Load('mail', 'utils');

		$originalValues = $values;
		
		$DI = new Data($this->confDIName);
		$DI->value = $values;
		$DI->ReSet('date');

		// Archive message
		if ($this->confUseArchive)
		{
			//fix browser bug with fields with spaces
			foreach ($values as $fieldName=>$fieldValue)
			{
				$values [ str_replace('_', ' ', $fieldName) ] = $fieldValue;
			}
			$DI->Insert($values);
		}

		// init email
		$Email = new EMail('contact_form_'.$this->iid, 'contact_form_'.$this->iid);

		// compose message body to send via email
		$visibleFields = $DI->GetFieldsByType(LiskType::TYPE_PROP.','.LiskType::TYPE_DATE.','.LiskType::TYPE_LIST.','.LiskType::TYPE_DATETIME.','.LiskType::TYPE_FILE.','.LiskType::TYPE_FLAG.','.LiskType::TYPE_HTML.','.LiskType::TYPE_IMAGE.','.LiskType::TYPE_INPUT.','.LiskType::TYPE_TEXT);
		$visibleFields = explode(',', $visibleFields);
		$rez = null;
		
		foreach ($visibleFields as $field)
		{
			switch ($DI->fields[$field]->type)
			{
			    case LiskType::TYPE_PROP:
            		$propValue = '';
            		foreach ($originalValues as $key => $val)
            		{
            			$matches = array();
            			if (($val == 1) && preg_match('/^'.preg_quote($field).'_(\d+)/', $key, $matches ))
            			{
            				$propValue .= '<'.$matches[1].'>';
            			}
            		}
			        $DI->fields[$field]->value = $propValue;
					$rez[]=array(
						'caption'	=> $DI->fields[$field]->label,
						'value'		=> $DI->fields[$field]->Render()
					);
			        break;
				case LiskType::TYPE_FILE:
					if ($_FILES[$DI->fields[$field]->uploadHttpName]['error']==0 )
					{
						$tmpName	= $_FILES[$DI->fields[$field]->uploadHttpName]['tmp_name'];
						$realName	= $_FILES[$DI->fields[$field]->uploadHttpName]['name'];
						$Email->AddAttachment(array(
							'file'		=> $tmpName,
							'filename'	=> $realName
						));
					}
					break;
				case LiskType::TYPE_DATE:
					$check = array('_year', '_month', '_day');
					$passed = true;
					foreach ($check as $one)
					{
						if (!isset($values[$field.$one]))
						{
							$passed = false;
							break;
						}
					}
					if ($passed)
					{
						$DI->fields[$field]->value= sprintf('%04d-%02d-%02d',
													$DI->value[$field.'_year'],
													$DI->value[$field.'_month'],
													$DI->value[$field.'_day']);
					}
					$rez[] = array(
						'caption'	=> $DI->fields[$field]->label,
						'value'		=> $DI->fields[$field]->Render()
					);
					break;
				case LiskType::TYPE_DATETIME:
					$check = array('_year', '_month', '_day', '_hour', '_minute');
					$passed = true;
					foreach ($check as $one)
					{
						if (!isset($values[$field.$one]))
						{
							$passed = false;
							break;
						}
					}
					if ($passed)
					{
						$DI->fields[$field]->value = sprintf('%04d-%02d-%02d %02d:%02d:%02d',
													$DI->value[$field.'_year'],
													$DI->value[$field.'_month'],
													$DI->value[$field.'_day'],
													$DI->value[$field.'_hour'],
													$DI->value[$field.'_minute'],
													'00');
					}
					$rez[] = array(
						'caption'	=> $DI->fields[$field]->label,
						'value'		=> $DI->fields[$field]->Render()
					);
					break;
				default:
					$DI->fields[$field]->value = $DI->value[$field];
					$rez[]=array(
						'caption'	=> $DI->fields[$field]->label,
						'value'		=> $DI->fields[$field]->Render()
					);
					break;
			}
		}
		$html = $Parser->MakeList($rez, $this->tplPath.'mail_notification', 'list');

		if ($this->confUseDepartments)
		{
			$DI->ReSet('department');
			$Department = Data::Create($this->confDIDepartmentsName);
			$toSendEmail = $Department->GetValue("id={$values['department']}", 'email');
		}
		else
		{
			$toSendEmail = $this->confDefaultSendTo;
		}

		$Email->ParseVariables(array(
			'email_to'	=> $toSendEmail,
			'contact_form'	=> $html
		));

		$Email->Send();
		
		StatActionHandler::Set('STAT_OBJECT_CONTACT', 'STAT_OBJECT_CONTACT_SUBMIT');
		
		//confirm visitor about form sent, if email is provided
		if ($this->confUseConfirmation && isset($values['email']{0}))
		{
		    $Email = new EMail('contact_form_confirmation_'.$this->iid, 'contact_form_confirmation_'.$this->iid);
		    $Email->ParseVariables($values);
		    $Email->Send();
		}
		
		//stat_visit, action=lead
		$this->SetActionLead();
		
		//EXPORT cUrl POST 
		if ($this->confExportEnabled)
		{
			$exportValues = $_POST;
			$exportValues['from_site']	= $this->confExportFromSite;
		
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->confExportURI);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $exportValues);
			$result = curl_exec($ch);
			curl_close($ch);
		}
		
	}

	/**
	 * Set action=lead for stat_visit module
	 */
	public function SetActionLead()
	{
		GLOBAL $App;
		$App->Load('stat_visit', 'mod');
		$svIid = StatVisit::GetInstalledIid();
		if (!$svIid) return;
		
		$sv = new StatVisit($svIid);
		$sv->InitVisit();
		$sv->SaveActionLead();
	}

	public function RenderConfirmationPage()
	{
		GLOBAL $Parser;
		
		$this->SetActionLead();
		
		return $Parser->GetHtml($this->tplPath.'confirmation', 'view');
	}

	/**
	 * Run snippet method
	 *
	 * @param array $params
	 * @return string
	 */
	public function Snippet($params)
	{
		switch (strtolower($params['name']))
		{
			case 'form':
				return $this->Render();
				break;
		}
		return '';
	}

	/**
	 * Get all available snippets of module
	 *
	 * @return array
	 */
	public function AvailableSnippets()
	{
		return array(
			'form' => array(
				'description' => 'Display contact form',
				'code' => '<lisk:snippet src="module" instanceId="[iid]" name="form" />',
			),
		);
	}

}
?>