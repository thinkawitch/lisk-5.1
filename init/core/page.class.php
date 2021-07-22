<?php
/**
 * CLASS Page
 * @package lisk
 *
 */
class Page
{
	/**
	 * Global template file name
	 *
	 * @var string
	 */
	protected $globalTemplate = 'global';

	/**
	 * template
	 *
	 * @var Template
	 */
	protected $Tpl;
	
	/**
	 * database
	 *
	 * @var Database
	 */
	protected $Db;
	
	/**
	 * application
	 *
	 * @var Application
	 */
	protected $App;
	
	/**
	 * parser
	 *
	 * @var Parser
	 */
	protected $Parser;
	
	/**
	 * paging
	 *
	 * @var Paging
	 */
	protected $Paging;
	
	/**
	 * authorization
	 *
	 * @var Authorization
	 */
	protected $Auth;

	/**
	 * @var Cache
	 */
	public $Cache;
	
	protected $handlers = array(
		1	=> array(),	// system action GET[action] || POST[action]
		2	=> array(),	// Custom GET/POST action WITHOUT value i.e. null
		3	=> array()	// Custom GET/POST action WITH value
	);

	private $method = 'Page';

	/**
	 * Page Title. Parsed in Page->Output
	 *
	 * @var string
	 */
	public $title;

	/**
	 * parameters from url formed by Static Url modules
	 *
	 * @var array
	 */
	public $parameters	= array();

	/**
	 * @var array
	 */
	protected $globalVariables = array();
	
	/**
	 * additional css files
	 *
	 * @var array
	 */
	protected $cssFiles = array();
	
	/**
	 * additional js files
	 *
	 * @var array
	 */
	protected $jsFiles = array();
	
	
	protected $notifications;

	/**
	* Constructor
	*/
	function __construct()
	{
		GLOBAL $Tpl, $Db, $App, $Parser, $Paging, $Auth, $Debug, $Cache;
		
		$this->Tpl = $Tpl;
		$this->Db = $Db;
		$this->App = $App;
		$this->Parser = $Parser;
		$this->Paging = $Paging;
		$this->Auth = $Auth;
		$this->Cache = $Cache;
		
		// strip slashes from GET, POST, SSESION variables
		$this->StripRequestVars();
		
		$this->InitParameters();

		// Debug Page Parameters
		if ($App->debug) $Debug->AddDebug('GENERAL', 'Parameters', $this->parameters, null);
		
		$this->InitNotifications();
	}


	/**
	 * Taking Get variables from url method.
	 * Should be redeclarated for custom use
	 *
	 */
	protected function InitParameters()
	{
		if (isset($GLOBALS['parameters'])) $this->parameters = $GLOBALS['parameters'];
		
	    $firstParameter = $this->GetParameter(0);
	    $id = isset($_GET['id']) ? $_GET['id'] : null;
	    $action = isset($_GET['action']) ? $_GET['action'] : null;
	    
		// if 1st parametr is number - it's ID
		// else it's action
		if (is_numeric($firstParameter) && !strlen($id))
		{
			$_GET['id'] = $firstParameter;
		}
		elseif (strlen($firstParameter) && !strlen($action))
		{
			$_GET['action'] = $firstParameter;
		}
		return;
	}
	
    protected function GetParameter($idx)
	{
	    return isset($this->parameters[$idx]) ? $this->parameters[$idx] : null;
	}
	
	private function InitNotifications()
	{
		if (!isset($_SESSION['SYS_app_notifications'])) $_SESSION['SYS_app_notifications'] = array();
		
		$this->notifications =& $_SESSION['SYS_app_notifications'];
		
		if (!Utils::IsArray($this->notifications))
		{
			$this->notifications = array(
				'errors' => array(),
				'notifications' => array(),
				'growls' => array(),
			);
		}
	}
	

	/**
	 * Add global variables to parse in global template
	 *
	 * @param array $array
	 */
	public function SetGlobalVariable(array $array)
	{
		$this->globalVariables = Utils::MergeArrays($this->globalVariables, $array);
	}

	/**
	 * @desc Set Global Template name
	 * @param string new global template name
	 *
	 */
	public function SetGlobalTemplate($name)
	{
		GLOBAL $App;
		if ($name == '0' || $name == '')
		{
			$name = $App->systemTplPath.$name;
		}
		$this->globalTemplate = $name;
	}

	/**
	 * Load page template
	 *
	 * @param string/booleas $tpl_name
	 * @param boolean $removeUnknownVariables
	 * @param boolean $removeEmptyBlocks
	 */
	public function LoadTemplate($tpl_name=true, $removeUnknownVariables=true, $removeEmptyBlocks=true)
	{
		$this->Tpl->load($tpl_name, $removeUnknownVariables, $removeEmptyBlocks);
	}

	/**
	 * Tpl->SetVariable
	 *
	 * @param string/array $placeholder
	 * @param string/object $variable
	 */
	public function SetVariable($placeholder, $variable='')
	{
		$this->Tpl->SetVariable($placeholder, $variable);
	}

	/**
	 * @param array|string $error - error message string or array of errors
	 * @desc Set error, that will be parsed next time app->output is called
	 */
	public function SetError($error)
	{
		if (!is_array($error)) $this->notifications['errors'][] = $error;
		else $this->notifications['errors'] = Utils::MergeArrays($this->notifications['errors'], $error);
	}
	
    public function SetNotification($message)
	{
		if (!is_array($message)) $this->notifications['notifications'][] = $message;
		else $this->notifications['notifications'] = Utils::MergeArrays($this->notifications['notifications'], $message);
	}
    	
	public function SetGrowl($growl)
	{
	    if (!is_array($growl)) $this->notifications['growls'][] = $growl;
		else $this->notifications['growls'] = Utils::MergeArrays($this->notifications['growls'], $growl);
	}
    	
	/**
	 * @desc Otput - finalize app and  make page output
	 */
	public function Output()
	{
		GLOBAL $Db,$Tpl,$App,$Debug;

		$page = $this->Cache->Render();
		
		if ($page == '' || !$App->cache)
		{
			$page = $Tpl->Get();
		}
		else
		{
			echo $page;
			$App->Destroy();
		}
		
		unset($Tpl->blocklist['__global__']);
		$Tpl->Free();
		$Tpl->LoadTemplatefile($this->globalTemplate, true, true);

		$this->title = !strlen($this->title) ? DEFAULT_TITLE : $this->title;

		// Metatags, define constant not to call excessive query
		if (defined('MODULE_METATAGS_INSTALLED')) $useMetatags = constant('MODULE_METATAGS_INSTALLED');
		else $useMetatags = $Db->Get("name='metatags_manager'", 'id', 'sys_modules') !== false;
		
		if ($useMetatags)
		{
			$App->Load('metatags_manager', 'mod');
			$Metatags = new MetatagsManager();
			$Metatags->InitMetainfo();
			if (strlen($Metatags->GetTitle()))
			{
				$this->title = $Metatags->GetTitle();
			}

			$Tpl->SetVariable(array(
				'metatags'	=> $Metatags->Render()
			));
		}

		$Tpl->SetVariable(array(
		    'title'	=> $this->title,
			'page' 	=> $page,
		));

		// SetGlobal Variables
		$Tpl->SetVariable($this->globalVariables);

		//errors
		$errorBlock = $this->RenderError();
		
		//notifications
		$errorBlock .= $this->RenderNotification();
		
		//growls
		$errorBlock .= $this->RenderGrowl();
		
		
		//set base href if static urls
		if (defined('STATIC_URLS') && STATIC_URLS==true)
		{
			$Tpl->SetVariable(array(
				'BASE_HREF'	=> '<base href="' . self::GetBaseUrl() . '" />',
			));
		}

		// execute block functions
		$page = $this->ExecuteBlockFunctions($Tpl->get());
		
		//additional resources
        $page = $this->LinkAdditionalResources($page);

		//Save to cache
		if ($this->Cache->cache_filepath != '' && ($this->Cache->AutoCache || $this->Cache->ManualCache) && $App->cache)
		{
			$page = $Tpl->ExecLiskSnippet($page);
			$this->Cache->SaveToCache($page);
		}
		
		$App->cachedFlag = true;
		$page = $Tpl->ExecLiskSnippet($page);
		
    	//Insert Lisk Debug && Insert Lisk Error
		if ($App->debug) $page = str_replace('<!-- LISK_DEBUG -->', $Debug->Render(), $page);
		if ($errorBlock != '') $page = str_replace('<!-- LISK_ERROR -->', $errorBlock, $page);
		$page = str_replace('<!-- LISK_FOOTER -->', $this->RenderSystemFooter(), $page);
		
		// replace  all short markers
		$page = $this->ReplaceShortMarkers($page);
		
		// show page
		$Tpl->Show($page);
		// destroy application
		$App->Destroy();
	}
	
	protected function RenderSystemFooter()
	{
	    $items = $this->Db->Select('TRIM(content)!=""', null, null, 'sys_footer');
	    if (Utils::IsArray($items))
	    {
	        $footer = '';
	        foreach ($items as $item)
	        {
	            $footer .= "\r\n".$item['content'];
	        }
	        return $footer;
	    }
	}
	
	/**
	 * Replace all short markers in rendered page
	 *
	 * @param string $page
	 * @return string
	 */
	protected function ReplaceShortMarkers($page)
	{
	    GLOBAL $App;
	    $path = isset($GLOBALS['path']) ? $GLOBALS['path'] : '';
	    
	    // [/] replace
		$page = str_replace('[/]', $App->httpRoot, $page);
		// [./] replace
		$page = str_replace('[./]', $_SERVER['REQUEST_URI'], $page);
		// [//] replace
		$page = str_replace('[//]', self::GetBaseUrl(), $page);
		// [path] replace
		return str_replace('[path]', $path, $page);
	}
	
	/**
	 * link additional js and css resources to page
	 *
	 * @param string $page
	 */
	protected function LinkAdditionalResources(&$page)
	{
	    $resources = '';
	    
	    foreach (Application::$jsResources as $jsResource)
	    {
	        $resources .= '<script src="'.$jsResource.'" type="text/javascript"></script>';
	    }
	    
	    foreach (Application::$cssResources as $cssResource)
	    {
	        $resources .= '<link href="'.$cssResource.'" rel="stylesheet" type="text/css" />';
	    }
	    
	    return str_replace('<!-- LISK_ADDITIONAL_RESOURCES -->', $resources, $page);
	}
	
	function Process()
	{
        $found = false;
		// check GET-POST handlers
		if (isset($this->handlers[0]) && Utils::IsArray($this->handlers[0]))
		{
			foreach ($this->handlers[0] as $row)
			{
				$rez = $this->CheckGetPostHandler($row);
				if ($rez != false && !$found)
				{
					$found = true;
					$methodName = $rez;
				}
			}
		}

		// Find current handler
		for ($i=1; $i<=3; $i++)
		{
			if (Utils::IsArray($this->handlers[4-$i]) && !$found)
			{
				foreach ($this->handlers[4-$i] as $row)
				{
					$rez = $this->CheckHandler($row);
					if ($rez!=false && !$found)
					{
						$found=true;
						$methodName=$rez;
					}
				}
			}
		}

		if ($found) $this->method = $methodName;

		if (method_exists($this, $this->method)) $this->{$this->method}();
		else $this->App->RaiseError("Method '{$this->method}' not found!");

		$this->Output();
	}
	
    public static function GetBaseUrl()
	{
		GLOBAL $App;
		$proto = 'http://';
		if (isset($_SERVER['HTTPS'])) $proto = 'https://';
		
		return $proto . $_SERVER['HTTP_HOST'] . $App->httpRoot;
	}
	
    public function GetPageUrl()
	{
	    GLOBAL $App;
	    $url = '';
	    
	    // if scms page
	    if (isset($this->curPage) && isset($this->curPage['url']))
	    {
	        $url = $this->curPage['url'];
	    }
	    else
	    {
	        // index or php file page
    	    if (strtolower(basename($_SERVER['PHP_SELF'])) == 'index.php') $url = 'index/';
    	    if (Utils::IsArray($this->parameters))
    	    {
    	        $url .= implode('/', $this->parameters).'/';
    	    }
	    }
	    
	    return $App->httpRoot.$url;
	}

	/**
	 * Render Page
	 *
	 */
	public function Render()
	{
		$this->Process();
	}

	public function SetGetAction($value, $name)
	{
		$this->handlers[1][] = array(
			'type'		=> 'get',
			'value'		=> $value,
			'method'	=> $name,
			'var_name'	=> 'action'
		);
	}

	public function SetPostAction($value, $name)
	{
		$this->handlers[1][] = array(
			'type'		=> 'post',
			'value'		=> $value,
			'method'	=> $name,
			'var_name'	=> 'action'
		);
	}

	public function AddGetHandler($varName, $methodName, $value=null)
	{
		$area = ($value == null) ? 2 : 3;
		$this->handlers[$area][] = array(
			'type'		=> 'get',
			'value'		=> $value,
			'method'	=> $methodName,
			'var_name'	=> $varName
		);
	}

	public function AddPostHandler($varName, $methodName, $value=null)
	{
		$area = ($value == null) ? 2 : 3;
		$this->handlers[$area][] = array(
			'type'		=> 'post',
			'value'		=> $value,
			'method'	=> $methodName,
			'var_name'	=> $varName
		);
	}

	public function SetGetPostAction($getValue, $postValue, $name)
	{
		$this->AddGetPostHandler('action', $getValue, 'action', $postValue, $name);
	}

	public function AddGetPostHandler($getVar, $getValue, $postVar, $postValue, $methodName)
	{
		$this->handlers[0][] = array(
			'type'		=> 'get_post',
			'get_var'	=> $getVar,
			'get_value'	=> $getValue,
			'post_var'	=> $postVar,
			'post_value'=> $postValue,
			'method'	=> $methodName
		);
	}

	private function CheckHandler($handler)
	{

		if ($handler['type'] == 'get') $var = @$_GET[$handler['var_name']];
		else $var = @$_POST[$handler['var_name']];

		if ($handler['value'] != null)
		{
			// check value
			if ($var == $handler['value']) return $handler['method'];
		}
		else
		{
			// check not empty
			if (strlen($var)) return $handler['method'];
		}

		return false;
	}

	private function CheckGetPostHandler($handler)
	{
		$getVar = @$_GET[$handler['get_var']];
		$postVar = @$_POST[$handler['post_var']];

		if ($getVar == $handler['get_value'] && $postVar == $handler['post_value'])
		{
			return $handler['method'];
		}

		return false;
	}

	/**
	 * Render error messages for the page
	 */
	public function RenderError()
	{
		GLOBAL $App, $Parser;
		
		$errors = $this->notifications['errors'];
		
		if (!Utils::IsArray($errors)) return '';
	
		$list = array();
		foreach ($errors as $message)
		{
			array_push($list, array('error_message' => addcslashes($message, "'")));
		}
		$html = $Parser->MakeList($list, $App->systemTplPath.'error', 'error');
	
		//clean
		$this->notifications['errors'] = array();
		
		return $html;
	}
	
	/**
	 * Render notifications for page
	 */
	public function RenderNotification()
	{
	    GLOBAL $App, $Parser;
	    
	    $notifications = $this->notifications['notifications'];
		
		if (!Utils::IsArray($notifications)) return '';
	
		$list = array();
		foreach ($notifications as $message)
		{
			array_push($list, array('notify_message' => addcslashes($message, "'")));
		}

		$html = $Parser->MakeList($list, $App->systemTplPath.'error', 'notify');
		
		//clean
		$this->notifications['notifications'] = array();
		
		return $html;
	}
	
	/**
	 * Render growl messages for page
	 */
	public function RenderGrowl()
	{
	    GLOBAL $App, $Parser;
	    
		$growls = $this->notifications['growls'];
		
		if (!Utils::IsArray($growls)) return '';
		
		Application::LoadJs('[/]js/jquery/jquery.jgrowl.js');
		
		$list = array();
		foreach ($growls as $message)
		{
			array_push($list, array('growl_message' => addcslashes($message, "'")));
		}

		$html = $Parser->MakeList($list, $App->systemTplPath.'error', 'growl');
		
		//clean
		$this->notifications['growls'] = array();
		
		return $html;
	}

	private function StripRequestVars()
	{
		if (Utils::IsArray($_POST)) $_POST = Utils::StripSlashes($_POST);
		if (Utils::IsArray($_GET)) $_GET = Utils::StripSlashes($_GET);
		if (Utils::IsArray($_SESSION)) $_SESSION = Utils::StripSlashes($_SESSION);
	}

	/**
	 * @desc Blocks - execute block functions
	 * @param string $page - page html code
	 * @returns string new html code with results of block functions
	*/
	protected function ExecuteBlockFunctions($page)
	{
		// <<<MENU>>>
		$regs = array();
		preg_match_all('/<<<([1234567890A-Z\/\|?\_]+?)>>>/ms', $page, $regs);

		$blocks = array();
		if (0 != count($regs[1]))
		{
	        foreach ($regs[1] as $k => $var)
	        {
				$par = '';
				$var_old = $var;
				$fqs = strpos($var,'?');
				if ($fqs !== false)
				{
					$var = substr($var, 0, $fqs);
					$par = substr($var_old, $fqs + 1);
				}
				$var = strtolower($var);
				$blocks[$regs[0][$k]] = $var($par);
			}
		}

		foreach ($blocks as $k=>$var)
		{
			$page = str_replace($k, $var, $page);
		}
		
		return $page;
	}
}

?>