<?php
/**
 * CLASS FileSystem
 * @package lisk
 *
 */

class FileSystem
{
    /**
     * don't copy these files with CopyDir() method
     * @var array
     */
    static private $skipFiles = array('.', '..', '.svn', 'cvs');
    
	function __construct()
	{
		
	}
	
	/**
	 * @return boolean true -- if shell command was executed successfully, false -- otherwise
	 * @param string $command command to execute
	 * @desc Executes shell command.
	*/
	public static function SysExec($command)
	{
		$res = `$command 2>&1`;
		if ($res) print_r($command.'<BR>'.$res);
		return $res == 0;
	}

	/**
	 * @return boolean true -- if mode was succesfully changed, false -- otherwise
	 * @param string $name file name (path)
	 * @param string $mode new mode
	 * @param boolean $recursive is mode change resursively
	 * @desc Change file mode.
	*/
	public function ChangeMode($name, $mode='0777', $recursive=true)
	{
		GLOBAL $App;
		$this->Debug("chmod $mode", $name);
		if (!$App->isWindows)
		{
			$rec_flag = $recursive ? '-R' : '';
			return self::SysExec("chmod $rec_flag $mode '$name'");
		}
		else return true;
	}

	public function DeleteFile($name)
	{
		$this->Debug('Delete file', $name);
		if (file_exists($name) && is_file($name)) return unlink($name);
		else return false;
	}

	/**
	 * @return boolean true - if dir was succesfully created and chmoded, false -- otherwise
	 * @param string $path directory path
	 * @param string $mode directory mode
	 * @desc Creates directory.
	*/
	public function CreateDir($path, $mode='0777')
	{
		GLOBAL $App;
		$path = self::NormalizeDirPath($path);

		$this->Debug('Create directory ', $path);

		if (!$App->isWindows)
		{
			if (!self::SysExec("mkdir -p $path")) return false;
			if (!$this->ChangeMode($path, $mode, true)) return false;
			return true;
		}
		else
		{
			$new_path_start = strpos($path, 'files/');
			$new_path = substr($path, $new_path_start+6, -1);
			$basic_path = substr($path, 0, $new_path_start+6);
			$new_dirs = preg_split('[/]', $new_path);

			foreach ($new_dirs as $new_dir)
			{
				$create_dir = $basic_path.$new_dir.'/';
				if (!file_exists($create_dir)) mkdir($create_dir);
				$basic_path .= $new_dir.'/';
			}

			return true;
		}
	}

	/**
	 * @return boolean true -- if file was successfully copied, false -- otherwise
	 * @param string $src source file
	 * @param string $dst destination file
	 * @param string $mode destination file mode
	 * @desc Copies file.
	*/
	public function CopyFile($src, $dst, $mode='0666')
	{
		GLOBAL $App;
		$this->Debug('Copy file', "src: $src<br> dst:$dst<br> mode:$mode");
		if (!$App->isWindows)
		{
			if (!self::SysExec("cp '$src' '$dst'")) return false;
			if (!$this->ChangeMode($dst, $mode)) return false;
			return true;
		}
		else
		{
			return copy($src, $dst);
		}
	}

	public function DeleteDir($dir)
	{
		$dir = self::NormalizeDirPath($dir);

		$this->Debug('Delete directory', $dir);
		
		if (!file_exists($dir)) return;
		
		$handle = @opendir($dir);
		if ($handle)
		{
			while (false !== ($file = readdir($handle)))
			{
				if ($file != '.' && $file != '..')
				{
					if (is_dir($dir.$file)) $this->DeleteDir($dir.$file.'/');
					if (is_file($dir.$file)) $this->DeleteFile($dir.$file);
				}
			}
			closedir($handle);
		}
		rmdir($dir);
	}
	
	/**
	 * Get extension of file
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function GetFileExtension($filename)
	{
		$ext = false;
		
		$arr = explode('.', basename($filename));
		if (is_array($arr) && count($arr) >= 2)
		{
			$ext = $arr[ count($arr)-1 ];
		}
		
		return $ext;
	}
	
	/**
	 * Get name of file without extension
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function GetFileNameWOExtension($filename)
	{
		$name = '';
		
		$arr = explode('.', basename($filename));
		
		if (is_array($arr) && count($arr) >= 2)
		{
			unset($arr[ count($arr)-1 ]);
		}
		$name = implode('.', $arr);
		
		return $name;
	}
	
	public function CopyDir($src, $dst, $mode='0777')
	{
		$src = self::NormalizeDirPath($src);
		$dst = self::NormalizeDirPath($dst);

		$this->Debug('Copy directory recursive', "Source folder: $src <br> Destination folder: $dst");

		if (!is_dir($src))
		{
			$this->Debug('Error', '', "Directory $src doesn't exists");
			return false;
		}

		// create destination folder if doesn't exist
		if (!is_dir($dst)) $this->CreateDir($dst, $mode);
		
		$d = dir($src);
		while (false !== ($entry = $d->read()))
		{
			if (is_dir($d->path.$entry))
			{
				if (!in_array(strtolower($entry), self::$skipFiles))
				{
					$this->CreateDir($dst.$entry.'/', $mode);
					$this->CopyDir($d->path.$entry, $dst.$entry, $mode);
				}
			}
			else
			{
				$this->CopyFile($d->path.$entry, $dst.$entry, '0666');
			}
		}
		$d->close();
		return true;
	}

	private function Debug($name, $value, $error=null)
	{
		GLOBAL $Debug, $App;
		if ($App->debug) $Debug->AddDebug('FILESYS', $name, $value, $error);
	}

	/**
	 * Check if file already exists in this directory, if so - generate new filename
	 *
	 * @param string $dir
	 * @param string $filename
	 */
	public static function MakeFilenameUnique($dir, $filename)
	{
	    $dir = self::NormalizeDirPath($dir);
		$i = 0;
	    while ($i < 1000)
	    {
	        if ($i == 0)
	        {
	            $newFilename = $filename;
	        }
	        else
	        {
	        	$reg = array();
	            if (preg_match('/(.*)\.([^\.]*)$/', $filename, $reg))
	            {
	                $newFilename = $reg[1]."[$i].".$reg[2];
	            }
	            else
	            {
	                $newFilename = $filename."[$i]";
	            }
	        }

	        if (!file_exists($dir.$newFilename)) break;
	        $i++;
	    }
	    return $newFilename;
	}
	
	/**
	 * Get directory files
	 *
	 * @param string $dir
	 * @return array
	 */
	public static function GetDirectoryFiles($dir, $recursive)
	{
		static $list;
		if (!$list || !$recursive) $list = array();
		
		if (!is_dir($dir)) return array();
		
		$d = dir($dir);
		if ($d)
		{
			while (false !== ($entry = readdir($d->handle)))
			{
				if ($entry != '.' && $entry != '..')
				{
					if ($recursive)
					{
						if (is_dir($d->path.$entry))
						{
							$list[] = $d->path.$entry;
							self::GetDirectoryFiles($d->path.$entry, true);
						}
						else
						{
							$list[] = str_replace('//', '/', $d->path.'/'.$entry);
						}
					}
					else
					{
						$list[] = $entry;
					}
				}
			}
			$d->close();
		}
		
		return $list;
	}
	
	/**
	 * Get file permissions
	 *
	 * @param string $file
	 * @return string
	 */
	public static function GetFilePermissions($file)
	{
		return substr(sprintf('%o', fileperms($file)), -4);
	}
	
	/**
	 * Check if file permissions match
	 *
	 * @param string $file
	 * @param string $compare
	 * @return boolean
	 */
    public static function CheckPermissions($file, $compare='0777')
    {
        GLOBAL $FileSystem;

        if (strtolower(substr(PHP_OS, 0, 3)) == 'win') return is_writable($file);
        else
        {
            $real = self::GetFilePermissions($file);
            return ($real == $compare);
        }
    }
	
	/**
	 * @param string $dir
	 * @return string
	 */
	public static function NormalizeDirPath($dir)
	{
		if (strlen($dir) && substr($dir, -1, 1) != '/') $dir .= '/';
		return $dir;
	}

}

$GLOBALS['FileSystem'] = new FileSystem();

?>