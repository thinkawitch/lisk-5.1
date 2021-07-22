<?php
/**
 * Lisk Type Image
 * @package lisk
 *
 */

//TODO image::update - fix if user updates only one thumbnail without original image change, no it empties the image field

$GLOBALS['App']->Load('image_base', 'type');

class T_image extends T_image_base
{
    private $ftpName;

	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		
		$this->tplFile = 'type/image';
	}

	public function Insert(&$values)
	{
		$methodType = @$values[$this->name.'_add_image_method'];

		$canInsert = false;
		switch ($methodType)
		{
			case 'upload':
				if ($this->IsFileUploaded())
				{
				    $this->InitUploadedNameAndExt();
				    if ($this->IsFileTypeValid())
				    {
                        $canInsert = $this->UploadImage();
				    }
				}
				break;

			case 'my_pictures':
				$this->ftpName = $values['ftp_'.$this->name];
				if (strlen($this->ftpName))
				{
            	    $this->InitFtpSelectedNameAndExt();
            	    if ($this->IsFileTypeValid())
			        {
			           $canInsert = $this->UploadImage();
			        }
				}
				break;
		}

		if ($canInsert) return $this->GetValueToSave();
		else return false;
	}


	public function Update(&$values)
	{
		GLOBAL $App, $Db;
        
		//don't update with absent id, this is incorrect
		if (!isset($values['id'])) return false;
		
		$di = Data::Create($this->dataItem->name, false);
        $this->value = $Db->Get('id='.$values['id'], $this->name, $di->table);
        $this->InitStateFromValue();

		$actionVarName = @$this->name.'_edit_action';
		$methodVarName = @$this->name.'_add_image_method';

		if (!isset($values[$actionVarName])) $values[$actionVarName] = 'change';

		switch ($values[$actionVarName])
		{
			case 'change':
				switch ($values[$methodVarName])
				{
					case 'upload':
            				if ($this->IsFileUploaded())
            				{
            				    if (!strlen($this->fileShortName)) $this->InitUploadedNameAndExt();
            				    if ($this->IsFileTypeValid())
            				    {
                                    $isUploaded = $this->UploadImage();
                                    if (!$isUploaded) return false;
            				    }
            				}
						break;

					case 'my_pictures':
            				$this->ftpName = $values['ftp_'.$this->name];
        					if (strlen($this->ftpName))
        					{
                        	    if (!strlen($this->fileShortName)) $this->InitFtpSelectedNameAndExt();
                        	    if ($this->IsFileTypeValid())
        				        {
        				           $isUploaded = $this->UploadImage();
        				           if (!$isUploaded) return false;
        				        }
        					}
						break;

					default:
						$App->RaiseError('T_image::Update() unknown method!');
                    break;

				}
				break;
				
			case 'delete':
				$this->DeleteFiles();
				$this->InitDefaultState();
				break;
				
			case 'recreate':
				$this->RecreateThumbnails();
				break;
				
			default:
			    return false;
				break;
		}

		return $this->GetValueToSave();
	}

	public function RenderFormTplView()
	{
		GLOBAL $ImageLibrary;

		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

		$originalPath = $this->state[self::ORIGINAL]['path'];
		$exists = file_exists($originalPath) && is_file($originalPath);

		if (!$exists)
		{
			foreach ($this->thumbnails as $key=>$thumbnail)
			{
				$tpl->SetCurrentBlock('new_thumbnail');
				$tpl->SetVariable(array(
					'NAME'				=> $this->name,
					'THUMBNAIL_NAME'	=> Format::Label($thumbnail['name']),
					'THUMBNAIL_WIDTH'	=> $thumbnail['width'],
					'THUMBNAIL_HEIGHT'	=> $thumbnail['height'],
					'THUMBNAIL_KEY'		=> $key
				));
				$tpl->ParseCurrentBlock();
			}
			// create original
			$tpl->SetCurrentBlock('new');
			$tpl->SetVariable(array(
				'NAME'		=> $this->name,
				'CUR_VALUE'	=> $this->fileShortName,
				'MAX_SIZE'	=> $this->maxUploadSize,
				'PARAMS' => $this->RenderFormParams(),
			));
			$tpl->ParseCurrentBlock();
		}
		else
		{
			// create thumbnails
			foreach ($this->thumbnails as $key=>$params)
			{
				$thumb = $this->state[$key];
				// consider thumbnail exists
				if (strlen($thumb['filename']))
				{

					$tpl->SetCurrentBlock('update_thumbnail');
					$tpl->SetVariable(array(
						'THUMBNAIL_NAME'		=> Format::Label($params['name']),
						'THUMBNAIL_W'			=> $thumb['width'],
						'THUMBNAIL_H'			=> $thumb['height'],
						'THUMBNAIL_FILE_NAME'	=> $thumb['httpPath'],
						'THUMBNAIL_FILE_SIZE'	=> Format::FileSize(filesize($thumb['path'])),
						'THUMBNAIL_ZOOM'        => $this->RenderZoom($params['name']),
					));
					$tpl->ParseCurrentBlock();

					// change thumbnails input boxes
					$tpl->ParseVariable(array(
						'THUMBNAIL_NAME'		=> Format::Label($params['name']),
						'THUMBNAIL_KEY'			=> $key,
						'NAME'					=> $this->name,
						'THUMBNAIL_W'			=> $params['width'],
						'THUMBNAIL_H'			=> $params['height'],
					),'upload_thumbnail');

				}
				else
				{
					$tpl->ParseVariable(array(
						'THUMBNAIL_NAME'		=> Format::Label($params['name']),
						'THUMBNAIL_W'			=> 0,
						'THUMBNAIL_H'			=> 0,
						'THUMBNAIL_FILE_SIZE'	=> Format::FileSize(0),
						'THUMBNAIL_FILE_NAME'	=> '',
					),'update_thumbnail');

					// change thumbnails input boxes
					$tpl->ParseVariable(array(
						'THUMBNAIL_NAME'		=> Format::Label($params['name']),
						'THUMBNAIL_KEY'			=> $key,
						'NAME'					=> $this->name,
						'THUMBNAIL_W'			=> $params['width'],
						'THUMBNAIL_H'			=> $params['height'],
					),'upload_thumbnail');
				}

			}

			$orig = $this->state[self::ORIGINAL];
			$sys = $this->state[self::THUMBNAIL_SYSTEM];

			$info = getimagesize($orig['path']);

			$tpl->ParseVariable(array(
				'ORIGINAL_SRC'				=> $orig['httpPath'],
			    'NAME'						=> $this->name,
			    'CUR_VALUE'					=> $this->fileShortName,
				'ORIGINAL_THUMBNAIL_SRC'	=> $sys['httpPath'],
				'ORIGINAL_THUMBNAIL_INFO'	=> 'width="'.$sys['width'].'" height="'.$sys['height'].'"',
				'TIMESTAMP'					=> time(),
				'IMAGE_TYPE'				=> strtoupper($ImageLibrary->GetTypeName($info[2])),
				'IMAGE_W'					=> $orig['width'],
				'IMAGE_H'					=> $orig['height'],
				'IMAGE_FILE_SIZE'			=> Format::FileSize(filesize($orig['path'])),
				'MAX_SIZE'				    => $this->maxUploadSize,
				'ORIGINAL_ZOOM'             => $this->RenderZoom('original'),
				'PARAMS' => $this->RenderFormParams(),
			),'update');
		}

		return $tpl->Get();
	}

	private function UploadImage()
	{
		$isSaved = $this->SaveUploadedFile();

		if (!$isSaved) return false;

		$this->CreateSystemThumbnail();

		foreach (array_keys($this->thumbnails) as $key)
		{
			if (@$_POST[$this->name.'_thumbnails_creation'] == 'custom')
			{
				$resizeFlag = (@$_POST[$this->name.'_resizing']!=1);
				$this->SaveUploadedFile($key, $resizeFlag);
			}
			else
			{
				$this->CreateThumbnail($key);
			}
		}
		return true;
	}

	private function SaveUploadedFile($key=null, $resize=true)
	{
		GLOBAL $App,$FileSystem,$ImageLibrary;

		$params = ($key!==null) ? $this->thumbnails[$key] : array('width'=>null, 'height'=>null, 'crop' => false);

		if($this->ftpName!='')
		{
			$serverFileName = basename($this->ftpName);
			$serverFile = $App->sysRoot.$App->filePath.'_system/'.$this->ftpName;

			if (!file_exists($serverFile))
			{
				$App->SetError('File you selected was not found on ftp. Please check it.');
				return false;
			}

			$fileInfo = getimagesize($serverFile);
			$fileType = sprintf("%d", @$fileInfo[2]);
			if ($fileType == 0)
			{
				$App->SetError("File $serverFileName is not an image file");
				return false;
			}

			// create folder(s) if not exist(s)
			if (!file_exists($this->path)) $FileSystem->CreateDir($this->path);

			$dstFileName = $this->path.$this->MakeFileName($key);

			if ($resize)
			{
				$ImageLibrary->Resize($serverFile, $dstFileName, $params['width'], $params['height'], true, $params['crop']);
			}
			else
			{
				$ImageLibrary->Resize($serverFile, $dstFileName, 0, 0);
			}

			$this->UpdateState($key);
			return true;

		}
		else
		{
			$name = ($key!==null) ? $this->name.'_upload_'.$key : $this->name.'_upload';

			$originalFileName = $_FILES[$name]['name'];
			$tmpFileName      = $_FILES[$name]['tmp_name'];

		    // check if file is image ?
			$fileInfo = getimagesize($tmpFileName);
			$fileType = sprintf("%d", @$fileInfo[2]);
			if ($fileType == 0)
			{
				$App->setError("File $originalFileName is not an image file");
				return false;
			}

			// create folder(s) if not exist(s)
			if (!file_exists($this->path)) $FileSystem->CreateDir($this->path);

			$dstFileName = $this->path.$this->MakeFileName($key);

			if ($resize)
			{
				$ImageLibrary->Resize($tmpFileName, $dstFileName, $params['width'], $params['height'], true, $params['crop']);
			}
			else
			{
				$ImageLibrary->Resize($tmpFileName, $dstFileName, 0, 0);
			}

			$this->UpdateState($key);
			return true;
		}
	}

	private function InitFtpSelectedNameAndExt()
	{
	    $this->fileShortName = $this->GenerateShortName();
	    $this->fileExt = FileSystem::GetFileExtension($this->ftpName);
	    $this->fileExt = strtolower($this->fileExt);
	}
}

?>