<?php

$GLOBALS['METATAGS_MANAGER_MODULE_INFO'] = array(
	'name'			=> 'Metatags Manager',
	'sys_name'		=> LiskModule::MODULE_METATAGS_MANAGER,
	'version'		=> '5.0',
	'description'	=> 'Full control on site meta information',
	'object_name'	=> 'MetatagsManager',
	'multiinstance'	=> false,
	'ss_integrated'	=> false
);


$GLOBALS['DATA_METATAGS_PRESET'] = array(
	'table'	=> 'mod_metatags_presets',
	'order'	=> 'oder',
	'fields'	=> array(
		'id'			=> LiskType::TYPE_HIDDEN,
		'oder'			=> LiskType::TYPE_HIDDEN,
		'def'			=> LiskType::TYPE_HIDDEN,
		'name'			=> array(
			'type'			=> LiskType::TYPE_INPUT,
			'label'			=> 'Preset Name'
		),
		'title'			=> LiskType::TYPE_INPUT,
		'description'	=> LiskType::TYPE_TEXT,
		'keywords'		=> LiskType::TYPE_TEXT,
		'revisit_after'	=> LiskType::TYPE_INPUT,
		'robots'		=> LiskType::TYPE_INPUT,
		'language' 		=> LiskType::TYPE_INPUT,
		'classification'=> LiskType::TYPE_INPUT,
		'page_type'		=> LiskType::TYPE_INPUT,
		'page_topic'	=> LiskType::TYPE_INPUT,
		'copyright'		=> LiskType::TYPE_INPUT,
		'author'		=> LiskType::TYPE_INPUT,
		'url'			=> LiskType::TYPE_INPUT,
	)
);

$GLOBALS['DATA_METATAGS_PAGE'] = array (
	'table'		=> 'mod_metatags_pages',
	'order'		=> 'name',
	'fields'	=> array (
		'id'			=> LiskType::TYPE_HIDDEN,
		'name'			=> array(
			'type'			=> LiskType::TYPE_INPUT,
			'label'			=> 'Page Url'
		),
		'title'			=> LiskType::TYPE_INPUT,
		'description'	=> LiskType::TYPE_TEXT,
		'keywords'		=> LiskType::TYPE_TEXT,
		'revisit_after'	=> LiskType::TYPE_INPUT,
		'robots'		=> LiskType::TYPE_INPUT,
		'language' 		=> LiskType::TYPE_INPUT,
		'classification'=> LiskType::TYPE_INPUT,
		'page_type'		=> LiskType::TYPE_INPUT,
		'page_topic'	=> LiskType::TYPE_INPUT,
		'copyright'		=> LiskType::TYPE_INPUT,
		'author'		=> LiskType::TYPE_INPUT,
		'url'			=> LiskType::TYPE_INPUT,
	)
);

class MetatagsManager extends LiskModule
{

	/**
	 * Metatags info for current requested page
	 *
	 * @var Hashtable
	 */
	public $metaInfo = array();
	
	/**
	 * Add meta not to index this url
	 * @var boolean
	 */
	private static $flagNoIndex = false;

	/**
	 * constructor
	 *
	 * @return Statistics
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_METATAGS_MANAGER;

		if ($instanceId!=null) $this->Init($instanceId);
	}
	
	static function SetFlagNoIndex($flag)
	{
	    self::$flagNoIndex = $flag;
	}

	function InitMetainfo()
	{
		GLOBAL $App;

		$pregEscaped = preg_quote($App->httpRoot, '/');
		
		$metatags = Data::Create('metatags_page');
		$metaInfo = null;

		//1st try - regular url
		if (strlen($_SERVER['QUERY_STRING']))
		{
		    $requestUrl = preg_replace("/^$pregEscaped(.*)/", "\\1", $_SERVER['REQUEST_URI'].'?'.urldecode($_SERVER['QUERY_STRING']));
		    if (substr($requestUrl,0,1) != '/') $requestUrl = '/'.$requestUrl;
		    
		    $metaInfo = $metatags->GetValue("name='$requestUrl'");
		}
        
		//2nd try - rewrite url
		if (!Utils::IsArray($metaInfo))
		{
    		$requestUrl = preg_replace("/^$pregEscaped(.*)/", "\\1", $_SERVER['REQUEST_URI']);
    		if (substr($requestUrl,0,1) != '/') $requestUrl = '/'.$requestUrl;
    		
    		$metaInfo = $metatags->GetValue("name='$requestUrl'");
    		
    		//try with ending slash
    		if (!Utils::IsArray($metaInfo) && substr($requestUrl, -1, 1) != '/')
    		{
    		    $requestUrl .= '/';
    		    $metaInfo = $metatags->GetValue("name='$requestUrl'");
    		}
		}
        
		//3rd try - default preset
		if (!Utils::IsArray($metaInfo))
		{
			$preset = Data::Create('metatags_preset');
			$metaInfo = $preset->GetValue('id=1');
		}
		
		$this->metaInfo = $metaInfo;
	}

	/**
	 * Render metatags HTML
	 *
	 * @return HTML
	 */
	function Render()
	{
		return $this->FormatMetatags();
	}

	function GetTitle()
	{
		return $this->metaInfo['title'];
	}

	/**
	 * Returns html code with metainfo from
	 * this->metaInfo
	 *
	 * @return string
	 */
	private function FormatMetatags()
	{
		$metatags = $this->metaInfo;
        $rez = '';
		/*if ($metatags[title]!='') {
			$rez.='<title>'.$metatags['title'].'</title>'."\n";
		}*/
        
		if ($metatags['description']!='')
		{
			$rez .= '<meta name="description" content="'.$metatags['description'].'">'."\n";
		}

		if ($metatags['keywords']!='')
		{
			$rez .= '<meta name="keywords" content="'.$metatags['keywords'].'">'."\n";
		}

		if ($metatags['revisit_after']!='')
		{
			$rez .= '<meta name="revisit-after" content="'.$metatags['revisit_after'].'">'."\n";
		}

		//custom
		if (self::$flagNoIndex)
		{
		    $rez .= '<meta name="robots" content="NOINDEX, NOFOLLOW">'."\n";
		}
		else
		{
    		if ($metatags['robots'] != '')
    		{
    			$rez .= '<meta name="robots" content="'.$metatags['robots'].'">'."\n";
    		}
		}

		if ($metatags['language']!='')
		{
			$rez .= '<meta name="Language" content="'.$metatags['language'].'">'."\n";
		}

		if ($metatags['classification']!='')
		{
			$rez .= '<meta name="classification" content="'.$metatags['classification'].'">'."\n";
		}

		if ($metatags['page_type']!='')
		{
			$rez .= '<meta name="page-type" content="'.$metatags['page_type'].'">'."\n";
		}

		if ($metatags['page_topic']!='')
		{
			$rez .= '<meta name="page-topic" content="'.$metatags['page_topic'].'">'."\n";
		}

		if ($metatags['copyright']!='')
		{
			$rez .= '<meta name="copyright" content="'.$metatags['copyright'].'">'."\n";
		}

		if ($metatags['author']!='')
		{
			$rez .= '<meta name="author" content="'.$metatags['author'].'">'."\n";
		}

		if ($metatags['url']!='')
		{
			$rez .= '<meta name="URL" content="'.$metatags['url'].'">';
		}

		return $rez;

	}

	/**
	 * Init module instance by IID
	 *
	 * @param integer $instanceId
	 */
	public function Init($instanceId)
	{
		parent::Init($instanceId);
		$this->version = $GLOBALS['METATAGS_MANAGER_MODULE_INFO']['version'];
	}

	/**
	 * Save module settings
	 *
	 */
	public function SaveSettings()
	{
	    
	}

	function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/metatags_manager/metatags_manager.install.mod.php', 1);
		installMetatagsManagerModule();
	}

	function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/metatags_manager/metatags_manager.install.mod.php', 1);
		uninstallMetatagsManagerModule();
		parent::Uninstall();
	}

	public function AvailableSnippets()
	{
		return array(
			'metatags_manager'	=> array(
				'description'		=> 'Main snippet that should be inserted into all site pages',
				'code'				=> '<lisk:snippet src="metatags_manager" name="metatags_manager" />'
			),
		);
	}
}

?>