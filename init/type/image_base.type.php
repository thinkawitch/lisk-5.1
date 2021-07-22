<?php
/**
 * Lisk Type ImageBase
 * @package lisk
 *
 */
abstract class T_image_base extends LiskType
{
    const ORIGINAL = 'original';
    const THUMBNAIL_SYSTEM = 's';

    /**
     * @var string
     */
	public $httpPath;
	
	/**
     * @var string
     */
    public $path;
    
    /**
     * @var array
     */
	public $object;
	
	/**
     * @var string
     */
    protected $noImage;

    /**
     * @var array
     */
	protected $thumbnails;

	/**
	 * @var string
	 */
	protected $maxUploadSize;

	protected $allowedTypes = array(
	    'jpg',
		'jpeg',
		'gif',
		'png',
		'bmp',
	);

	/**
	 * image file extension
	 *
	 * @var string
	 */
	protected $fileExt;
	
	/**
	 * image file name without extension
	 *
	 * @var string
	 */
	protected $fileShortName;

	/**
	 * see InitDefaultState() for its structure
	 *
	 * @var array
	 */
	protected $state;

	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		GLOBAL $App;
		$App->Load('imagelibrary', 'utils');

		if (!isset($info['object'])) $App->RaiseError('Image object property not set!');

		if (Utils::IsArray($info['object']))  $this->object = $info['object'];
		else
		{
		    $this->object = $GLOBALS['IMAGE_'.strtoupper($info['object'])];
		    if (!Utils::IsArray($this->object)) $App->RaiseError("Image {$info['object']} not found.");
		}

		$this->object['path'] = FileSystem::NormalizeDirPath($this->object['path']);

		$this->httpPath		= $App->httpRoot.$App->filePath.$this->object['path'];
		$this->path			= $App->sysRoot.$App->filePath.$this->object['path'];
		$this->noImage		= isset($this->object['no_image']) ? $this->object['no_image'] : false;

		$this->thumbnails	= isset($this->object['thumbnails']) && Utils::IsArray($this->object['thumbnails']) ? $this->object['thumbnails'] : array();
	    if (Utils::IsArray($this->thumbnails))
		{
		    foreach ($this->thumbnails as $k=>$v)
		    {
		        $this->thumbnails[$k]['crop']  = (isset($v['crop']) && $v['crop']==true);
		    }
		}

		$this->maxUploadSize= ini_get('upload_max_filesize').'b';

		$this->type = LiskType::TYPE_IMAGE;
		
		$this->InitDefaultState();
	}

	public function Delete(&$values)
	{
		if (Utils::IsArray($values))
		{
			foreach ($values as $curValue)
			{
				$this->value = @$curValue[$this->name];
				$this->InitStateFromValue();
				$this->DeleteFiles();
			}
		}
		return true;
	}


	protected function DeleteFiles()
	{
	    GLOBAL $FileSystem;
	    $FileSystem->DeleteFile($this->GetFileParam(self::ORIGINAL, 'path'));
	    $FileSystem->DeleteFile($this->GetFileParam(self::THUMBNAIL_SYSTEM, 'path'));
	    foreach (array_keys($this->thumbnails) as $key)
		{
			$FileSystem->DeleteFile($this->GetFileParam($key, 'path'));
		}
	}

	protected function RecreateThumbnails()
	{
	    foreach (array_keys($this->thumbnails) as $key)
		{
			$this->CreateThumbnail($key);
		}
		$this->CreateSystemThumbnail();
	}

	public function RecreateUserThumbnails($value)
	{
	    $this->value = $value;
	    $this->InitStateFromValue();
	    foreach (array_keys($this->thumbnails) as $key)
		{
			$this->CreateThumbnail($key);
		}
	}

	public function RenderFormView()
	{
	    $this->InitStateFromValue();

		return $this->RenderFormTplView();
	}

	public function RenderZoom($zoomName)
	{
	    $zoomKey = null;
		switch ($zoomName)
		{
			case '':
			case self::ORIGINAL:
				$zoomKey = null;
				break;

			case self::THUMBNAIL_SYSTEM:
			case 'system':
				$zoomKey = self::THUMBNAIL_SYSTEM;
				break;

			default:
				$thumbnailFound = false;
				foreach ($this->thumbnails as $key=>$row)
				{
					if ($zoomName==$row['name'])
					{
						$zoomKey = $key;
						$thumbnailFound = true;
					}
				}
				if (!$thumbnailFound) return '';
				break;
		}

		$filename = $this->GetFileName($zoomKey);
		$path = $this->path.$filename;

		if (!file_exists($path) || !is_file($path))
		{
			if ($this->noImage) $filename = 'no_image';
	        else return '';
		}

		$fileLink	= $this->httpPath.$filename;

		return "$fileLink\" liskZoom=\"true";
	}

	public function RenderView($param1=null,$param2=null)
	{
		GLOBAL $Parser;

		$this->InitStateFromValue();

		$originalPath = $this->state[self::ORIGINAL]['path'];
		$exists = strlen($originalPath) && file_exists($originalPath) && is_file($originalPath);

		if (!$exists && !$this->noImage) return '';

		// is dynamic view/list
		if ($Parser->isDynamic)
		{
			$param1 = 'system';
			$param2 = null;
		}

		$param1 = strtolower($param1);
		$param2 = strtolower($param2);
        $thumbKey = null;
		$thumbnailFound = false;

		switch ($param1)
		{
			case 'filesize':
				return Format::FileSize(filesize($this->state[self::ORIGINAL]['path']));
				break;

			case 'view':
				foreach ($this->thumbnails as $key=>$row)
				{
					if ($param2 == $row['name'])
					{
						$thumbKey = $key;
						$thumbnailFound = true;
					}
				}
				if (!$thumbnailFound) return '';
				break;

			case '':
			case self::ORIGINAL:
				$thumbKey = null;
				break;

			case self::THUMBNAIL_SYSTEM:
			case 'system':
				$thumbKey = self::THUMBNAIL_SYSTEM;
				break;

			case 'zoom':
				return $this->RenderZoom($param2);
				break;

			default:
		        foreach ($this->thumbnails as $key=>$row)
				{
					if ($param1 == $row['name'])
					{
						$thumbKey = $key;
						$thumbnailFound = true;
					}
				}
				if (!$thumbnailFound) return '';
				break;
		}

	    $filename = $this->GetFileName($thumbKey);
		$path = $this->path.$filename;

		$width = null;
		$height	= null;

		if (!file_exists($path) || !is_file($path))
		{
			if ($this->noImage)
			{
			    $filename = 'no_image';
			    if ($thumbKey !== null) $filename .= '_'.$thumbKey;
			    $size = getimagesize($this->path.$filename);
			    $width = $size[0];
		        $height = $size[1];
			}
	        else return '';
		}
		else
		{
            $size = $this->GetFileParam($thumbKey, 'size');
            list($width, $height) = explode('x', $size);
		}

		$fileLink	= $this->httpPath.$filename;

		switch ($param2)
		{
			case 'src':
				return $fileLink;
				break;
				
			case 'info':
				return ' src="'.$fileLink.'" width="'.$width.'" height="'.$height.'" ';
				break;
				
			case 'height':
				return $height;
				break;
				
			case 'width':
				return $width;
				break;
				
			case 'size':
				return ' width="'.$width.'" height="'.$height.'" ';
				break;
				
			default:
				return '<img src="'.$fileLink.'" width="'.$width.'" height="'.$height.'"  '.$this->RenderFormParams().' />';
				break;
		}
	}

	protected function CreateSystemThumbnail()
	{
		GLOBAL $ImageLibrary,$App;
		list($systemW, $systemH) = preg_split('[x]', $App->originalThumbnailSize);
		$originalFile = $this->path.$this->MakeFileName();
		$systemFileName = $this->path.$this->MakeFileName(self::THUMBNAIL_SYSTEM);

		if (file_exists($originalFile))
		{
		    $ImageLibrary->Resize($originalFile, $systemFileName, $systemW, $systemH);
		}
		//update state hash
		$this->UpdateState(self::THUMBNAIL_SYSTEM);
	}


	protected function CreateThumbnail($key)
	{
		GLOBAL $ImageLibrary;

		$params = $this->thumbnails[$key];
		$originalFile = $this->path.$this->MakeFileName();
		$thumbnailFile = $this->path.$this->MakeFileName($key);

		if (file_exists($originalFile))
		{
			$ImageLibrary->Resize($originalFile, $thumbnailFile, $params['width'], $params['height'], true, $params['crop']);
		}
		//update state hash
		$this->UpdateState($key);
	}

	protected function UpdateState($key=null)
	{
	    if ($key===null) $this->UpdateStateOriginal();
	    else $this->UpdateStateThumbnail($key);
	}

	protected function UpdateStateOriginal()
	{
	    $node =& $this->state[self::ORIGINAL];

	    $fullname = $this->path.$this->MakeFileName();

	    if (file_exists($fullname) && is_file($fullname))
	    {
	        $node['ext'] = $this->fileExt;
		    $node['filename'] = $this->MakeFileName();

		    $node['path'] = $this->path.$node['filename'];
    		$node['httpPath'] = $this->httpPath.$node['filename'];

    		$info = getimagesize($node['path']);
    		$node['size'] = $info[0].'x'.$info[1];
	    }
	    else
	    {
	        $node = null;
	    }
	}

	protected function UpdateStateThumbnail($key)
	{
	    $node =& $this->state[$key];

	    $fullname = $this->path.$this->MakeFileName($key);

	    if (file_exists($fullname) && is_file($fullname))
	    {
	        $node['ext'] = $this->fileExt;
		    $node['filename'] = $this->MakeFileName($key);

		    $node['path'] = $this->path.$node['filename'];
    		$node['httpPath'] = $this->httpPath.$node['filename'];

    		$info = getimagesize($node['path']);
    		$node['size'] = $info[0].'x'.$info[1];
	    }
	    else
	    {
	        $node = null;
	    }
	}


	protected function InitDefaultState()
	{
	    $this->state = array(
		    self::ORIGINAL => array(
		        'filename' => null,
	            'size' => null,
		        'path' => null,
				'httpPath' => null,
		    ),
		    self::THUMBNAIL_SYSTEM => array(
	            'ext' => null,
		        'filename' => null,
	        	'size' => null,
		        'path' => null,
				'httpPath' => null,
	         ),
		);
	}

	public function InitStateFromValue()
	{
	    $this->InitDefaultState();

	    $hash = @unserialize($this->value);
	    if (!Utils::IsArray($hash)) return;

	    //original
	    $val = @$hash[self::ORIGINAL];
	    if (Utils::IsArray($val))
	    {
	        $fullname = $this->path.$val['filename'];
	        if (file_exists($fullname) && is_file($fullname))
	        {
	            $node =& $this->state[self::ORIGINAL];
	            
	            $node = $val;
	            $node['path'] = $this->path.$val['filename'];
	            $node['httpPath'] = $this->httpPath.$val['filename'];
	            
	            if (isset($val['size']))
                {
                	list($w, $h) = explode('x', $val['size']);
                	$node['width'] = $w;
                	$node['height'] = $h;
                }
                else
                {
                	$info = getimagesize($node['path']);
                	$node['size'] = $info[0].'x'.$info[1];
                	$node['width'] = $info[0];
                	$node['height'] = $info[1];
                }
	        }

	        $this->fileShortName = FileSystem::GetFileNameWOExtension($val['filename']);
	        $this->fileExt = FileSystem::GetFileExtension($val['filename']);
	    }

	    //system thumbnail
	    $val = @$hash[self::THUMBNAIL_SYSTEM];
	    if (Utils::IsArray($val))
	    {
	        $fullname = $this->path.$val['filename'];
	        if (file_exists($fullname) && is_file($fullname))
	        {
	            $node =& $this->state[self::THUMBNAIL_SYSTEM];

	            $node = $val;
	            $node['path'] = $this->path.$val['filename'];
	            $node['httpPath'] = $this->httpPath.$val['filename'];
	            
	            list($w, $h) = explode('x', $val['size']);
    	        $node['width'] = $w;
    	        $node['height'] = $h;
	        }
	    }

	    //other thumbnails
	    foreach (array_keys($this->thumbnails) as $key)
	    {
            $val = @$hash[$key];
    	    if (Utils::IsArray($val))
    	    {
    	        $fullname = $this->path.$val['filename'];
    	        if (file_exists($fullname) && is_file($fullname))
    	        {
    	            $node =& $this->state[$key];

    	            $node = $val;
    	            $node['path'] = $this->path.$val['filename'];
    	            $node['httpPath'] = $this->httpPath.$val['filename'];
    	            
    	            list($w, $h) = explode('x', $val['size']);
    	            $node['width'] = $w;
    	            $node['height'] = $h;
    	        }
    	    }
	    }
	}

	public function GetValueToSave()
	{
		$state = $this->state;
		$atLeastOneFileExists = false;
			
		foreach (array_keys($state) as $key)
	    {
	    	unset($state[$key]['path']);
			unset($state[$key]['httpPath']);
			unset($state[$key]['width']);
			unset($state[$key]['height']);
			unset($state[$key]['filesize']);
			
			if (strlen($state[$key]['filename'])) $atLeastOneFileExists = true;
	    }

	    if ($atLeastOneFileExists) return serialize($state);
	    else return '';
	}

	protected function GenerateShortName()
	{
		return uniqid('');
	}

    protected function MakeFileName($key=null)
    {
        if ($key!==null) return $this->fileShortName.'_'.$key.'.'.$this->fileExt;
        else return $this->fileShortName.'.'.$this->fileExt;
    }

    public function GetFileName($key=null)
    {
        return $this->GetFileParam($key, 'filename');
    }

    public function GetFileParam($key, $name)
    {
        if ($key === null) return $this->state[self::ORIGINAL][$name];
        elseif (isset($this->state[$key])) return $this->state[$key][$name];
        else return null;
    }

	protected function IsFileUploaded()
	{
		$name = $this->name.'_upload';
		return (isset($_FILES[$name]) && $_FILES[$name]['error']==0 && $_FILES[$name]['size']>0 && is_uploaded_file($_FILES[$name]['tmp_name']));
	}

	protected function InitUploadedNameAndExt()
	{
	    $name = $this->name.'_upload';
	    $this->fileShortName = $this->GenerateShortName();
	    $this->fileExt = FileSystem::GetFileExtension($_FILES[$name]['name']);
	    $this->fileExt = strtolower($this->fileExt);
	}
	
	protected function IsFileTypeValid()
	{
	    return (strlen($this->fileExt) && in_array($this->fileExt, $this->allowedTypes));
	}

	protected function GetFileShortName()
	{
	    return $this->fileShortName;
	}

	public function InsertFromFile($serverFile)
	{
	    GLOBAL $FileSystem;
	    
	    //is files
		if (!file_exists($serverFile) || !is_file($serverFile)) return false;
		
		//is image file
		$fileInfo = getimagesize($serverFile);
		$fileType = sprintf('%d', @$fileInfo[2]);
		if ($fileType == 0) return false;
		
		//is image file with allowed extension
		$this->fileShortName = $this->GenerateShortName();
	    $this->fileExt = FileSystem::GetFileExtension($serverFile);
	    $this->fileExt = strtolower($this->fileExt);
	    if (!$this->IsFileTypeValid()) return false;
	    
		// create directory to save image in
		if (!file_exists($this->path)) $FileSystem->CreateDir($this->path);
		
		//copy original file
		$copied = $FileSystem->CopyFile($serverFile, $this->path.$this->MakeFileName());
		if (!$copied) return false;
		$this->UpdateState();
		
		//create thumbnails
		$this->RecreateThumbnails();

		return true;
	}
}

?>