<?php
/**
 * CLASS EMail
 * @package lisk
 *
 */
class EMail
{
	private $templateName;
	
	private $objectId;
	private $attachmentVariable;

	private $rn;
    
	public $from;
    public $header; //prepared header
	public $subject;
	public $message; // readable text message
	public $body; // prepared body

	public $contentType;
	const TYPE_PLAIN = 'text/plain';
	const TYPE_HTML = 'text/html';
	public $charset;

	private $recipients = array();

	public $attachBodyImages = false;
	private $imagesToAttach = array();
	private $attachments;
	
	public $pathAttachmentFiles = 'email_attachments/';

	private $boundary;
	private $relatedBoundary;

	private $sendmailPath = '/usr/sbin/sendmail';
    
	const MAILER_NATIVE = 'phpmail';
	const MAILER_SENDMAIL = 'sendmail';
	
	/**
	 * What program to use to send
	 * @var string
	 */
	public $mailerType;
	
	/**
	 * Send email immediately or via cron job
	 *
	 * @var boolean
	 */
	public $instantSend = false;
	
	/**
	 * Constructor
	 *
	 * @param string $templateName
	 * @param string $objectId
	 * @return nothing
	 */
	function __construct($templateName=null, $objectId=null)
	{
		GLOBAL $App;
        
		$this->contentType = self::TYPE_PLAIN;
		$this->charset = 'utf-8';
		$this->mailerType = self::MAILER_NATIVE;
		
		if ($objectId != null)
		{
			$this->objectId = $objectId;
			$this->attachmentVariable = 'email_'.$this->objectId;
			$this->InitAttachments();
		}

		if ($templateName != null)
		{
			$this->templateName = $templateName;
			$this->InitTemplate();
		}

		$this->rn = ($App->isWindows) ? "\r\n" : "\n";

		$this->boundary        = '=_'.substr(md5(uniqid('', false)), 0, 32);
		$this->relatedBoundary = '=_'.substr(md5(uniqid('', false)), 0, 32);
		
		$this->instantSend = ($App->mailDispatcher == 'instant');
	}

	/**
	 * Add debug info
	 *
	 * @param string $name
	 * @param string $value
	 */
	private function Debug($name, $value)
	{
		GLOBAL $App,$Debug;
		if ($App->debug) $Debug->AddDebug('EMAIL', $name, $value, null);
	}
	
    private function InitAttachments()
	{
	    if (!isset($_SESSION[$this->attachmentVariable])) $_SESSION[$this->attachmentVariable] = array();
	    $this->attachments =& $_SESSION[$this->attachmentVariable];
	}
	
    private function InitTemplate()
	{
		GLOBAL $Db,$App;

		$email = $Db->Get('id='.Database::Escape($this->templateName), null, 'sys_email');

		if ($email === false)
		{
			$App->RaiseError("Email template {$this->templateName} is not found");
		}

		$this->subject = trim($email['subject']);
		$this->from = trim($email['from_header']);
		$this->message = trim($email['body']);

		$this->AddRecipientsCSV($email['recipients']);

		if ($email['content_type_header'] == 1) $this->contentType = self::TYPE_HTML;
		else $this->contentType = self::TYPE_PLAIN;

		$this->Debug('Init Template', $this->templateName);
	}

	/**
	 * Replace all markers within subject,from,body fields of email
	 *
	 * @param array $values
	 */
	public function ParseVariables($values)
	{
	    if (!Utils::IsArray($values)) return;
	    
	    foreach ($values as $key=>$value)
	    {
			foreach ($this->recipients as $recKey=>$recipient)
			{
				$this->recipients[$recKey] = str_replace('%'.strtoupper($key).'%', $value, $recipient);
			}
			$this->from = str_replace('%'.strtoupper($key).'%', $value, $this->from);
			$this->subject = str_replace('%'.strtoupper($key).'%', $value, $this->subject);
			$this->message = str_replace('%'.strtoupper($key).'%', $value, $this->message);
			$this->body = str_replace('%'.strtoupper($key).'%', $value, $this->body);
		}
	}

	/**
	 * @param boolean $generateEmail
	 * @param boolean $deleteAttachments
	 */
	public function Send($generateEmail=true, $deleteAttachments=true)
	{
	    GLOBAL $Db, $App;
		if (!Utils::IsArray($this->recipients)) return;
		
	    if ($generateEmail == true)
		{
			$this->header = $this->GenerateHeader();
			$this->body = $this->GenerateBody();
		}
        
		if ($App->debug) $this->SaveMailToFile();
		
		if ($this->instantSend)
		{
    		switch ($this->mailerType)
    		{
    		    case self::MAILER_NATIVE:
    		        $this->SendNative();
    		        break;
    		        
    		    case self::MAILER_SENDMAIL:
    		        $this->SendViaSendmail();
    		        break;
    		}
    		
    		//save email to history
    		$historyId = $Db->Insert(array(
		        'date' => Format::DateTimeNow(),
		        'subject' => $this->subject,
		        'message' => $this->message,
		    ), 'sys_email_history');
		    
		    if ($historyId)
		    {
		        foreach ($this->recipients as $recipient)
		        {
		            $Db->Insert(array(
		                'parent_id' => $historyId,
		                'email' => $recipient
		            ), 'sys_email_history_recipients');
		        }
		    }
		}
		else
		{
		    $queueId = $Db->Insert(array(
		        'date' => Format::DateTimeNow(),
		        'subject' => $this->subject,
		        'message' => $this->message,
		        'body' => $this->body,
		        'header' => $this->header,
		    ), 'sys_email_queue');
		    
		    if ($queueId)
		    {
		        foreach ($this->recipients as $recipient)
		        {
		            $Db->Insert(array(
		                'parent_id' => $queueId,
		                'email' => $recipient,
		                //'params' => '', // some array of params to parse on email send
		            ), 'sys_email_queue_recipients');
		        }
		    }
		    
		    //make record in debug only for first 3 recipients, in case of mass-mail not to down the site
		    $first3 = array();
		    for ($i=0; $i<=2; $i++)
		    {
		        if (isset($this->recipients[$i])) $first3[] = $this->recipients[$i];
		    }
		    $this->EnvelopeToDebug(implode(',', $first3));
		}
		
        if ($deleteAttachments) $this->DeleteAllAttachments();
	}
	
	private function SendNative()
	{
	    $subject = $this->GenerateSubject();
	    foreach ($this->recipients as $recipient)
        {
            @mail($recipient, $subject, $this->body, $this->header);
            $this->EnvelopeToDebug($recipient);
        }
	}
	
	private function SendViaSendmail()
	{
	    if (!is_executable($this->sendmailPath)) return;
	    
	    foreach ($this->recipients as $recipient)
        {
			$mail = popen($this->sendmailPath.' -f'.$this->from.' -- '.$recipient, 'w');
			fputs($mail, $this->header);
			fputs($mail, $this->rn);
			fputs($mail, $this->body);
			$result = pclose($mail) >> 8 & 0xFF;
			
            $this->EnvelopeToDebug($recipient);
        }
	}
	
	private function EnvelopeToDebug($recipient)
	{
	    $debugHtmlCode = '<table width="100%" border="1" cellspacing="0" cellpadding="5">
	    	<tr><td width="11%">Subject</td><td width="89%">'.$this->subject.'</td></tr>
	    	<tr><td>Body</td><td>'.$this->message.'</td></tr>
	    	<tr><td>Header</td><td>'.$this->header.'</td></tr>
	    	</table>';

	    $prefix = ($this->instantSend) ? 'sent to ' : 'put in queue to ';
		$this->Debug($prefix.$recipient, $debugHtmlCode);
	}

	public function GetAttachments()
	{
		return $this->attachments;
	}

	/**
	 * Add new recipients to send list
	 *
	 * @param mixed $recipient
	 */
	public function AddRecipient($recipient)
	{
		if (Utils::IsArray($recipient))
		{
			foreach($recipient as $v) $this->AddRecipient($v);
		}
		else
		{
		    $recipient = trim($recipient);
			if (strlen($recipient) && !in_array($recipient, $this->recipients))
			{
				array_push($this->recipients, $recipient);
			}
		}
	}
	
	public function ClearRecipients()
	{
	    $this->recipients = array();
	}

	/**
	 * Add new recipients in SCV format to send list
	 *
	 * @param string $recipients
	 */
	function AddRecipientsCSV($recipients)
	{
		$arr = explode(',', $recipients);
		$this->AddRecipient($arr);
	}
    
	private function GenerateSubject()
	{
	    return '=?'.$this->charset.'?B?'.base64_encode($this->subject).'?=';
	}
	
	/**
	 * Generate email header
	 *
	 * @return string $header
	 */
	public function GenerateHeader()
	{
		$header  = '';
		
		if ($this->mailerType == self::MAILER_NATIVE && strlen($this->from))
		{
		    $header .= 'From: '.$this->from.$this->rn;
		}
		
        if ($this->mailerType == self::MAILER_SENDMAIL && strlen($this->subject))
		{
		    $header .= 'Subject: '.$this->GenerateSubject().$this->rn;
		}

		$header .= 'MIME-version: 1.0'.$this->rn;
        $header .= 'Content-Type: multipart/mixed; boundary="'.$this->boundary.'"'."\r\n";

		return $header;
	}

	/**
	 * Generate email body
	 *
	 * @return string $body
	 */
	public function GenerateBody()
	{
		$boundary = $this->boundary;
		$relatedBoundary = $this->relatedBoundary;
		
		$encodedAttachments = $this->EncodeAttachments();
		$body = $this->message;

		$text =
			"--$boundary".
			$this->rn.
			"Content-Type: multipart/related; boundary=\"$relatedBoundary\"".
			$this->rn.
			$this->rn.
			"--$relatedBoundary".
			$this->rn.
			"Content-Type: {$this->contentType}; charset=\"{$this->charset}\"".
			$this->rn.
			'Content-Transfer-Encoding: 8bit'.
			$this->rn.
			$this->rn.
			$body.
			$this->rn.
			$this->rn;

		$text .= $encodedAttachments;

		$text .=
			"--$relatedBoundary--".
			$this->rn.
			"--$boundary--".
			$this->rn;

		return $text;
	}

	/**
	 * Encode attachments, if any
	 *
	 * @return string $encodedAttachments
	 */
	private function EncodeAttachments()
	{
		$relatedBoundary = $this->relatedBoundary;
		$attachments = $this->attachments;
		$encodedAttachments = '';

		//first find images in html code
		if ($this->attachBodyImages)
		{
			$this->ProcessBodyImages();
			if (Utils::IsArray($this->imagesToAttach))
			{
				if (Utils::IsArray($attachments))
				{
					foreach($this->imagesToAttach as $node)
					{
						$attachments[] = $node;
					}
				}
				else $attachments = $this->imagesToAttach;
			}
		}
		
		if (!Utils::IsArray($attachments)) return $encodedAttachments;

		$encoding = 'base64';
		
		foreach ($attachments as $attachment)
		{
			$attachFile = $attachment['file'];
			$attachName = $attachment['filename'];
			$attachCid = isset($attachment['cid']) ? $attachment['cid'] : substr(md5(uniqid('',false)),0,32);
			$attachType = isset($attachment['mime_type']) ? $attachment['mime_type'] : 'application/octet-stream';
			$attachValue = '';
            
			if (false !== ($fp = fopen($attachFile, 'r')))
			{
			    while (!feof($fp))
			    {
			        $line = fread($fp, 54);
			        $attachValue .= base64_encode($line);
					$attachValue .= $this->rn;
			    }
				fclose($fp);

				$encodedAttachments .=
					"--$relatedBoundary".
					$this->rn.
					"Content-Type: $attachType". //; name=\"".$attachName."\"".
					$this->rn.
					"Content-Transfer-Encoding: $encoding".
					$this->rn;

				if (in_array($attachType, $this->GetImageMimeTypes()))
				{
					$encodedAttachments .=
						"Content-Disposition: inline; filename=\"$attachName\"".
						$this->rn.
						"Content-ID: <$attachCid>".
						$this->rn;
						//"Content-Location: <$attachName>".
						//$this->rn.
				}
				else
				{
					$encodedAttachments .=
						"Content-Disposition: attachment; filename=\"$attachName\"". //attachment
						$this->rn;
				}

				$encodedAttachments .= $this->rn.$attachValue;
			}
		}
		
		return $encodedAttachments;
	}

	/* methods for body images */

	/**
	 * Get all possible image mime types
	 *
	 * @return string mime type
	 */
	private function GetImageMimeTypes()
	{
		return array(
			'.bmp'  => 'image/bmp',
			'.jpg'  => 'image/jpeg',
			'.jpeg' => 'image/jpeg',
			'.jpe'  => 'image/jpeg',
			'.gif'  => 'image/gif',
			'.png'  => 'image/png',
			'.tiff' => 'image/tiff',
			'.tif'  => 'image/tiff',
			'.ico'  => 'image/x-icon',
		);
	}

	/**
	 * Get image file mime type according to its extention
	 *
	 * @return mixed mime type
	 */
	private function GetImageMimeType($filename)
	{
		$types = $this->GetImageMimeTypes();
		foreach($types as $tExt=>$tMime)
		{
			$ext = substr($filename, -strlen($tExt));
			if (strtolower($ext) == $tExt)
			{
				return $tMime;
			}
		}
		return false;
	}

	/**
	 * Find all email body images and attach them to email
	 *
	 */
	private function ProcessBodyImages()
	{
		// images
		$matches = array();
		preg_match_all('/(?<=<img)([^>]+(?<=src=")([^"]+)(?=")[^>]+)(?=>)/i', $this->body, $matches);
		$this->ProcessBodyImagesFiles($matches[2]);

		// background images
		$matches = array();
		preg_match_all('/(?<=<)([^>]+(?<=background=")([^"]+)(?=")[^>]+)(?=>)/i', $this->body, $matches);
		$this->ProcessBodyImagesFiles($matches[2]);

		// input type=image
		$matches = array();
		preg_match_all('/(?<=<input)([^>]+(?<=src=")([^"]+)(?=")[^>]+)(?=>)/i', $this->body, $matches);
		$this->ProcessBodyImagesFiles($matches[2]);

		$this->ReplaceImagesToAttach();
	}

    /**
     * Attach found body images to email
     *
     * @param array $arr
     */
	private function ProcessBodyImagesFiles($arr)
	{
		if (!Utils::IsArray($arr)) return;
		
		$root = 'http://'.$_SERVER['HTTP_HOST'].'/';
		
	    foreach($arr as $file)
	    {
            $fileReplace = $file;
			//define if this is url
			if (strtolower(substr($file, 0, 7)) == 'http://' || strtolower(substr($file, 0, 8)) == 'https://')
			{
				//need url fopen wrapper
			}
			elseif ($file{0} == '/')
			{
                if (file_exists($file))
                {
                    //need no more
				}
				elseif (!file_exists($file))
				{
					//what to do ?
					$newFile =  $root.substr($file, -strlen($file)+1);
					if (fopen($newFile, 'r'))
					{
                        $fileReplace = $newFile;
					}
					else
					{
						//
					}
				}
			}

		    $this->AppendImageToAttach($file, $fileReplace);
		}
	}

	/**
	 * Add body image to attachments list
	 *
	 * @param string $fileOriginal
	 * @param string $fileReplace
	 */
	private function AppendImageToAttach($fileOriginal, $fileReplace)
	{
		$fileAdded = false;

		foreach($this->imagesToAttach as $node)
		{
			if ($node['file_orig'] == $fileOriginal)
			{
				$fileAdded = true;
				break;
			}
		}

		if (!$fileAdded)
		{
			$this->imagesToAttach[] = array(
				'file_orig' => $fileOriginal,
				'file'      => $fileReplace,
				'filename'  => basename($fileReplace),
				'cid'       => uniqid('img', false),
				'mime_type' => $this->GetImageMimeType($fileReplace),
			);
		}
	}

	/**
	 * Replace body images src tags with its content-id
	 *
	 */
	private function ReplaceImagesToAttach()
	{
		$body = $this->body;
		$images = $this->imagesToAttach;
		if (Utils::IsArray($images))
		{
			foreach($images as $node)
			{
				$body = str_replace('src="'.$node['file_orig'].'"', 'src="cid:'.$node['cid'].'"', $body);
				$body = str_replace('SRC="'.$node['file_orig'].'"', 'src="cid:'.$node['cid'].'"', $body);
				$body = str_replace('background="'.$node['file_orig'].'"', 'src="cid:'.$node['cid'].'"', $body);
				$body = str_replace('BACKGROUND="'.$node['file_orig'].'"', 'src="cid:'.$node['cid'].'"', $body);
			}
			$this->body = $body;
		}
	}

	/**
	 * Save attachments file
	 *
	 * @param string $name
	 * @return boolean
	 */
	function SaveAttach($name)
	{
		GLOBAL $FileSystem;
		$file = $_FILES[$name]['name'];
		$tmp  = $_FILES[$name]['tmp_name'];
		$type = $_FILES[$name]['type'];

		if ($file == '') return true;

		if (($file != '') && (!is_uploaded_file($tmp))) return false;

		$file1 = FileSystem::MakeFilenameUnique($this->pathAttachmentFiles, $file);

		$path = $this->pathAttachmentFiles.$file1;
		$FileSystem->CopyFile($tmp, $path);

		/* google fails on empty attachments */
		if (filesize($path) < 1)
		{
			$FileSystem->DeleteFile($path);
			return false;
		}

		$file = array(
			'filename' => basename($file1),
			'file'	   => $path,
			'type'	   => $type,
			'size'     => filesize($path),
			'size_formatted' => Format::FileSize(filesize($path)),
		);

		$this->AddAttachment($file);

		return true;
	}

	/**
	 * Add attachment
	 *
	 * @param array $file
	 */
	function AddAttachment($file)
	{
		$attachExists = false;

		if (Utils::IsArray($this->attachments))
		{
			foreach($this->attachments as $node)
			{
				if ($node['file'] == $file['file'])
				{
					$attachExists = true;
					break;
				}
			}
		}

		if (!$attachExists)
		{
			$this->attachments[] = $file;
		}
	}

	/**
	 * Delete attachemnt file from disk
	 *
	 * @param string $filename
	 */
	function DeleteAttachment($filename)
	{
		GLOBAL $FileSystem;
		if (!Utils::IsArray($this->attachments)) return;
		
	    foreach ($this->attachments as $k=>$node)
	    {
			if ($node['filename'] == $filename)
			{
				$FileSystem->DeleteFile($node['file']);
				unset($this->attachments[$k]);
			}
		}
	}

	/**
	 * Delete all attachments from disk
	 *
	 */
	function DeleteAllAttachments()
	{
	    if (!Utils::IsArray($this->attachments)) return;
	    
	    foreach($this->attachments as $attachment)
	    {
		    $this->DeleteAttachment($attachment['filename']);
		}
	}
	
    private function SaveMailToFile()
    {
        GLOBAL $App;
        $savePath = $App->sysRoot.'files/temp/mail/';
        $subject = $this->GenerateSubject();
        foreach ($this->recipients as $recipient)
        {
            $cont  = "Subject: {$subject}\n";
            $cont .= "To: {$recipient}\n";
            $cont .= $this->header.$this->body;
            file_put_contents("{$savePath}{$this->subject} ({$recipient}).eml", $cont);
        }
    }

}
?>