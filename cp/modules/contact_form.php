<?php
chdir('../');
require_once('init/init.php');

class CpContactFormPage extends CPModulePage
{
	/**
	 * @var ContactForm
	 */
	private $ContactForm;
	//safari requires url for header Location:
    private $selfUri = 'module_contact_form.php';
    
	function __construct()
	{
		parent::__construct(true);

		$this->App->Load(LiskModule::MODULE_CONTACT_FORM, 'mod');

		$this->titlePicture = 'modules/contact_form/uho.gif';
		$this->ContactForm = new ContactForm($this->iid);


		if ($this->ContactForm->confUseArchive)
		{
			$this->AddBookmark('Archive', '?action=archive', 'img/modules/contact_form/archive.gif');
		}
		if ($this->ContactForm->confUseDepartments)
		{
			$this->AddBookmark('Departments', '?action=departments', 'img/modules/contact_form/departments.gif');
		}
		$this->AddBookmark('E-Mail Template', '?action=email', 'img/modules/contact_form/mailtemplate.gif');
	    if ($this->ContactForm->confUseConfirmation)
		{
			$this->AddBookmark('Confirmation E-Mail', '?action=email_confirmation', 'img/modules/contact_form/mailtemplate.gif');
		}
		
		//Lead export tab
		if ($this->ContactForm->confExportEnabled)
		{
			$this->AddBookmark('Export','?action=export', 'img/modules/contact_form/departments.gif');
			$this->SetGetAction('export', 'Export');
			$this->SetGetPostAction('export','submit','ExportSave');
		}
		
		//Thank you template
		
		$this->AddBookmark('Settings', '?action=settings', 'img/modules/contact_form/settings.gif');

		$this->SetGetAction('archive', 'Archive');
		$this->SetGetAction('departments', 'Departments');

		// Email Template
		$this->SetGetAction('email', 'EmailTemplate');
		$this->SetGetPostAction('email', 'submit', 'SubmitEmailTemplate');

		// Confirmation Email
		$this->SetGetAction('email_confirmation', 'EmailConfirmation');
		$this->SetGetPostAction('email_confirmation', 'submit', 'SubmitEmailConfirmation');

		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');
	}

	function GetSnippet()
	{
		$code = $this->ContactForm->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}

	function Page()
	{
		if ($this->ContactForm->confUseArchive) Navigation::Jump($this->selfUri.'?action=archive');
		if ($this->ContactForm->confUseDepartments) Navigation::Jump($this->selfUri.'?action=departments');
		Navigation::Jump($this->selfUri.'?action=settings');

	}

	function Departments()
	{
		$this->SetBack();
		$this->currentBookmark = 'Departments';
		$this->SetTitle('Contact Departments');

		$List = new CMSList($this->ContactForm->confDIDepartmentsName);
		$List->MakeLinkButtons();
		$List->buttonView = false;

		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $List->Render();
	}
	
	function Export()
	{
		GLOBAL $Parser, $App;
		
		$App->Load('cpmodules', 'lang');
		
		$this->currentBookmark = 'Export';
		
		$this->SetBack();
		$this->SetTitle('Export');
		
		$GLOBALS['DATA_TEMP'] = array(
			'fields'	=> array(
				'export_uri'	=> array(
					'type'			=> 'input',
					'label'			=> 'Export URI'
				),
				'export_from_site'	=> array(
					'type'			=> 'input',
					'label'			=> 'From site ID'
				)
			)
		);
		
		$DI = new Data('temp');
		
		$DI->value = array(
			'export_uri'		=> $this->ContactForm->confExportURI,
			'export_from_site'	=> $this->ContactForm->confExportFromSite,
		);
		
		$this->pageContent = $Parser->MakeDynamicForm($DI, 'cms/edit');
	}
	
	function ExportSave()
	{
		GLOBAL $App;

		$this->ContactForm->confExportURI		= $_POST['export_uri'];
		$this->ContactForm->confExportFromSite	= $_POST['export_from_site'];

		$this->ContactForm->SaveSettings();
		
		$this->SetNotification('Settings successfully saved');
		
		Navigation::Jump($this->selfUri.'?action=export');
	
	}

	function Archive()
	{
		GLOBAL $Parser,$App;
		$App->Load('calendar', 'class');
        
        $this->AddLink('Export to Excel', 'export.php?type='.$this->ContactForm->confDIName, 'img/cms/remove.gif');

		$DI = new Data($this->ContactForm->confDIName);

		$Calendar = new Calendar("?action=archive", $DI->table);
		$Calendar->SetTplName('modules/contact_form/calendar');

		$this->SetBack();
		$this->currentBookmark = 'Archive';
		$this->SetTitle('Archive');

		$DI->Select("SUBSTRING(date, 1, 10)='{$Calendar->currentDate}'");

		$DIView = new Data($this->ContactForm->confDIName);
		if ($this->ContactForm->confUseDepartments) $DIView->ReSet('department');

		$listHtml = '';
		if (Utils::IsArray($DI->values))
		{
			foreach ($DI->values as $row)
			{
				$DI->Get('id='.$row['id']);
				$date = $DI->value['date'];
				$date = Format::DateTime($date, 'M d, H:i');
				$Parser->SetCaptionVariables(array(
					'date'	=> $date,
					'id'	=> $row['id'],
					'back'	=> $this->setBack,
					'diname'=> $this->ContactForm->confDIName
				));

				$DIView->value = $DI->value;
				$listHtml .= '<br />'.$Parser->MakeDynamicView($DIView,'modules/contact_form/archive','view_message');
			}
		}
		else
		{
			$listHtml = $Parser->GetHtml('modules/contact_form/archive', 'empty');
		}

		$this->pageContent .= $Parser->MakeView(array(
			'calendar'	=> $Calendar->Render(),
			'list'		=> $listHtml
		),'modules/contact_form/archive','view');
	}

	function EmailTemplate()
	{
		$this->SetTitle('E-Mail Template');
		$this->currentBookmark='E-Mail Template';
		$this->SetBack();
		$EditEmail=new CMSEditEmail('contact_form_'.$this->iid);
		$this->pageContent.=$EditEmail->Render();
	}

	function SubmitEmailTemplate()
	{
		$EditEmail=new CMSEditEmail('contact_form_'.$this->iid);
		$EditEmail->Update();
		Navigation::Jump($this->selfUri.'?action=email');
	}

    function EmailConfirmation()
	{
		$this->SetTitle('Confirmation E-Mail');
		$this->currentBookmark='Confirmation E-Mail';
		$this->SetBack();
		$EditEmail=new CMSEditEmail('contact_form_confirmation_'.$this->iid);
		$this->pageContent.=$EditEmail->Render();
	}

	function SubmitEmailConfirmation()
	{
		$EditEmail=new CMSEditEmail('contact_form_confirmation_'.$this->iid);
		$EditEmail->Update();
		Navigation::Jump($this->selfUri.'?action=email_confirmation');
	}

	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('Contact Form Settings');
		$this->currentBookmark='Settings';

		$this->settingsFields = array (
			'use_departments' => array(
				'type'  => 'flag',
				'label' => 'Enable departments'
			),
			'use_archive' => array(
				'type' => 'flag',
				'label' => 'Enable archive'
			),
			'departments_caption' => array(
				'type' => 'input',
				'label' => 'Departments caption'
			),
			'use_confirmation' => array(
				'type' => 'flag',
				'label' => 'Enable confirmation'
			),
			'default_send_to' => array(
				'type' => 'input',
				'label' => 'Default email address',
			    'strip_tags' => false,
			),
			'render'			=> array(
				'type'				=> 'list',
				'object'			=> 'def_contact_form_render_type'
			),
			'export_enabled'	=> array(
				'type'				=> 'flag',
				'label'				=> 'Export enabled'
			)
		);
		$this->settingsFieldsValues=array(
			'use_departments'	=> ($this->ContactForm->confUseDepartments)?1:0,
			'use_archive'		=> ($this->ContactForm->confUseArchive)?1:0,
			'departments_caption'	=> $this->ContactForm->confDepartmentsCaption,
			'default_send_to'	=> $this->ContactForm->confDefaultSendTo,
			'render'			=> $this->ContactForm->confRender,
			'use_confirmation'      => $this->ContactForm->confUseConfirmation ? 1 : 0,
			'export_enabled'	=> $this->ContactForm->confExportEnabled ? 1 : 0,
		);

		$this->customizableDI=array($this->ContactForm->confDIName);

		$this->pageContent.= $this->RenderSettingsPage($this->ContactForm);
	}

	function SaveSettings()
	{

		$this->ContactForm->confUseDepartments	= (@$_POST['use_departments_checked']==1);
		$this->ContactForm->confUseArchive		= (@$_POST['use_archive_checked']==1);
		$this->ContactForm->confDepartmentsCaption	= $_POST['departments_caption'];
		$this->ContactForm->confDefaultSendTo	= $_POST['default_send_to'];
		$this->ContactForm->confRender			= $_POST['render'];
		$this->ContactForm->confUseConfirmation	= (@$_POST['use_confirmation_checked']==1);
		$this->ContactForm->confExportEnabled	= (@$_POST['export_enabled_checked']==1);

		$this->ContactForm->SaveSettings();
		Navigation::Jump($this->selfUri.'?action=settings');
	}
}

$Page = new CpContactFormPage();
$Page->Process();

?>