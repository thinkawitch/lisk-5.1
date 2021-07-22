<?php
chdir('../');
require_once('init/init.php');

class CpNewsletterPage extends CPModulePage
{
    /**
     * @var CMSTreeNewsletter
     */
	private $tree;
	
	/**
	 * @var Newsletter
	 */
	private $Newsletter;
	
	private $testMode = false;
	private $importAllFoundEmails = false; //import all found emails, fulltext search

	/**
	 * @var Data
	 */
	private $Compose;

	function __construct()
	{
		parent::__construct(true);

		if (!isset($_SESSION['newsletterObjectId'])) $_SESSION['newsletterObjectId'] = uniqid('nl');

		$this->App->Load('newsletter', 'mod');
		$this->App->LoadModule('installed/newsletter/newsletter.cms.php');

		$this->Newsletter = new Newsletter($this->iid);
		$this->Newsletter->InitEMail(null, $_SESSION['newsletterObjectId']);

		$this->Compose = new Data('newsletter_compose');
		$this->tree = new CMSTreeNewsletter($this->Newsletter, $this->Compose);

		$this->AddBookmark('Subscribers List', '?action=list', 'img/modules/newsletter/subscribers_list.gif');
		$this->AddBookmark('Send Newsletter', '?action=send', 'img/modules/newsletter/compose.gif');
		$this->AddBookmark('Archive', '?action=archive', 'img/modules/newsletter/archive.gif');
		$this->AddBookmark('Templates', '?action=templates', 'img/modules/newsletter/templates.gif');
		$this->AddBookmark('Settings', '?action=settings&id=1', 'img/modules/newsletter/settings.gif');


		$this->AddPostHandler('list_'.$this->Newsletter->confDIGroupName.'_action', 'DeleteSelected', 'delete_selected');
		$this->AddPostHandler('list_'.$this->Newsletter->confDISubscriberName.'_action', 'DeleteSelected', 'delete_selected');

		$this->SetGetAction('delete_node', 'DeleteNode');
		$this->SetGetAction('list', 'ListSubscribers');
//		$this->SetGetPostAction('list','delete_selected','DeleteSelectedSubscribers');

		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');
		$this->SetGetAction('settings', 'Settings');


		$this->SetPostAction('attach', "SaveAttachment");
		$this->SetGetAction('delete_attachment', "DeleteAttachment");

		$this->SetGetAction('send', 'ComposeNewsletter');

		$this->SetGetPostAction('send', 'preview', 'SaveNewsletterToSend');

		$this->SetGetAction('preview', 'Preview');
		$this->SetGetAction('preview_body', 'PreviewBody');

		$this->SetGetPostAction('preview', 'send', 'SendNewsletter');
		$this->SetGetAction('sent_confirmation', 'SentConfirmation');

		$this->SetGetAction('archive', 'ListArchive');

		$this->SetGetAction('import', 'ImportCsvDbForm');
		$this->AddGetPostHandler('action', 'import', 'submit', 'Submit', 'ImportCsvDbPost');
		$this->SetGetAction('export', 'ExportCsvDb');

		$this->SetGetAction('templates', 'ListTemplates');
		$this->SetGetAction('add_template', 'AddTemplate');
		$this->SetGetPostAction('add_template', 'submit', 'AddTemplatePost');
		$this->SetGetAction('delete_template', 'DeleteTemplate');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');

		$this->titlePicture = 'modules/newsletter/newsletters.gif';
	}

	function Page()
	{
		$this->ListSubscribers();
	}

	function ListSubscribers()
	{
		Navigation::SetBack($this->back);
		$this->ParseBack();

		$this->currentBookmark = 'Subscribers List';
		$this->SetTitle('Newsletter: Subscribers', $this->titlePicture);

		$this->tree->MakeLinkButtons($this);
		$this->AddLinkImportExport();

		$this->tree->AdditionalNavigation($this);

		$this->pageContent.=$this->tree->Render();
	}

	function AddTemplate()
	{
		$this->currentBookmark = 'Templates';
		$this->SetTitle('Newsletter: Add Template', $this->titlePicture);

		$dataItem = new Data('newsletter_template');
		$dataItem->ReSet('add_template');

		$cmsAdd = new CMSAdd($dataItem);
		$this->pageContent .= $cmsAdd->Render();

		if ($this->setBack > 0) $this->ParseBack();
	}

	function AddTemplatePost()
	{
		GLOBAL $App, $FileSystem;
		$name = $_POST['name'];
		$file = '';
		if (is_uploaded_file($_FILES['file_template_upload_http']['tmp_name']))
		{
			$file = $_FILES['file_template_upload_http']['tmp_name'];

		}
		elseif (!is_dir($App->sysRoot.$App->filePath.'_system/'.$_POST['file_template_upload_ftp']) && file_exists($App->sysRoot.$App->filePath.'_system/'.$_POST['file_template_upload_ftp']) )
		{
			$file = $App->sysRoot.$App->filePath.'_system/'.$_POST['file_template_upload_ftp'];
		}

		$extractPath = $App->sysRoot.$App->filePath.$this->Newsletter->confTemplatesPath.$name.'/';

		if (file_exists($extractPath))
		{
			$this->SetError('Template with this name ['.$name.'] already exists!');
			Navigation::JumpBack($this->back);
		}

		if ($file == '')
		{
			$this->SetError('You have to upload ZIP archive!');
			Navigation::JumpBack($this->back);
		}

		$FileSystem->CreateDir($extractPath);

		$App->Load('zip', 'utils');
        Utils::FreezeMBEncoding();
		$Zip = new Archive_Zip($file);
		$Zip->extract(array('add_path' => $extractPath, 'set_chmod' => 0777));
		//TODO extract only htm, html, css and images files
		Utils::UnfreezeMBEncoding();

		//check if template is correct
		$template = @file_get_contents($extractPath.'template.htm');
		if (!strstr($template,'{BODY}'))
		{
			$this->SetError('Template.htm has no BODY defined!');
			$FileSystem->DeleteDir($extractPath);
		}

		Navigation::JumpBack($this->back);
	}

	function DeleteTemplate()
	{
		GLOBAL $FileSystem,$App;
		$id = $_GET['id'];
		$rows = $this->GetTemplatesRows();
		if (Utils::IsArray($rows))
		{
			$name = '';
			foreach($rows as $row)
			{
				if ($row['id'] == $id)
				{
					$name = $row['name'];
					break;
				}
			}

			if (strlen($name))
			{
				$removePath = $App->sysRoot.$App->filePath.$this->Newsletter->confTemplatesPath.$name.'/';
				$FileSystem->DeleteDir($removePath);
			}
		}

		Navigation::JumpBack($this->back);
	}

	function ListTemplates()
	{
		Navigation::SetBack($this->back);
		$this->ParseBack();

		$this->currentBookmark = 'Templates';
		$this->SetTitle('Newsletter: Templates', $this->titlePicture);

		$rows = $this->GetTemplatesRows();
		$this->AddLink('Add Template', '?action=add_template&back='.$this->setBack, 'img/ico/links/add.gif', 'Add New Template');
		$List = new CMSList('newsletter_template');
		$List->Init();
		$List->buttonAdd = false;
		$List->buttonCheckbox  = false;
		$List->buttonDeleteAll = false;
		$List->buttonEdit = false;
		$List->buttonView = false;
		$List->buttonDelete = false;
		
		$List->AddButton('Delete', "#delete\" class=\"delete\" rel=\"module_newsletter.php?action=delete_template&id=[id]&back=[back]\" onclick=\"return false", 'Delete Template', '<img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle">');

		$this->pageContent .= $List->Render('cms_list', $rows);

	}

	private function GetTemplatesRows()
	{
		$list = $this->tree->GetTemplatesList();
		unset($list['empty']);
		$rows = array();
		$c = 1;
		if (Utils::IsArray($list))
		{
			foreach($list as $template)
			{
				$rows[] = array(
					'id'   => $c++,
					'name' => $template,
				);
			}
		}

		return $rows;
	}

	function Settings()
	{
		Navigation::SetBack($this->back);
		$this->ParseBack();

		$this->SetTitle('Newsletter Settings');
		$this->currentBookmark = 'Settings';

		$this->settingsFields = array(
			'from_address' => array(
				'label' => 'Email From Field',
				'type'  => 'input',
			),
			'attach_images' => array(
				'label' => 'Attach message images',
				'type'  => 'flag',
			),
		);
		$this->settingsFieldsValues = array(
			'from_address'  => $this->Newsletter->confFromAddress,
			'attach_images' => $this->Newsletter->confAttachImages,
		);
		$this->customizableDI = array($this->Newsletter->confDISubscriberName);

		$this->pageContent .= $this->RenderSettingsPage($this->Newsletter);
	}

	function SaveSettings()
	{
		$this->Newsletter->confFromAddress = $_POST['from_address'];
		$this->Newsletter->confAttachImages = (boolean)$_POST['attach_images_checked'];

		$this->Newsletter->SaveSettings();

		Navigation::JumpBack($this->back);
	}

	function DeleteNode()
	{
		GLOBAL $App;
		$id = intval($_GET['id']);
		
		if ($id != NEWSLETTER_GENERAL_GROUP_ID) $this->tree->node->delete('id='.$id);
		else $this->SetError('This group can\'t be deleted!');
		
		Navigation::JumpBack($this->back);
	}

	function DeleteSelected()
	{
		switch ($this->tree->clMode)
		{
			case 1:
				$DataItem = $this->tree->node;
				//remove general groupId for safety
				foreach ($_POST['ids'] as $k=>$id)
				{
				    if ($id == NEWSLETTER_GENERAL_GROUP_ID)
				    {
				        unset($_POST['ids'][$k]);
				        break;
				    }
				}
				break;
				
			case 2:
				$DataItem = $this->tree->point;
				break;
		}

		foreach ($_POST['ids'] as $id)
		{
		    $DataItem->Delete('id='.$id);
		}

		Navigation::JumpBack($this->back);
	}

	function ImportCsvDbForm()
	{
		GLOBAL $Parser;
		$Upload = new Data('newsletter_upload_csv');
		$this->pageContent = $Parser->MakeDynamicForm($Upload, 'cms/edit');

		$this->currentBookmark = 'Subscribers List';
		$this->SetTitle('Newsletter: Import CSV DB', $this->titlePicture);
		$this->AddLinkImportExport();
	}

	function ImportCsvDbPost()
	{
		GLOBAL $App, $Db;

		$file = '';
		if (is_uploaded_file($_FILES['file_csv_upload_http']['tmp_name']))
		{
			$file = $_FILES['file_csv_upload_http']['tmp_name'];
		}
		elseif (!is_dir($App->sysRoot.$App->filePath.'_system/'.$_POST['file_csv_upload_ftp']) && file_exists($App->sysRoot.$App->filePath.'_system/'.$_POST['file_csv_upload_ftp']))
		{
			$file = $App->sysRoot.$App->filePath.'_system/'.$_POST['file_csv_upload_ftp'];
		}

		$content = file($file);

		if (!Utils::IsArray($content))
		{
			$this->SetError('File is empty!');
			Navigation::Jump('module_newsletter.php?action=import&cl='.$this->tree->cl);
		}

		$rowsInserted = 0;
		$parent_id = $this->tree->cl;
		$Subscriber = new Data($this->Newsletter->confDISubscriberName, false);
		foreach ($content as $line)
		{
			if ($this->importAllFoundEmails || $_POST['import_all_emails_checked'] == 1)
			{
			    $matches = array();
				if (preg_match_all("/[a-zA-Z\.0-9\-\_]{2,}@(?>[a-zA-Z0-9\-]{2,}\.){1,}[a-zA-Z]{2,4}/", $line, $matches))
				{
					if (Utils::IsArray($matches))
					{
						foreach ($matches as $emails)
						{
							if (Utils::IsArray($emails))
							{
								foreach ($emails as $email)
								{
									$email = trim($email);
									$insert = array(
										'email' => $email,
										'parent_id' => $parent_id,
										'parents'   => "<1><{$parent_id}>",
									);
									if (1==1 || /*allow multi*/  !$Db->Get('email='.Database::Escape($email).' AND parent_id='.$parent_id, 'COUNT(id)', $Subscriber->table))
									{
										if ($Db->Insert($insert, $Subscriber->table))
										{
											$rowsInserted++;
										}
									}
								}
							}
						}
					}
				}
			}
			else
			{
				$split = explode(',', $line);

				if (Utils::IsArray($split))
				{
					$email = $split[0];
					$this->RemoveRn($email);
					$insert = array('email' => $email);
					$insert['parent_id'] = $parent_id;
					$insert['parents'] = "<1><{$parent_id}>";

					if (!$Db->Get('email="'.Database::Escape($email).'" AND parent_id='.$parent_id, 'COUNT(id)', $Subscriber->table))
					{
						if ($Db->Insert( $insert, $Subscriber->table))
						{
							$rowsInserted++;
						}
					}
				}
			}

		}

		$this->SetError('Import finished successfully! Added '.$rowsInserted.' records.');
		Navigation::JumpBack($this->back);
	}

	function RemoveRn(&$str)
	{
		$str = str_replace("\r\n", '', $str);
		$str = str_replace("\r", '', $str);
		$str = str_replace("\n", '', $str);
	}

	function ExportCsvDb()
	{
		$Subscriber = new Data($this->Newsletter->confDISubscriberName);
		$Subscriber->Select('parent_id='.$this->tree->cl);
		$arr = $Subscriber->values;

		$csv = '';
		$rn  = "\r\n";
		if (Utils::IsArray($arr))
		{
			foreach($arr as $v)
			{
//				$csv .= "{$v['email']},{$v['subscriber_type']}$rn";
				$csv .= "{$v['email']},$rn";
			}
		}
		$csv = substr($csv, 0, -strlen($rn));
		header('Content-type: text/plain');
		header('Content-Disposition: attachment; filename="subscribers_'.date('Y_m_d').'.csv"');
		header("Content-Length: ".strlen($csv));
		echo $csv;
		exit();
	}

	function AddLinkImportExport()
	{
		if ($this->tree->cl > 1)
		{
			$this->AddLink('Import CSV DB', '?action=import&cl='.$this->tree->cl, 'img/modules/newsletter/import.gif');
			$this->AddLink('Export CSV DB', '?action=export&cl='.$this->tree->cl, 'img/modules/newsletter/export.gif');
		}
	}

	function ListArchive()
	{
		$this->SetBack();
		$this->ParseBack();
		$this->currentBookmark = 'Archive';
		$this->SetTitle('Newsletter: Archive', $this->titlePicture);

		$List = new CMSList($this->Newsletter->confDIHistoryName);
		$List->Init();
		$List->buttonCheckbox = false;
		$List->buttonDeleteAll = false;
		$List->buttonEdit = false;
		
        $this->Paging->SwitchOn('cp');
		$this->pageContent .= $List->Render();
	}

	function ComposeNewsletter()
	{
		GLOBAL $Parser;
		$this->SetTitle('Newsletter: Compose', $this->titlePicture);
		$this->currentBookmark = 'Send Newsletter';

		Navigation::SetBack($this->back);
		$this->ParseBack();

		$this->tree->ToSendInit();
		$attachedFiles = $this->Newsletter->EMail->GetAttachments();
		
		$Parser->SetListDecoration(' ', 'bgcolor="#F8F8FE"');
		$attachments = $Parser->MakeList($attachedFiles, 'modules/newsletter/newsletter', 'attachments');

		$this->Compose->value['attachments'] = $attachments;

		$this->pageContent .= $Parser->MakeForm($this->Compose, 'modules/newsletter/newsletter', 'compose');

	}

	function SaveAttachment()
	{
		if ($_POST['action'] == 'attach')
		{
			$this->tree->ToSendSave($_POST);
			if ($_FILES['newsletter_attach']['name'] != '')
			{
				$this->Newsletter->EMail->SaveAttach("newsletter_attach");
				Navigation::JumpBack($this->back);
			}
			Navigation::JumpBack($this->back);
		}
	}

	function DeleteAttachment()
	{
		$this->Newsletter->EMail->DeleteAttachment($_GET['id']);
		Navigation::JumpBack($this->back);
	}

	function SaveNewsletterToSend()
	{
		$this->tree->ToSendSave($_POST);
		Navigation::Jump('?action=preview');
	}

	function Preview()
	{
		GLOBAL $Parser;
		$this->SetTitle('Newsletter: Preview',$this->titlePicture);
		$this->currentBookmark='Send Newsletter';

		$this->tree->ToSendInit();
		$this->Compose->value['body'] = $this->tree->RenderBody($this->Compose->value['body'], $this->Compose->value['template']);

		$di = new Data($this->Newsletter->confDIGroupName);
		$list = $di->SelectValues('id>1');
		$list = Utils::ListToHash($list,'id','name');

		$info = array(
			'send_to_group' => array(
				'type'   => 'list',
				'object' => 'arr',
				'cond'   => 'id>1',
				'add_values' => array(-1 => 'All'),
				'render' => 'tpl',
				'values' => $list,
 			),
		);

		$this->Compose->SetFields($info);
		$this->Compose->value['send_to_group'] = -1;

		$this->pageContent .= $Parser->MakeView($this->Compose, 'modules/newsletter/newsletter', 'preview');
	}

	function PreviewBody()
	{
		$this->tree->ToSendInit();

		$this->Compose->value['body'] = $this->tree->RenderBody($this->Compose->value['body'], $this->Compose->value['template']);

		echo $this->Compose->value['body'];
		exit;
	}

	function SendNewsletter()
	{
		GLOBAL $App,$Db;

		$sendToGroup = $_REQUEST['send_to_group'];

		$Subscriber = new Data($this->Newsletter->confDISubscriberName);

		switch ($_POST['send_mode'])
		{
			//to one group
			case '1':
				$cond = 'parent_id>1';
				if ($sendToGroup > 1) $cond = 'parent_id='.$sendToGroup;
				
				$rows = $Subscriber->SelectValues($cond, '');
			break;
            
			// test send
			case '2':
				$this->testMode = true;
			    $this->tree->toSend['test_email'] = $_POST['test_email'];
				$rows = array(
					array('email' => $this->tree->toSend['test_email'])
				);
			break;

			default:
				// send to all
				$rows = $Subscriber->SelectValues();
			break;

		}

		if (!Utils::IsArray($rows))
		{
			$this->setError('No subscribers selected!');
			Navigation::Jump('?action=preview');
		}

		$this->tree->ToSendInit();
		$this->Compose->value['body'] = $this->tree->RenderBody($this->Compose->value['body'], $this->Compose->value['template']);

		if (!$this->testMode)
		{
			$NewsletterHistory = new Data($this->Newsletter->confDIHistoryName);
			$NewsletterHistory->Insert(array(
				'subject'	=> $this->Compose->value['subject'],
				'content'	=> $this->Compose->value['body'],
				'users'		=> count($rows),
			));
		}

		// add to send to tables
		$this->Newsletter->EMail->from = $this->Compose->value['from'];
		$this->Newsletter->EMail->subject = $this->Compose->value['subject'];
		$this->Newsletter->EMail->message = $this->Compose->value['body'];

		$header	= $this->Newsletter->EMail->GenerateHeader();
		$body	= $this->Newsletter->EMail->GenerateBody();

		switch (NEWSLETTER_SEND_METHOD)
		{
			case NEWSLETTER_SEND_NOW:
			    $this->Newsletter->EMail->instantSend = true;
				foreach ($rows as $user)
				{
				    $this->Newsletter->EMail->ClearRecipients();
				    
					$this->Newsletter->EMail->body = $body;
					$this->Newsletter->EMail->header = $header;

					$this->Newsletter->EMail->ParseVariables($user);
					$this->Newsletter->EMail->AddRecipient($user['email']);
					$this->Newsletter->EMail->Send(false, false);
				}
			break;

			case NEWSLETTER_SEND_CROND:
			    $this->Newsletter->EMail->instantSend = false;
			    
			    //put into mail dispatcher
        	    $queueId = $Db->Insert(array(
        	        'date' => Format::DateTimeNow(),
        	        'subject' => $this->Compose->value['subject'],
        	        'message' => $this->Compose->value['body'],
        	        'body' => $body,
        	        'header' => $header,
        	    ), 'sys_email_queue');
        	    
        	    if ($queueId)
        	    {
        	        foreach ($rows as $user)
        	        {
        	            $Db->Insert(array(
        	                'parent_id' => $queueId,
        	                'email' => $user['email'],
        	                'params' => serialize($user), // some array of params to parse on email send
        	            ), 'sys_email_queue_recipients');
        	        }
        	    }
				break;
				
			default:
				$App->RaiseError('Unknown send method cp/newsletter.php:'.__LINE__);
				break;
		}

		if (!$this->testMode)
		{
			$this->Newsletter->EMail->DeleteAllAttachments();
			$this->Compose->value = array();
			$this->tree->ToSendSave(array());
		}

		Navigation::Jump('?action=sent_confirmation');
	}

	function SentConfirmation()
	{
		GLOBAL $Parser;
		$this->SetTitle('Newsletter: Sent', $this->titlePicture);
		$this->pageContent .= $Parser->GetHtml('modules/newsletter/newsletter', 'confirmation');
	}

	function GetSnippet()
	{
		$code = $this->Newsletter->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}
}

$Page = new CpNewsletterPage();
$Page->Render();
?>