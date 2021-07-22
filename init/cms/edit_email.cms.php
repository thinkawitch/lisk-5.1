<?php
/**
 * CMS Edit Email
 * @package lisk
 *
 */

class CMSEditEmail
{
	/**
	 * Main DataItem.
	 * Based on data.cfg DATA_EMAIL
	 *
	 * @var DataItem
	 */
	private $di;
	/**
	 * Email id. The id field from emails table.
	 *
	 * @var string
	 */
	private $emailId;
	
	/**
	 * Constructor.
	 *
	 * @param string $emailId
	 * @return CMSEditEmail
	 */
	function __construct($emailId)
	{
		GLOBAL $App;
		$App->Load('cpmodules', 'lang');
		$this->emailId = $emailId;
		$this->di = Data::Create('email');
		
	}
	
	/**
	 * Update email tpl in databse
	 *
	 */
	function Update()
	{
		$values = array();
		foreach ($_POST as $key=>$val)
		{
			// T.k. v _POST mogut bit vlogennie massivi (type = listbox), to
			if (Utils::IsArray($val))
			{
				$values[$key] = stripslashes($key);
				foreach($val as $in_val)
				{
					$values[$key.'_'.$in_val] = stripslashes($in_val);
				}
			}
			else
			{
				$values[$key] = stripslashes($val);
			}
		}
		$values['recipients'] = substr($_POST['recipients_result'], 0, -1);
		
		if ($_POST['content_type_header'] == 0)
		{
			$values['body'] = $_POST['body'];
		}
		else
		{
			$values['body'] = $_POST['body_html'];
			$this->di->ReSet('update');
		}
		
		$this->di->Update("id='{$this->emailId}'", $values);
	}
	
	/**
	 * Render email edit form
	 *
	 * @return HTML
	 */
	function Render()
	{
		GLOBAL $Parser,$App;
		
		$this->di->ReSet('edit');
		$this->di->Get("id='{$this->emailId}'");
		
		if (!Utils::IsArray($this->di->value))
		{
			$App->RaiseError("Email <b>{$this->emailId}</b> not found");
		}
		
		$this->di->value['body_html'] = $this->di->value['body'];
		$this->di->fields['body']->AddFormParam('style', 'width:100%; height:200px;');
		
		// Recipients html seect
		$recipientsHtml = '';
		$recipients = preg_split('/[,]/', $this->di->value['recipients'], null, PREG_SPLIT_NO_EMPTY);
		foreach ($recipients as $recipient)
		{
			$recipientsHtml .= '<option>'.trim($recipient)."</option>\n";
		}

		// Radio button def value
		if ($this->di->value['content_type_header']==0 && @$_GET['mode'] != 'html')
		{
			$defText = 'checked="checked"';
			$defHtml = '';
			$defHtmlDiv = 'none';
			$defTextDiv = 'block';
		}
		else
		{
		    $defText = '';
			$defHtml = 'checked="checked"';
			$defHtmlDiv = 'block';
			$defTextDiv = 'none';
		}
		
		$Parser->SetAddVariables(array(
			'RECIPIENTS_html'	=> $recipientsHtml,
			'def_text'			=> $defText,
			'def_html'			=> $defHtml,
			'def_html_div'		=> $defHtmlDiv,
			'def_text_div'		=> $defTextDiv,
			'id'				=> $this->emailId,
			'jump_to_html_href'	=> Navigation::AddGetVariable(array(
				'mode' => 'html'
			))
		));

		return $Parser->MakeForm($this->di, 'cms/edit_email');
	}
}

?>