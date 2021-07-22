<?php
/**
 * FileManager
 * @package lisk
 *
 */

class FileManager
{
    private $categories = array( /* name => type */
    	'Image'		=> 'image',
    	'Document'	=> 'document',
    	'Media'		=> 'media',
    	'Flash'		=> 'flash',
    	'File'		=> 'file',
	);
	
	private $extensions = array( /* type => extension */
    	'image'		=> array('bmp', 'gif', 'jpg', 'jpeg', 'jpe', 'png', 'tif', 'tiff', 'psd'),
    	'document'	=> array('doc', 'rtf', 'xls', 'pdf', 'txt', 'docx', 'xlsx', 'csv'),
    	'media'		=> array('mpg', 'mpeg', 'avi', 'mp3', 'wav', 'mov', 'mid', 'midi', '3gp', 'mp4', 'divx', 'flv'),
    	'flash'		=> array('swf', 'flv', 'fla'),
    	'file'		=> array('*'),
	);
	
	private $deniedExts = array('html', 'htm', 'shtm', 'shtml', 'js', 'php', 'php3', 'php5', 'pl', 'cgi');
	
	private $icons = array(
		'bmp', 'gif', 'info', 'jpg', 'jpeg', 'png', 'swf', 'doc', 'rtf', 'xls',
	    'pdf', 'avi', 'mpg', 'mpeg', 'wav', 'mp3', 'txt', 'gzip', 'zip', 'rar',
	    'jar', 'flv',
    );
    
    
	private $basePath;
	private $baseHttpPath;
	private $baseImageEditorPath;
	
	private $dir;
	
	private $isCorrectDir;
	
	const ERROR_WRONG_FILE_TYPE = 1;
	const ERROR_WRONG_DIRECTORY = 2;
	const ERROR_FILE_NOT_UPLOADED = 3;
	
	function __construct($dir)
	{
		GLOBAL $App;
		
		$this->basePath = $App->sysRoot.$App->filePath . '_system/';
		$this->baseHttpPath = $App->httpRoot.$App->filePath . '_system/';
		$this->baseImageEditorPath = $App->filePath . '_system/';
		
		$this->Init($dir);
	}
	
	function Init($dir)
	{
	    $this->isCorrectDir = false;
	    
	    if (is_dir($this->basePath.$dir) && is_readable($this->basePath.$dir))
	    {
	        $this->isCorrectDir = true;
	        $this->dir = FileSystem::NormalizeDirPath($dir);
	    }
	}
	
	function GetDirectory()
	{
	    return $this->dir;
	}
    
	function GetDirectoryType($dir)
	{
        $chunks = explode('/', $dir);
        if (isset($chunks[0]) && isset($this->categories[$chunks[0]])) return $this->categories[$chunks[0]];
        else return null;
	}
	
	function GetDirectorySubdirs()
	{
	    if (!$this->isCorrectDir) return null;
	    
	    $dir = $this->basePath.$this->dir;
	    $list = array();
	    
	    $handle = opendir($dir);
		if ($handle)
		{
    		while (false !== ($file = readdir($handle)))
    		{
    			if ($file != '.' && $file != '..' && $file != '.svn' && $file != '_thumbs' && is_dir($this->basePath.$this->dir.$file))
    			{
    			    $list[] = array(
						'dir_name' => $file,
    					'dir_path' => $this->dir.$file,
    					'type' => $this->GetDirectoryType($this->dir)
                    );
    			}
    		}
    		closedir($handle);
		}
	    
		return $list;
	}
	
	function GetDirectoryFiles()
	{
	    if (!$this->isCorrectDir) return null;
	    
	    $dir = $this->basePath.$this->dir;
	    $list = array();
	    
	    $handle = opendir($dir);
		if ($handle)
		{
    		while (false !== ($file = readdir($handle)))
    		{
    			if ($file != '.' && $file != '..' && is_file($this->basePath.$this->dir.$file))
    			{
    			    $imageSize = @getimagesize($this->basePath.$this->dir.$file);
    			    $ext = strtolower(FileSystem::GetFileExtension($file));
    			    $ico = 'i_blank.gif';
    			    $icoBig = 'i_big_blank.gif';
    			    if (in_array($ext, $this->icons))
    			    {
    			        $ico = 'i_'.$ext.'.gif';
    			        $icoBig = 'i_big_'.$ext.'.gif';
    			    }
    			    
    			    $list[] = array(
                    	'name'		    => $file,
                        'path1'		    => $this->baseHttpPath.$this->dir.$file,
                        'path2'         => urlencode($this->baseImageEditorPath.$this->dir.$file),
                        'iwidth'	    => $imageSize[0],
                        'iheight'       => $imageSize[1],
                        'current_dir'   => $this->dir,
                        'img_path'	    => $this->dir.$file,
                        'file_type'	    => $this->GetDirectoryType($this->dir.$file),
                        'ext'		    => FileSystem::GetFileExtension($file),
                        'size'		    => Format::FileSize(filesize($this->basePath.$this->dir.$file)),
                        'last_modified' => date('Y F d H:m:s', filemtime($this->basePath.$this->dir.$file)),
    			        'ico' => $ico,
    			        'ico_big' => $icoBig,
                    );
					
    			}
    		}
    		closedir($handle);
		}
	    
		return $list;
	}
	
	
	/**
	 * Create directory
	 *
	 * @param string $dirName
	 * @return boolean
	 */
	function CreateDirectory($dirName)
	{
		GLOBAL $FileSystem;

		$dirName = Format::Filename($dirName);
		$dirName = FileSystem::MakeFilenameUnique($this->basePath.$this->dir, $dirName);
		
		return $FileSystem->CreateDir($this->basePath.$this->dir.$dirName, '0777');
	}


	/**
	 * Delete directory
	 *
	 * @param string $dir
	 * @return boolean
	 */
	function DeleteDirectory($dir)
	{
		GLOBAL $FileSystem;
		//don't delete files/_system/ directory
		if (!strlen($dir)) return false;
		
		//don't delete files/_system/{type}/ directory
		$chunks = explode('/', $dir);
        if (count($chunks)<2) return false;
        
        return $FileSystem->DeleteDir($this->basePath.$dir);
	}
	
	
	/**
	 * Uploads file in the current directory
	 *
	 * @return mixed
	 */
	function UploadFile()
	{
	    GLOBAL $FileSystem;

		if (is_uploaded_file($_FILES['file']['tmp_name']))
		{
		    $fileName = $_FILES['file']['name'];
		    $checkResult = $this->CheckFileExt($fileName);
			if ($checkResult!==true) return $checkResult;
			
			$fileName = Format::Filename($fileName);
			$fileName = FileSystem::MakeFilenameUnique($this->basePath.$this->dir, $fileName);
			
			$dst = $this->basePath.$this->dir.$fileName;

    		if (!move_uploaded_file($_FILES['file']['tmp_name'], $dst)) return self::ERROR_FILE_NOT_UPLOADED;
    		
            $FileSystem->ChangeMode($dst);
            return true;
		}
		else return true;
	}
	
	
	/**
	 * Delete file
	 *
	 * @param string $file
	 * @return boolean
	 */
	function DeleteFile($file)
	{
		GLOBAL $FileSystem;
		return $FileSystem->DeleteFile($this->basePath.$file);
	}

	

	/**
	 * Make current directories path (links location)
	 *
	 * @return array
	 */
	function GetNavigation()
	{
		$dirs = explode('/', $this->dir);
        $list = array();
        
		$linksPath = '';

		foreach ($dirs as $dir)
		{
			if (strlen($dir)&& $dir!='..')
			{
				$linksPath .= $dir.'/';
				$list[] = array(
    				'link_name' => $dir,
    				'link_dir_path' => $linksPath,
    				'type' => $this->GetDirectoryType($linksPath)
				);
				
			}
		}
		
		return $list;
	}


	/**
	 * Check file extension, if allowed
	 *
	 * @param string $file
	 * @return boolean
	 */
	private function CheckFileExt($file)
	{
		$ext = strtolower(FileSystem::GetFileExtension($file));
		
		//first check if extension is denied
		if (in_array($ext, $this->deniedExts)) return self::ERROR_WRONG_FILE_TYPE;
		
		$type = $this->GetDirectoryType($this->dir);
		
		//wrong directory
		if (!isset($this->extensions[$type])) return self::ERROR_WRONG_DIRECTORY;
		
		// "files" directory may contain all allowed types
		if ($type=='file') return true;
		
		if (!in_array($ext, $this->extensions[$type])) return self::ERROR_WRONG_FILE_TYPE;
		
		return true;
	}
}

?>