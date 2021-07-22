<?php

$GLOBALS['DATA_NEWSLETTER_UPLOAD_CSV'] = array(
	'fields' => array(
		'file_csv' => array(
			'type' => 'file',
			'path' => 'not_realy_needed',
			'label' => 'CSV<br>Comma separated values'
		),
		'import_all_emails' => array(
			'type'  => 'flag',
			'label' => 'Import all emails regarding CSV file format',
		),
	),
);

$GLOBALS['DATA_NEWSLETTER_TEMPLATE'] = array(
	'fields' => array(
		'id' => 'hidden',
		'name' => array(
			'type'  => 'input',
			'label' => 'Name',
			'check' => 'reg:[A-z0-9_]{1,50}',
			'check_msg' => 'Field Name may contain only letters,numbers and _ characters!',
		),
	),

	'redefine_add_template' => array(
		'file_template' => array(
			'type' => 'file',
	        'path' => '/foo/',
			'label' => 'ZIP archive',
		),
	),
);

$GLOBALS['DATA_NEWSLETTERS_TO_SEND'] = array(
	'table'	=>	'newsletters_to_send',
	'order'	=>	'id',
	'fields'	=> array (
		'id'		=> 'hidden',
		'recipient'	=> 'input',
		'sender'	=> 'input',
		'subject'	=> 'input',
		'message'	=> 'text',
		'headers'	=> 'text',
	)
);

$GLOBALS['DATA_NEWSLETTER_COMPOSE'] = array(
	'fields'	=> array(
		'html'			=> 'hidden',
		'subject'		=> array(
			'type'	=> 'input',
			'check'	=> 'pre:empty',
		),
		'from'			=> array(
			'type'	=> 'input',
			'check'	=> 'pre:empty'
		),
		'template'		=> array(
			'type'			=> 'list',
			'object'		=> 'arr'
		),
		'body'			=> 'html',
		'attachments'	=> array(
			'type' => 'input',
		),
		'date_start'	=> 'date',
		'date_end'		=> 'date',
		'test_email'    => 'input',
	)
);

function newsletterCountGroupSubscribers($arr)
{
	GLOBAL $Db,$App,$Page;
	static $Newsletter;
	if (!$Newsletter)
	{
		$App->Load('newsletter','mod');
		$Newsletter = new Newsletter($Page->iid);
	}
	$dataItem = new Data($Newsletter->confDISubscriberName, false);
	$count = $Db->Get('parent_id='.$arr['id'], 'COUNT(id)', $dataItem->table);
	return "[$count]";
}

class CMSTreeNewsletter extends CMSTree
{
    /**
     * @var Newsletter
     */
	public $Newsletter;
	
	/**
	 * @var Data
	 */
	public $Compose;
	
	/**
	 * @var array
	 */
	public $toSend;
	
	function __construct(Newsletter $Newsletter, Data $Compose)
	{
		$this->Newsletter = $Newsletter;
		$this->Compose = $Compose;
		parent::__construct($this->Newsletter->confTreeName);
		
		$this->buttonNodeView = false;
		$this->buttonPointView = false;
		$this->buttonNodeCheckbox = true;
		$this->buttonNodeDeleteAll = true;
		$this->buttonNodeEdit = false;
		$this->buttonNodeDelete = false;
		
		$this->AddNodeButton('Rename', 'edit.php?type='.$this->Newsletter->confDIGroupName.'&id=[id]&back=[back]', 'Rename Group', '<img src="img/cms/list/edit.gif" width="8" height="14" border="0" align="absmiddle">');
		$this->AddNodeButton('Delete', "javascript: DeleteRow('?action=delete_node&id=[id]&back=[back]','id_[id]')", 'Delete Group', '<img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle">');
		
		if (!isset($_SESSION['newsletter_to_send']))
		{
		    $_SESSION['newsletter_to_send'] = array(
		        'subject' => null,
		        'custom_to' => null,
		    	'from' => null,
		        'body' => null,
		        'template' => null,
		        'test_email' => null,
		    );
		}
		$this->toSend =& $_SESSION['newsletter_to_send'];
	}
	
	function Render()
	{
		GLOBAL $Parser,$Paging,$Page;

		$this->back = $Page->setBack;

		switch ($this->clMode)
		{
			case 0:
				return $Parser->GetHtml('cms/tree/tree', 'empty');
				break;
				
			case 1:
				// nodes
				$list = new CMSList($this->node);
				$list->Init();
				$list->customDeleteSelected = true;
				
				$cond ="parent_id={$this->cl}";
				$cond .= ($this->cond!=null) ? '&'.$this->cond : '';
				
				$list->SetCond($cond);
		
				// copy buttons view status
				$list->buttonCheckbox	= $this->buttonNodeCheckbox;
				$list->buttonDeleteAll	= $this->buttonNodeDeleteAll;
				$list->buttonDelete 	= $this->buttonNodeDelete;
				$list->buttonEdit		= $this->buttonNodeEdit;
				$list->buttonView		= $this->buttonNodeView;
				
				//remove buttons from general group
				$list->RemoveButton('Delete', '[id]=='.NEWSLETTER_GENERAL_GROUP_ID);
				$list->RemoveButton('<input type="checkbox" name="ids[]" value="[id]" />', '[id]=='.NEWSLETTER_GENERAL_GROUP_ID);
			
				$list->SetFieldLink('name','?cl=[id]&back=[back]');
				
				// node add buttons
				if (Utils::IsArray($this->nodeAddButtons))
				{
					foreach ($this->nodeAddButtons as $row)
					{
						$list->AddButton($row['name'], $row['link'], $row['hint'], $row['icon']);
					}
				}
				// initialize paging
				$Paging->SwitchOn('cp');
				return $list->Render();
				break;
				
			case 2:
				$list = new CMSList($this->point);
				$list->Init();
				$list->SetCond("parent_id={$this->cl}");
				// copy buttons view status
				$list->buttonCheckbox	= $this->buttonPointCheckbox;
				$list->buttonDeleteAll	= $this->buttonPointDeleteAll;
				$list->buttonDelete 	= $this->buttonPointDelete;
				$list->buttonEdit		= $this->buttonPointEdit;
				$list->buttonView		= $this->buttonPointView;
				// point add buttons
				if (Utils::IsArray($this->pointAddButtons))
				{
					foreach ($this->pointAddButtons as $row)
					{
						$list->AddButton($row['name'], $row['link'], $row['hint'], $row['icon']);
					}
				}

				// initialize paging
				$Paging->SwitchOn('cp');
				return $list->Render();
				break;
		}
		return '';
	}
	
	function ToSendInit()
	{
		$this->Compose->value= array(
			'subject'	=> $this->toSend['subject'],
			'custom_to'	=> $this->toSend['custom_to'],
			'from'		=> $this->toSend['from'],
			'body'		=> $this->toSend['body'],
			'template'	=> $this->toSend['template'],
			'test_email'=> $this->toSend['test_email'],
		);
		$this->Compose->fields['template']->values = $this->GetTemplatesList();
	}

	function ToSendSave($values)
	{
		$this->toSend = array(
			'custom_to'	=> @$values['custom_to'],
			'subject'	=> @$values['subject'],
			'body'		=> @$values['body'],
			'template'	=> @$values['template'],
			'from'		=> $this->Newsletter->confFromAddress,
			'test_email'=> isset($values['test_email']) ? $values['test_email'] : $this->toSend['test_email'],
		);
		$this->ToSendInit();
	}
	
	function GetTemplatesList()
	{
		GLOBAL $App;
		$LIST_TEMPLATE['empty']	= 'empty';
		
		$hDir = dir($App->sysRoot.$App->filePath.$this->Newsletter->confTemplatesPath);
		if ($hDir)
		{
        	while (false !== ($szTemplateName = $hDir->read()))
        	{
        		if (($szTemplateName != '.') && ($szTemplateName != '..')) $LIST_TEMPLATE[$szTemplateName] = $szTemplateName;
        	}
		    $hDir->close();
		}
		
		return $LIST_TEMPLATE;
	}
	
	function RenderBody($body, $newsletterTemplateName=null)
	{
		GLOBAL $App;

		// add template
		$templateHtmlCode = $this->GetTemplateHtmlCode($newsletterTemplateName);

		$path = $App->httpRoot.$App->filePath.$this->Newsletter->confTemplatesPath.$newsletterTemplateName;

		if (strlen($templateHtmlCode))
		{
			$replaced = array();
			$matches = array();
			// update image path in newsletter template
			preg_match_all('/(?<=<img)([^>]+(?<=src=")([^"]+)(?=")[^>]+)(?=>)/i', $templateHtmlCode, $matches);
			if(Utils::IsArray($matches[2]))
			{
				foreach($matches[2] as $file)
				{
					if (!in_array($file, $replaced))
					{
						$templateHtmlCode = str_replace('"'.$file.'"', '"'.$path.'/'.$file.'"', $templateHtmlCode);
						$replaced[] = $file;
					}
				}
			}

			// update background images
			$replaced = array();
			$matches = array();
			preg_match_all('/(?<=<)([^>]+(?<=background=")([^"]+)(?=")[^>]+)(?=>)/i', $templateHtmlCode, $matches);
			if(Utils::IsArray($matches[2]))
			{
			    
				foreach($matches[2] as $file)
				{
					if (!in_array($file, $replaced))
					{
						$templateHtmlCode = str_replace('"'.$file.'"', '"'.$path.'/'.$file.'"', $templateHtmlCode);
						$replaced[] = $file;
					}
				}
			}

			// update input type=image
			$replaced = array();
			$matches = array();
			preg_match_all('/(?<=<input)([^>]+(?<=src=")([^"]+)(?=")[^>]+)(?=>)/i', $templateHtmlCode, $matches);
			if(Utils::IsArray($matches[2]))
			{
				foreach($matches[2] as $file)
				{
					if (!in_array($file,$replaced))
					{
						$templateHtmlCode = str_replace('"'.$file.'"', '"'.$path.'/'.$file.'"', $templateHtmlCode);
						$replaced[] = $file;
					}
				}
			}

			$rez = str_replace('{BODY}', $this->Compose->value['body'], $templateHtmlCode);
			$rez = str_replace('{SUBJECT}', $this->Compose->value['subject'], $rez);
		}
		else
		{
			$rez = $body;
		}

		return $rez;
	}

	protected function GetTemplateHtmlCode($tplName)
	{
		GLOBAL $App;

		$fileName = $App->sysRoot.$App->filePath.$this->Newsletter->confTemplatesPath.$tplName.'/template.htm';
		if (!file_exists($fileName)) return '';

		return file_get_contents($fileName);
	}
}

?>