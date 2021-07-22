<?php
require_once('init/init.php');

class CpBackupPage extends CPPage
{
	/**
	 * backup directory full path
	 *
	 * @var string
	 */
	private $path;
	
	/**
	 * project "files" directory name
	 *
	 * @var string
	 */
	private $filesDirName;
	
	/**
	 * "backup" directory name
	 *
	 * @var string
	 */
	private $backupDirName;
	
	/**
	 * "publish" directory name
	 *
	 * @var string
	 */
	private $publishDirName;
	
	/**
	 * if backup enabled
	 *
	 * @var boolean
	 */
	private $isEnabled = true;
	
	private $selfUri = 'backup.php?z=x';

	function __construct()
	{
		parent::__construct();
		
		$this->App->Load('backup', 'lang');
        $this->App->Load('backup', 'obj');
        $this->App->Load('zip', 'utils');
		
		$this->path = $this->App->sysRoot.$this->App->backupPath;
		$this->filesDirName = substr($this->App->filePath,-1,1)=='/' ? substr($this->App->filePath,0,-1) : $this->App->filePath;
		$this->backupDirName = substr($this->App->backupPath,-1,1)=='/' ? substr($this->App->backupPath,0,-1) : $this->App->backupPath;
		$this->publishDirName = substr($this->App->publishPath,-1,1)=='/' ? substr($this->App->publishPath,0,-1) : $this->App->publishPath;

		$this->AddBookmark(LANG_BACKUP_BACKUP, $this->selfUri.'&action=default', 'img/cms/backup/backup.gif');
		$this->AddBookmark(LANG_BACKUP_RESTORE, $this->selfUri.'&action=custom', 'img/cms/backup/restore.gif');

		$this->SetGetAction('custom', 'InstallCustom');
		$this->SetGetPostAction('custom', 'submit', 'InstallCustomSubmit');

		$this->SetGetAction('submit', 'Backup');
		$this->SetGetAction('install', 'Install');
		$this->SetGetAction('delete', 'Delete');
	}

	function Page()
	{
		GLOBAL $Parser;
		
		$this->SetBack();
		
		$this->currentBookmark = LANG_BACKUP_BACKUP;
		$this->SetTitle(LANG_BACKUP_BACKUP, 'cms/backup/uho.gif');

		$di = Data::Create('backup_options');
		$this->pageContent .= $Parser->MakeView(array(
			'form' => $Parser->MakeDynamicForm($di, 'cms/backup/backup', 'form'),
			'list' => $this->RenderBackupFilesList('backup')
		), 'cms/backup/backup', 'default');
	}

	function Backup()
	{
		GLOBAL $App, $FileSystem;
		
		$doFiles = isset($_GET['option']) && in_array(BACKUP_FILES, $_GET['option']) ? true : false;
		$doDb = isset($_GET['option']) && in_array(BACKUP_DB, $_GET['option']) ? true : false;
		$doSource = isset($_GET['option']) && in_array(BACKUP_SOURCE, $_GET['option']) ? true : false;

		$archivename = $this->path . date('Y_m_d_h_i_s_') . $doFiles . $doDb . $doSource . '.zip';
		$filelist = array ();

		// return if no options selected
		if (!$doFiles && !$doDb && !$doSource) Navigation::Jump($this->selfUri);
        
		// do nothing if disabled
		if (!$this->isEnabled) Navigation::Jump($this->selfUri);
		
		//files directory
		if ($doFiles) $filelist[] = $App->sysRoot . $this->filesDirName;
		
		//other files, except "files" and "backup"
		if ($doSource)
		{
			if (false!==($handle = opendir($App->sysRoot)))
			{
				while (false !== ($file = readdir($handle)))
				{
					if ($file!='.' && $file!='..'
					    && $file!=$this->backupDirName && $file!=$this->filesDirName
					    && $file!=$this->publishDirName)
					{
						$filelist[] = $App->sysRoot.$file;
					}
				}
				closedir($handle);
			}
		}
		
		//database
		if ($doDb)
		{
			$dumpFilename = $this->path.'dump.sql';
			$App->Load('mysqldump', 'utils');
			
			$dump = new MysqlDump();
			$dump->dbHost = $App->sqlHost;
			$dump->dbUser = $App->sqlUser;
			$dump->dbPass = $App->sqlPassword;
			$dump->dbName = $App->sqlDbname;
			
			$dump->Backup($dumpFilename);

			$filelist[] = $dumpFilename;
		}
		

		error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
		Utils::FreezeMBEncoding();
		$zip = new Archive_Zip($archivename);
		$zip->Create($filelist, array ('remove_path' => $App->sysRoot));
		Utils::UnfreezeMBEncoding();
		error_reporting($App->errorLevel);
       
		if ($doDb && isset($dumpFilename)) $FileSystem->DeleteFile($dumpFilename);
		
		//save action to db
		$di = Data::Create('backup');
		$di->Insert(array(
	        'date' => date('Y-m-d H:i:s'),
	    	'filename' => basename($archivename),
	        'description' => $_GET['description'],
	        'type' => BACKUP_TYPE_BACKUP
		));

		Navigation::JumpBack();
	}

	function Install()
	{
		GLOBAL $App, $FileSystem;
		
		if (!$this->isEnabled) Navigation::Jump($this->selfUri);

		$archivename = $this->path.$_GET['filename'];
		
        Utils::FreezeMBEncoding();
        $zip = new Archive_Zip($archivename);
        $zip->extract(array (
        	'add_path'	=> $App->sysRoot,
        	'by_preg'	=> '/^('.$this->backupDirName.'|'.$this->filesDirName.')\/.*$/' // restore only db dump and files!!! all source better restore manually
        ));
        Utils::UnfreezeMBEncoding();
        
        $filename = $this->path . 'dump.sql';
        if (file_exists($filename))
        {
            $App->Load('mysqldump', 'utils');
        	
            $dump = new MysqlDump();
			$dump->dbHost = $App->sqlHost;
			$dump->dbUser = $App->sqlUser;
			$dump->dbPass = $App->sqlPassword;
			$dump->dbName = $App->sqlDbname;
			
			$dump->Restore($filename);
			
			$FileSystem->DeleteFile($filename);
        }

		Navigation::JumpBack();
	}

	function InstallCustom()
	{
		GLOBAL $Parser;

		$this->SetBack();
		$this->currentBookmark = LANG_BACKUP_RESTORE;

		$this->SetTitle(LANG_BACKUP_RESTORE, 'cms/backup/uho.gif');

		$di = Data::Create('install');
		$this->pageContent .= $Parser->MakeView(array (
			'form' => $Parser->MakeForm($di, 'cms/backup/backup', 'form2'),
			'list' => $this->RenderBackupFilesList('restore')
		), 'cms/backup/backup', 'default');
	}

	function InstallCustomSubmit()
	{
		GLOBAL $App, $FileSystem;
		
		$error = $_FILES['file']['error'];
		$name = $_FILES['file']['name'];
		$tmpName = $_FILES['file']['tmp_name'];
		
		if (!$this->isEnabled || $error) Navigation::Jump($this->selfUri);
		
		if (substr($name, -3) == 'zip')
		{
			$FileSystem->CopyFile($tmpName, $this->path.$name);
		}
		elseif (substr($name, -3) == 'sql')
		{
			$App->Load('mysqldump', 'utils');
        	
            $dump = new MysqlDump();
			$dump->dbHost = $App->sqlHost;
			$dump->dbUser = $App->sqlUser;
			$dump->dbPass = $App->sqlPassword;
			$dump->dbName = $App->sqlDbname;
			
			$dump->Restore($tmpName);
			
			$FileSystem->DeleteFile($tmpName);
		}
		
		Navigation::JumpBack();
	}

	function Delete()
	{
		GLOBAL $FileSystem;
		$filename = trim($_GET['filename']);
		$FileSystem->DeleteFile($this->path.$filename);
		
		//remove from backup table
		$di = Data::Create('backup');
		$di->Delete('filename='.Database::Escape($filename).' AND type='.BACKUP_TYPE_BACKUP);
		
		Navigation::JumpBack();
	}

	function RenderBackupFilesList($blockName)
	{
		GLOBAL $LIST_BACKUP, $Parser;

		$result = array();
		
		$exclude = array('.', '..', '.htaccess', '.svn');

		if (false !== ($handle = opendir($this->path)))
		{
			while (false !== ($file = readdir($handle)))
			{
				if (!in_array($file, $exclude))
				{
					$filename = $this->path . $file;
					$options = substr($filename, -7, 3);
					$result1 = array ();
					if ((int)($options{0})) $result1[] = $LIST_BACKUP[BACKUP_FILES];
					if ((int)($options{1})) $result1[] = $LIST_BACKUP[BACKUP_DB];
					if ((int)($options{2})) $result1[] = $LIST_BACKUP[BACKUP_SOURCE];
					$result[] = array(
						'date' => date ('F d Y H:i:s', filemtime($filename)),
						'options' => implode(' / ', $result1),
						'filename' => $file,
						'size' => Format::FileSize(filesize($filename)),
					);
				}
			}
			closedir($handle);
		}

		function cmp($a, $b)
		{
			return strcmp($b['date'], $a['date']);
		}

		usort($result, 'cmp');

		$Parser->SetListDecoration('ListTD1', 'ListTD2');
		return $Parser->MakeList($result, 'cms/backup/list', $blockName);

	}

}

$Page = new CpBackupPage();
$Page->Render();
?>