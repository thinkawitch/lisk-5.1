<?php
/**
 * CMS Dev Tool
 * @package lisk
 *
 */

class CMSDevTools
{
    /**
     * Check directories and its files for permissions
     *
     * @param array $dirs
     * @param string $compare
     * @return array
     */
    static function CheckPermissions($dirs, $compare='0777')
    {
        GLOBAL $App;

        $allOk = true;
		$lines = array();
		clearstatcache();
		
        foreach ($dirs as $dir)
		{
		    self::Check($App->sysRoot.$dir, $compare, $lines, 1);
		}
		
		foreach ($lines as $line)
		{
		    if ($line['status'] != 'Ok')
		    {
		        $allOk = false;
		        break;
		    }
		}
		
		return array($allOk, $lines);
    }
    
    /**
     * Check permissions of one directory
     *
     * @param string $dir
     * @param string $compare
     * @param array $list
     * @param integer $level
     */
    static function Check($dir, $compare='0777', &$list, $level)
    {
        GLOBAL $App,$FileSystem;
        
        $d = dir($dir);
		if (!$d) return;

		$filesOk = true;
		
		$prms = $FileSystem->GetFilePermissions($dir);
		$name = $dir;
		$len = strlen($App->sysRoot);
		if (substr($name, 0, $len)==$App->sysRoot) $name = substr($name,$len);
		
		$list[] = array(
            'path' => $dir,
		    'name' => str_repeat('<img src="img/0.gif" height="1" width="20" alt="" />', $level-1).$name,
            'permissions' => $prms,
            'status' => FileSystem::CheckPermissions($dir, $compare) ? 'Ok' : 'Warning',
		    'level' => $level,
        );
        $listKey = count($list)-1;
        
        $exclude = array('.', '..', '.svn');
		
		while (false!==($entry = readdir($d->handle)))
		{
		    if (in_array($entry, $exclude)) continue;
		    
            $part = str_replace('//', '/', $d->path.$entry);
            
		    if (is_dir($part))
		    {
		        self::Check($part.'/', $compare, $list, $level+1);
		    }
		    else
		    {
		        if (!FileSystem::CheckPermissions($part, $compare)) $filesOk = false;
		    }
		}
		
		$list[$listKey]['status'] = $filesOk ? 'Ok' : 'Warning';
		    
		$d->close();
    }
    
}

?>