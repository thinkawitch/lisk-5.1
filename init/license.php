<?php
/**
 * CLASS Application
 * @package lisk
 *
 */
class Application
{
	/**
	 * Lisk version
	 *
	 * @var string
	 */
	public $version = '5.1.6';

	/**
	 * Domain name the license goes to
	 *
	 * @var string
	 */
	public $licensedTo;

	public $debug;
	public $sysRoot;
	public $httpRoot;
	public $domain;

	public $cache;
	public $cachedFlag = false;

	public $sqlHost;
	public $sqlDbname;
	public $sqlUser;
	public $sqlPassword;

	public $imageLibType;
	public $imageMagickPath;

	public $isWindows;

	public $dateFormat  = 'd M Y';
	public $timeFormat  = 'H:i';
	public $timezone    = 'America/Los_Angeles';

	public $timeLimit   = 60;
	public $errorLevel  = '';

	public $initPath    = 'init/';
	public $filePath    = 'files/';
	public $cachePath	= '_cache/';
	public $backupPath  = 'backup/';
	public $publishPath = 'publish/';

	public $originalThumbnailSize = '100x100';

	public $tplPath = 'tpl/';
	public $tplExt  = 'htm';

	public $systemTplPath;

	public $startTime;	// *start time of the application's run
    public $endTime;	// *end time of the application's run

    public static $cssResources = array();
    public static $jsResources  = array();

    /**
     * How to send mails
     * instant - send instant
     * dispatcher - send via cron job
     *
     * @var string
     */
    public $mailDispatcher;

	// =========================== CONSTRUCTOR ====================================

	function __construct()
	{
		$allowedLocations = array(
			array(
				'systemRoot' => 'D:/_www/lisk-5.1/',
				'httpRoot' => '/lisk-5.1/',
			),
		);

		$this->licensedTo = 'http://lisk-cms.com/';

		$licenseIsValid = false;
		$compareRoot = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
		foreach ($allowedLocations as $location)
		{
			$systemRoot = $location['systemRoot'];
			$httpRoot	= $location['httpRoot'];

			if (substr($compareRoot, 0, strlen($systemRoot)) == $systemRoot)
			{
				$licenseIsValid = true;
				break;
			}
		}

		if (!$licenseIsValid) exit('Lisk CMS. Incorrect license file');

		//fix path if windows and cgi bin mode
		$this->sysRoot = str_replace('\\', '/', $systemRoot);
		$this->httpRoot= $httpRoot;

		$this->startTime = Utils::GetMicroTime();

		$host = $GLOBALS['host'.$GLOBALS['hostMode']];
		foreach($host as $k=>$v)
		{
			$this->$k = $v;
		}

		$this->appName = $GLOBALS['appName'];

		// arrange global TPL path
		if (ROOT_PATH != './' && ROOT_PATH != '')
		{
			$this->systemTplPath = '../'.ROOT_PATH.'tpl/system/';
		}
		else
		{
			$this->systemTplPath = 'system/';
		}

		$this->isWindows = (strstr(PHP_OS, 'WIN'));
	}

	// =========================== LOAD METHODS ====================================

	/**
	 * @desc Load module file(s) by using function loadModule().
	 * @param string $module_name - name of module
	 * @param string $module_type - type of file (def, cfg, class, func, inc, 3ger)
	 * @see loadModule()
	 */
	function Load($moduleName, $moduleType='mod')
	{
		$path = $this->sysRoot.$this->initPath;
		$loadType = 1;
		switch ($moduleType)
		{
			case 'cfg':
				$modulePath = $path.'cfg/'.$moduleName.'.cfg.php';
				$loadType = 2;
				break;

			case 'obj':
				$modulePath = $path.'obj/'.$moduleName.'.obj.php';
				$loadType = 2;
				break;

			case 'core':
				$modulePath = $path.'core/'.$moduleName.'.class.php';
				break;

			case 'class':
				$modulePath = $path.'class/'.$moduleName.'.class.php';
				$loadType = 2;
				break;

			case 'snippet':
				$modulePath = $path.'snippet/'.$moduleName.'.snippet.php';
				break;

			case 'mod':
				$modulePath = $path.'modules/'.$moduleName.'/'.$moduleName.'.mod.php';
				$loadType = 2;
				break;

			case 'parser':
				$modulePath = $path.'parser/'.$moduleName.'.pars.php';
				break;

			case 'type':
				$modulePath = $path.'type/'.$moduleName.'.type.php';
				if ($moduleName == 'image')
				{
				    if (defined('INIT_NAME') && constant('INIT_NAME') != 'cp')  $modulePath = $path.'type/image_simple.type.php';
				}
                elseif ($moduleName == 'prop_big')
                {
                    $this->Load('prop', $moduleType);
                }
				break;

			case 'utils':
				$modulePath = $path.'utils/'.$moduleName.'.class.php';
				break;

			case 'cms':
				$modulePath = $path.'cms/'.$moduleName.'.cms.php';
				break;

			case 'lang':
				$modulePath = $path.'lang/'.LANGUAGE.'/'.$moduleName.'.lang.php';
				break;
		}

		$this->__Load($modulePath, $loadType);
	}

	function LoadModule($path, $loadType=2)
	{
		$path = $this->sysRoot.$this->initPath.$path;
		$this->__Load($path, $loadType);
	}

	/**
	 * @desc Load module. This function used by load().
	 * @param string $module_name - module name
	 * @param string $module_type - type (def, cfg, class, func, inc, 3ger)
	 * @see load()
	 */
	private function __Load($modulePath, $loadType)
	{
	    //remember what files were processed
	    static $cache = array();

	    //check if already loaded
	    if (isset($cache[$modulePath])) return;

	    //try to load
		if (!include_once($modulePath))
		{
			$this->RaiseError('Can\'t load file '.$modulePath);
		}

		//globalize vars
		if ($loadType == 2)
		{
			$arr = get_defined_vars();
			unset($arr['GLOBALS']);
			unset($arr['this']);
			unset($arr['modulePath']);
			unset($arr['loadType']);
			$GLOBALS += $arr;
		}

		//mark as loaded
		$cache[$modulePath] = true;
	}

    /**
     * JS loader
     * @param string $file link on JS file
     */
    public static function LoadJs($file)
    {
        if (!in_array($file, self::$jsResources)) self::$jsResources[] = $file;
    }

    /**
     * CSS loader
     * @param string $file link on CSS file
     */
    public static function LoadCss($file)
    {
        if (!in_array($file, self::$cssResources)) self::$cssResources[] = $file;
    }


	function InstallDI($name, $arr)
	{
		GLOBAL $Db;
		$Db->Insert(array(
			'name'	=> $name,
			'data'	=> serialize($arr)
		), 'sys_di');
	}

	function UninstallDI($name)
	{
		GLOBAL $Db, $FileSystem;

		//remove files and folders
		$diConfig = $this->ReadDI($name);
		if (Utils::IsArray($diConfig))
		{
		    $types = array(LiskType::TYPE_FILE, LiskType::TYPE_IMAGE);
            foreach ($diConfig['fields'] as $field)
            {
                if ($field['type'] == LiskType::TYPE_IMAGE)
                {
                    if (strlen($field['object']['path']))
                    {
                        $path = $this->sysRoot.$this->filePath.$field['object']['path'];
                        $FileSystem->DeleteDir($path);
                    }
                }
                elseif ($field['type'] == LiskType::TYPE_FILE)
                {
                    if (strlen($field['path']))
                    {
                        $path = $this->sysRoot.$this->filePath.$field['path'];
                        $FileSystem->DeleteDir($path);
                    }
                }
            }
		}

		//remove di
		$Db->Delete("name='$name'", 'sys_di');
	}

	function SaveDI($name, $arr)
	{
		GLOBAL $Db;
		$Db->Update("name='$name'", array(
			'data'	=> serialize($arr)
		), 'sys_di');
	}

	function ReadDI($name)
	{
		GLOBAL $Db;
		$str = $Db->Get("name='$name'", 'data', 'sys_di');
		return unserialize($str);
	}

	function ReadTree($name)
	{
		if (substr($name, 0, 4) == 'dyn_') $rez = $this->ReadDI($name);
		else $rez = $GLOBALS['TREE_'.strtoupper($name)];

		if (!Utils::IsArray($rez)) $this->RaiseError('Read Tree error. Tree <b>name='.strtoupper($name).'</b> is undefined');

		return $rez;
	}

	function ReadCrossTree($name)
	{
		if (substr($name, 0, 4) == 'dyn_') $rez = $this->ReadDI($name);
		else $rez = $GLOBALS['CROSS_TREE_'.strtoupper($name)];

		if (!Utils::IsArray($rez)) $this->RaiseError('Read CrossTree error. Tree <b>name='.strtoupper($name).'</b> is undefined');

		return $rez;
	}

	function ReadCrossList($name)
	{
		if (substr($name, 0, 4) == 'dyn_') $rez = $this->ReadDI($name);
		else $rez = $GLOBALS['CROSS_LIST_'.strtoupper($name)];

		if (!Utils::IsArray($rez))
		{
		    $this->RaiseError('Read CrossList error. CrossList <b>name='.strtoupper($name).'</b> is undefined');
		}
		return $rez;
	}

	function ReadCrossTreeList($name)
	{
		if (substr($name, 0, 4) == 'dyn_') $rez = $this->ReadDI($name);
		else $rez = $GLOBALS['CROSS_TREE_LIST_'.strtoupper($name)];

		if (!Utils::IsArray($rez))
		{
		    $this->RaiseError('Read CrossTreeList error. CrossTreeList <b>name='.strtoupper($name).'</b> is undefined');
		}
		return $rez;
	}

	/**
	 * get module instance
	 *
	 * @param integer $instanceId
	 * @return LiskModule
	 */
	public function GetModuleInstance($instanceId)
	{
		GLOBAL $Db;
		$moduleInfo = $Db->Get("id=$instanceId", 'object_name,name', 'sys_modules');
		if (!Utils::IsArray($moduleInfo))
		{
		    $this->RaiseError('Can\'t get module instance iid='.$instanceId);
		}
		$this->Load($moduleInfo['name'], 'mod');
		return new $moduleInfo['object_name']($instanceId);
	}

	/**
	 * @desc Raise LISK internal error. Application die.
	 * @param string $error - error message
	 */
	function RaiseError($error)
	{
		$e = new LiskException($error);
	    $e->ShowError();
		$this->Destroy();
	}

	/**
	 * @param array|string $error - error message string or array of errors
	 * @desc Set error, that will be parsed next time app->output is called
	 */
	function SetError($error)
	{
		//TODO, should separate page errors and application level errors
		//App::SetError  and Page::SetError() - are different types of errros

		if (!is_array($error)) $_SESSION['SYS_app_notifications']['errors'][] = $error;
		else $_SESSION['SYS_app_notifications']['errors'] += $error;
	}

	/**
	 * @desc Desctructor - Update statistics, free resources.
	 */
	function Destroy()
	{
	    if (defined('LISK_PROFILER') && LISK_PROFILER === true)
		{
			GLOBAL $Profiler;
			$Profiler->Process();
		}
		exit();
	}

}

$App = new Application();

// set php settings
date_default_timezone_set($App->timezone);
error_reporting($App->errorLevel);
set_time_limit($App->timeLimit);
session_name('lisk');
session_start();

// force debug
if (isset($_SESSION['force_debug']) && $_SESSION['force_debug'] == 1) $App->debug = true;

/**************************** LOAD APP ENGINE **************************************/

$App->Load('debug', 'core');
$App->Load('liskException', 'core');

$App->Load('navigation', 'utils');

$App->Load('db', 'core');
$App->Load('stat_action_handler', 'utils');
$App->Load('format', 'utils');
$App->Load('auth', 'cfg');
$App->Load('auth', 'core');

$App->Load('page', 'core');
$App->Load('snippet', 'core');

// Load System Base
$App->Load('lisk', 'type');
$App->LoadModule('modules/lisk.mod.php', 1);

// Load Utils
$App->Load('paging', 'utils');
$App->Load('filesystem', 'utils');

// Load Configuarations
$App->Load('default', 'cfg');

// Load classes
$App->Load('tpl', 'core');
$App->Load('parser', 'core');
$App->Load('data', 'core');
$App->Load('cache', 'core');

// Load functions
$App->Load('system', 'snippet');

$App->Load('scms', 'class');

?>
