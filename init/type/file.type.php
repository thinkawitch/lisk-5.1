<?php
/**
 * Lisk Type File
 * @package lisk
 *
 */
class T_file extends LiskType
{
	/**
	 * Name of input box used when we insert/change file using file chooser
	 *
	 * @var string
	 */
	public $uploadFtpName;
	
	/**
	 * Name on file input field used when we insert/change file using file from user's disk
	 *
	 * @var string
	 */
	public $uploadHttpName;

	public $path;
	public $httpPath;
	public $maxUploadSize;

	public $fileLink;
	public $fileSize;
	
	/**
	 * Dangerous files, don't process on upload
	 *
	 * @var array
	 */
	private $dangerousFiles = array('php', 'php3', 'php4', 'php5', 'pl', 'sh', 'cgi', 'rb', 'py');
	
	/**
	 * @var Data
	 */
	public $dataItem;

	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		GLOBAL $App;

		$App->Load('filesystem', 'utils');

		$info['path'] = FileSystem::NormalizeDirPath($info['path']);
		$this->httpPath = $info['path'];
		$this->path     = $App->sysRoot.$App->filePath.$info['path'];

		$this->maxUploadSize = ini_get('upload_max_filesize').'b';
		
		$this->uploadFtpName = $this->name.'_upload_ftp';
		$this->uploadHttpName = $this->name.'_upload_http';
		
		$this->type = LiskType::TYPE_FILE;
		$this->tplFile = 'type/file';
	}

	public function Insert(&$values)
	{
		$method = @$values[$this->name.'_upload_method'];
		switch ($method)
		{
			case 'ftp':
				$fileName = $this->UploadFtpFile($values[$this->uploadFtpName]);
				break;
			
			default:
				$fileName = $this->UploadHttpFile();
				break;
		}
		
		return $fileName;
	}
	
	public function Update(&$values)
	{
		GLOBAL $FileSystem,$Db;
		
		$action = @$values[$this->name.'_edit_action'];
		
		switch ($action)
		{
			case 'delete':
				$this->value = $Db->Get('id='.$values['id'], $this->name, $this->dataItem->table);
				$this->DeleteFile();
				return '';
				break;
			
			case 'none':
				return false;
				break;
				
			default:
				// vibor upload methoda
				switch ($values[$this->name.'_upload_method'])
				{
					case 'ftp':
						$fileName = $this->UploadFtpFile($values[$this->uploadFtpName]);
						break;
					
					default:
						$fileName = $this->UploadHttpFile();
						break;
				}
				
				if ($fileName != '')
				{
					$oldFileName = $Db->Get('id='.$values['id'], $this->name, $this->dataItem->table);
					if ($oldFileName != '') $FileSystem->DeleteFile($this->path.$oldFileName);
					
					return $fileName;
				}
				else
				{
					return false;
				}
				break;
		}
	}
	
	public function Delete(&$values)
	{
		if (Utils::IsArray($values))
		{
			foreach ($values as $curValue)
			{
				$this->value = $curValue[$this->name];
				$this->DeleteFile();
			}
		}
	}

	

	/**
	* @return boolean True -- image new, no file(s) uploaded; false -- otherwise
	* @desc Check whether file new or exists
	*/
	public function FileExists()
	{
		return (strlen($this->value) && file_exists($this->path.$this->value));
	}

	public function RenderFormTplView()
	{
		GLOBAL $App;

		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

		$this->fileLink = $App->httpRoot.$App->filePath.$this->httpPath.$this->value;

		if (!$this->FileExists())
		{
			$tpl->SetCurrentBlock('new');
			$tpl->SetVariable(array(
				'NAME'	   => $this->name,
				'MAX_SIZE' => $this->maxUploadSize,
				'PARAMS'   => $this->RenderFormParams(),
			));
			$tpl->ParseCurrentBlock();
		}
		else
		{
			$tpl->SetCurrentBlock('update');
			$tpl->SetVariable(array(
				'NAME'		   => $this->name,
				'MAX_SIZE'	   => $this->maxUploadSize,
				'FILE_NAME'	   => $this->value,
				'FILE_LINK'	   => $this->fileLink,
				'FILE_SIZE'	   => Format::FileSize(filesize($this->path.$this->value)),
				'FILE_DELETE'  => $this->name.'_delete',
				'EDITORS_PATH' => 'editors/',
				'PARAMS' => $this->RenderFormParams(),
			));
			$tpl->ParseCurrentBlock();
		}

		return $tpl->Get();

	}
	
	public function RenderFormHtmlView()
	{
		return "<input type=\"hidden\" name=\"{$this->name}\" /><input type=\"file\" name=\"{$this->uploadHttpName}\" ".$this->RenderFormParams()." />";
	}

	public function RenderFormView()
	{
		switch ($this->formRender)
		{
			case 'tpl':
				return $this->RenderFormTplView();
				break;
			default:
				return $this->RenderFormHtmlView();
				break;
		}
	}

	public function RenderView($param1=null, $param2=null)
	{
		GLOBAL $App;
		if (!$this->FileExists()) return '';
			

		$this->fileLink = $App->httpRoot.$App->filePath.$this->httpPath.$this->value;
		$this->fileSize = Format::FileSize(filesize($this->path.$this->value));

		switch (strtoupper($param1))
		{
			case 'SRC':
				return $this->fileLink;
				break;
				
			case 'SIZE':
				return $this->fileSize;
				break;
				
			case 'FILENAME':
				return $this->value;
				break;

			default:
				return ($this->formRender=='tpl') ? $this->RenderTplView() : "{$this->value}, {$this->fileSize}";
				break;
		}

	}

	public function RenderTplView()
	{
		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		
		$tpl->ParseVariable(array(
			'file_name'	=> $this->value,
			'link'		=> $this->fileLink,
			'size'		=> $this->fileSize
		),'view');
		return $tpl->Get();
	}
	
	

	/**
	 * Insert/Update handler for uploading file from Site files
	 *
	 * @param string $fileName file from Site Files
	 * @return string uploaded file name or false
	 */
	private function UploadFtpFile($fileName)
	{
		GLOBAL $FileSystem,$App;
		
		$serverFileName = basename($fileName);
		$serverFileName = Format::Filename($serverFileName);
		$serverFile = $App->sysRoot.$App->filePath.'_system/'.$fileName;
		
	    if (!$this->IsFileSafe($fileName))
		{
			$App->SetError('File is not supported');
			return false;
		}

		// check file if exists
		if (!file_exists($serverFile))
		{
			$App->SetError('The file you have selected was not found. Please check it and try again.');
			return false;
		}

		// create folder(s) if not exist(s)
		if (!file_exists($this->path)) $FileSystem->CreateDir($this->path);

		$realName = $this->CopyFile($serverFile, $this->path.$serverFileName);

		if ($realName=='') return false;
		else return $realName;
	}
	
	/**
	 * Insert/Update handler for uploading file via http from user's PC
	 *
	 * @return string FileName or false if not file was uploaded
	 */
	private function UploadHttpFile()
	{
		GLOBAL $FileSystem, $App;
		
		if (!isset($_FILES[$this->uploadHttpName]) || $_FILES[$this->uploadHttpName]['error']>0 ) return false;

		$realName	= $_FILES[$this->uploadHttpName]['name'];
		$tmpName	= $_FILES[$this->uploadHttpName]['tmp_name'];
		
	    if (!$this->IsFileSafe($realName))
		{
			$App->SetError('File is not uploaded');
			return false;
		}

		if ($realName != '' && !is_uploaded_file($tmpName))
		{
			$App->SetError('File is not received');
			return false;
		}

		// create folder(s) if not exist(s)
		if (!file_exists($this->path)) $FileSystem->CreateDir($this->path);

		$realName = Format::Filename($realName);
		$realName = $this->CopyFile($tmpName, $this->path.$realName);
		
		if ($realName=='') return false;
		else return $realName;
	}

	private function CopyFile($tmp, $dst)
	{
		GLOBAL $FileSystem;
		
		$pathParts = pathinfo($dst);
		$dstDir = FileSystem::NormalizeDirPath($pathParts['dirname']);
		$dstFile = $pathParts['basename'];
		$newName = FileSystem::MakeFilenameUnique($dstDir, $dstFile);
		
		$dstFile = $dstDir.$newName;
		
		//TODO proverit'
		if (!$FileSystem->CopyFile($tmp, $dstFile)) return '';
		else return basename($dstFile);
	}

	function DeleteFile()
	{
		GLOBAL $FileSystem;
		return $FileSystem->DeleteFile($this->path.$this->value);
	}
	
	function IsFileSafe($filename)
	{
	    GLOBAL $FileSystem;
	    
		$ext = $FileSystem->GetFileExtension($filename);
		$ext = strtolower($ext);
		
		foreach ($this->dangerousFiles as $danExt)
		{
		    if ($danExt == $ext)
		    {
		        //bad file
		        return false;
		    }
		}
		
		return true;
	}
}
?>