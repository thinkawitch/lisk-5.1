<?php
/**
 * Lisk Type ImageSimple
 * @package lisk
 *
 */
$GLOBALS['App']->Load('image_base', 'type');

class T_image_simple extends T_image_base
{

    function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		
		$this->tplFile = 'type/image_simple';
	}
	
	public function Insert(&$values)
	{
	    $canInsert = false;
	    if ($this->IsFileUploaded())
		{
		    $this->InitUploadedNameAndExt();
		    if ($this->IsFileTypeValid())
		    {
                $canInsert = $this->UploadImage();
		    }
		}
		
		if ($canInsert) return $this->GetValueToSave();
		else return false;
	}
	
	public function Update(&$values)
	{
	    GLOBAL $Db;

		//don't update with absent id, this is incorrect
		if (!isset($values['id'])) return false;
		
		$di = Data::Create($this->dataItem->name, false);
        $this->value = $Db->Get('id='.$values['id'], $this->name, $di->table);
        $this->InitStateFromValue();

		$actionVarName = @$this->name.'_edit_action';
		if (!isset($values[$actionVarName])) $values[$actionVarName] = 'change';
		
		switch ($values[$actionVarName])
		{
			case 'change':
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
			    
			case 'delete':
			    $this->DeleteFiles();
				$this->InitDefaultState();
			    break;
			    
			default:
			    return false;
				break;
		}
		
		return $this->GetValueToSave();
	}
	
    private function UploadImage()
	{
		$isSaved = $this->SaveUploadedFile();

		if (!$isSaved) return false;
		
		$this->RecreateThumbnails();
		
		return true;
	}
	
    private function SaveUploadedFile($key=null, $resize=true)
	{
		GLOBAL $App, $FileSystem, $ImageLibrary;

		$params = ($key!==null) ? $this->thumbnails[$key] : array('width'=>null, 'height'=>null, 'crop' => false);

		$name = ($key!==null) ? $this->name.'_upload_'.$key : $this->name.'_upload';

		$originalFileName = $_FILES[$name]['name'];
		$tmpFileName      = $_FILES[$name]['tmp_name'];

	    // check if file is an image ?
		$fileInfo = getimagesize($tmpFileName);
		$fileType = sprintf('%d', @$fileInfo[2]);
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
	
	public function RenderFormTplView()
	{
		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

		$originalPath = $this->state[self::ORIGINAL]['path'];
		$exists = file_exists($originalPath) && is_file($originalPath);

		if (!$exists)
		{
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
			$orig = $this->state[self::ORIGINAL];
			//first thumbnail to be used in preview
			$sys = null;
			foreach ($this->state as $key=>$params)
			{
			    if ($key!=self::THUMBNAIL_SYSTEM && $key!=self::ORIGINAL)
			    {
			        $sys = $params;
			        break;
			    }
			}

			$tpl->ParseVariable(array(
				'ORIGINAL_SRC'    => $orig['httpPath'],
			    'NAME'			  => $this->name,
			    'CUR_VALUE'		  => $this->fileShortName,
				'THUMB_SRC'	      => $sys['httpPath'],
				'THUMB_INFO'	  => 'width="'.$sys['width'].'" height="'.$sys['height'].'"',
				'IMAGE_FILE_SIZE' => Format::FileSize(filesize($orig['path'])),
				'TIMESTAMP'		  => time(),
				'ORIGINAL_ZOOM'   => $this->RenderZoom('original'),
				'PARAMS'          => $this->RenderFormParams(),
			), 'update');
		}

		return $tpl->Get();
	}
}

?>