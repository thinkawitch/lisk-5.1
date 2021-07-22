<?php
/**
 * CLASS Parser
 * @package lisk
 *
 */
class Parser
{
	/**
	 * Current tpl name used with Parser
	 * built in Template object instance
	 *
	 * @var string
	 */
	private $curTplName;
		
	public $listDecoration1	= '';		// * list decoration 1
	public $listDecoration2	= '';		// * list decoration 2
	
	public $formRequiredMarker	= '*';
	
	public $captionVariables	= array();	// *Caption variables for list, table, etc...
	public $addVariables		= array();	// *addional variables for list_row, table_row, etc...

	public $dynamicListColumns;			// *array with captions for dynamic list
	
	/*
	 * @var Template
	 */
	public $tpl;
		
	public $isDynamic = false;				// we set this variable to true when process dynamic list/view
	
	function __construct()
	{
		// cretae new TPL object for make functions
		$this->tpl = new Template();
	}

	/********************************* TEMPLATE METHODS **************************/
	
	/**
	* @return void
	* @param string $tpl_name - template file name
	* @param bool $param1
	* @param bool $param2
	* @desc Load template file to $parser->tpl object
	*/
	function LoadTemplate($tplName, $param1=true, $param2=true)
	{
		if ($this->curTplName != $tplName)
		{
			$this->tpl->LoadTemplatefile($tplName, $param1, $param2);
			$this->curTplName = $tplName;
		}
		else
		{
			$this->tpl->blockdata = array();
			$this->tpl->Free();
		}
	}
	
	/**
	 * Render image tag detecting image size
	 *
	 * @param string $imgSrc image source
	 * @param string $params tag params i.e. align="absmiddle"
	 * @return string
	 */
	function RenderImage($imgSrc, $params=null)
	{
		if (!strlen($imgSrc)) return '';
		
		$size = getimagesize($imgSrc);
		$width = $size[0];
		$height = $size[1];
		
		return "<img src=\"$imgSrc\" height=\"$height\" width=\"$width\" border=\"0\" $params />";
		
	}
	
	
	//========================= LIST METHODS ==============================
	
	/**
	* @return void
	* @param array $arr
	* @param string $block_name
	* @desc parse curreent TPL page list block
	*/
	function ParseList($obj, $blockName='list')
	{
		GLOBAL $App,$Tpl;
		$App->Load('list', 'parser');
		ParserListHandler($obj, $blockName, $Tpl, 'parse');
	}
	
	/**
	* @return string
	* @param array $arr
	* @param string $tpl_name
	* @param string $block_name
	* @desc make list
	*/
	function MakeList($obj, $tplName, $blockName='list')
	{
		GLOBAL $App;
		$App->Load('list', 'parser');
		$this->LoadTemplate($tplName);
		return ParserListHandler($obj, $blockName, $this->tpl, 'make');
	}
	
	/**
	* @return string
	* @param array $arr
	* @param string $tpl_name
	* @param string $block_name
	* @desc make dynamic list
	*/
	function MakeDynamicList($obj, $tplName, $blockName='dynamic_list')
	{
		GLOBAL $App;
		$App->Load('dynamic_list', 'parser');
		$this->LoadTemplate($tplName);
		return ParserDynamicListHandler($obj, $blockName, $this->tpl, 'make');
	}
	
	/**
	* @return void
	* @param array $arr
	* @param string $block_name
	* @desc parse dynamic list
	*/
	function ParseDynamicList($obj, $blockName='dynamic_list')
	{
		GLOBAL $App,$Tpl;
		$App->Load('dynamic_list', 'parser');
		ParserDynamicListHandler($obj, $blockName, $Tpl, 'parse');
	}
	
	/**
	 * Set columns list for dynamic list to be parser
	 *
	 * @param hash array $columns
	 */
	function SetDynamicListColumns($columns)
	{
		if (Utils::IsArray($columns))
		{
			foreach ($columns as $key=>$name)
			{
				if (Utils::IsArray($name))
				{
					$columns[$key]['element'] = ucwords(str_replace('_', '&nbsp;', $name['element']));
				}
				else
				{
					$columns[$key] = ucwords(str_replace('_', '&nbsp;', $name));
				}
			}
			$this->dynamicListColumns = $columns;
		}
	}

	/******************************** VIEW METHODS ********************************/
	/**
	* @return void
	* @param array $arr
	* @param string $block_name
	* @desc parse $arr view
	*/
	function ParseView($obj, $blockName='view')
	{
		GLOBAL $App,$Tpl;
		$App->Load('view', 'parser');
		ParserViewHandler($obj, $blockName, $Tpl, 'parse');
	}
	
	/**
	* @return string
	* @param array $arr
	* @param string $tpl_name
	* @param string $block_name
	* @desc make $arr view
	*/
	function MakeView($obj, $tplName, $blockName='view')
	{
		GLOBAL $App;
		$App->Load('view', 'parser');
		$this->LoadTemplate($tplName);
		return ParserViewHandler($obj, $blockName, $this->tpl, 'make');
	}
	
	/**
	* @return void
	* @param array $arr
	* @param string $block_name
	* @desc parse dynamic view
	*/
	function ParseDynamicView($obj, $blockName='dynamic_view')
	{
		GLOBAL $App,$Tpl;
		$App->Load('dynamic_view', 'parser');
		ParserDynamicViewHandler($obj, $blockName, $Tpl, 'parse');
	}
	
	/**
	* @return string
	* @param array $arr
	* @param string $tpl_name
	* @param string $block_name
	* @desc make dynamic view
	*/
	function MakeDynamicView($obj, $tplName, $blockName='dynamic_view')
	{
		GLOBAL $App;
		$App->Load('dynamic_view', 'parser');
		$this->LoadTemplate($tplName);
		return ParserDynamicViewHandler($obj, $blockName, $this->tpl, 'make');
	}
	
	/******************************** FORM METHODS **************************************/
	/**
	* @return string
	* @param array $fields
	* @param array $values
	* @param string $tpl_name
	* @param string $block_name
	* @desc make html form
	*/
	function MakeDynamicForm($dataItem, $tplName, $blockName='dynamic_form')
	{
		GLOBAL $App;
		$App->Load('dynamic_form', 'parser');
		$this->LoadTemplate($tplName);
		Application::LoadJs('[/]js/lisk/check.js');
		return ParserDynamicFormHandler($dataItem, $blockName, $this->tpl, 'make');
	}
	
	/**
	* @return void
	* @param array $fields
	* @param array $values
	* @param string $block_name
	* @desc parse html form
	*/
	function ParseDynamicForm($dataItem, $blockName='form')
	{
		GLOBAL $App,$Tpl;
		$App->Load('dynamic_form', 'parser');
		Application::LoadJs('[/]js/lisk/check.js');
		ParserDynamicFormHandler($dataItem, $blockName, $Tpl, 'parse');
	}

	/**
	* @return void
	* @param array $arr
	* @param string $block_name
	* @desc parse $arr view
	*/
	function ParseForm($obj, $blockName='form')
	{
		GLOBAL $App,$Tpl;
		$App->Load('form', 'parser');
		Application::LoadJs('[/]js/lisk/check.js');
		ParserFormHandler($obj, $blockName, $Tpl, 'parse');
	}
	
	/**
	* @return string
	* @param array $arr
	* @param string $tpl_name
	* @param string $block_name
	* @desc make $arr view
	*/
	function MakeForm($dataItem, $tplName, $blockName='form')
	{
		GLOBAL $App;
		$App->Load('form', 'parser');
		$this->loadTemplate($tplName);
		Application::LoadJs('[/]js/lisk/check.js');
		return ParserFormHandler($dataItem, $blockName, $this->tpl, 'make');
	}
	
	/************************** ADDITIONAL PARSER METHODS **********************/
	
	/**
	 * Render Navigation structures. Based on List methods
	 * the only difference is that the last record goes to
	 * caption variables and we use only {NAME} value of this
	 * record.
	 *
	 * @param Rows $rows values
	 * @param string $tplName
	 * @param string $blockName
	 * @return HTML
	 */
	function MakeNavigation($rows, $tplName, $blockName='navigation')
	{
		if (!Utils::IsArray($rows)) return '';

		/*
		normilize navigation rows
		 from NAME -> URL to array (NAME -> URL)
		*/
		$rez = array();
		foreach ($rows as $key=>$row)
		{
			if (Utils::IsArray($row)) $rez[]=$row;
			else $rez[] = array(
				'name'	=> $key,
				'url'	=> $row
			);
		}
		$rows = $rez;

		$total = sizeof($rows);
		$last = $rows[$total-1];
		unset($rows[$total-1]);

		$this->SetCaptionVariables(array(
			'name'	=> $last['name']
		));

		return $this->MakeList($rows, $tplName, $blockName);
	}

	
	function MakeSteps($tplName, $blockName, $totalSteps, $curStep=null)
	{
		$this->LoadTemplate($tplName);
		if ($curStep == null) $curStep = 1;
		for ($i=1; $i<=$totalSteps; $i++)
		{
			$blockSuffix = ($i==$curStep) ? '_on' : '_off';
			$this->tpl->TouchBlock($blockName.$i.$blockSuffix);
		}
		$this->tpl->SetCurrentBlock($blockName);
		$this->tpl->ParseCurrentBlock();
		return $this->tpl->Get();
	}
	
	function MakeMenu($Node, $cl, $startLevel, $tplName)
	{
		GLOBAL $App;
		$App->Load('menu', 'parser');
		return ParserMenuHandler($Node, $cl, $startLevel, $tplName);
	}
	
	function GetHtml($tplName, $blockName)
	{
	    // do not use $this->tpl here
		STATIC $tpl = null;
		if ($tpl==null) $tpl = new Template();
		$tpl->LoadTemplateFile($tplName, false, true);
		$tpl->TouchBlock($blockName);
		return $tpl->Get($blockName);
	}
	
	/**************************** TABLE METHODS ******************************/

	/**
	 * Make Table
	 *
	 * @param DataItem $dataItem
	 * @param integer $cols
	 * @param string $tplName
	 * @param string $blockName
	 * @return HTML
	 */
	function MakeTable($dataItem, $cols, $tplName='system', $blockName='table')
	{
		GLOBAL $App;
		$App->Load('table', 'parser');
		$this->LoadTemplate($tplName);
		return ParserTableHandler($dataItem, $cols, $blockName, $this->tpl, 'make');
	}
	
	/**
	* @return void
	* @param array  $arr - hash^2 of values
	* @param int  $cols - number of columns
	* @param string $block_name
	* @desc parse table
	*/
	function ParseTable($arr, $cols, $blockName='table')
	{
		GLOBAL $App,$Tpl;
		$App->Load('table', 'parser');
		ParserTableHandler($arr, $cols, $blockName, $Tpl, 'parse');
	}
	
	function MakeTableByRows(Data $di, $cols, $tplName, $blockName)
	{
	    GLOBAL $App;
		$App->Load('table_by_rows', 'parser');
		return RenderTableByRows($di, $cols, $tplName, $blockName);
	}
	
	/********************* ADD & CAPTION & DECORATION VARIABLES METHODS ************/
	
	/**
	* @return void
	* @param array $add_variables Array of additional variables
	* @desc Set additionals variables.
	*/
	function SetAddVariables($addVariables)
	{
		$this->addVariables = Utils::MergeArrays($this->addVariables, $addVariables);
	}
	
	/**
	* @return void
	* @param array $caption_variables Array of caption variables
	* @desc Set caption variables.
	*/
	function SetCaptionVariables($captionVariables)
	{
		$this->captionVariables = Utils::MergeArrays($this->captionVariables, $captionVariables);
	}
	
	function SetListDecoration($listDecoration1, $listDecoration2)
	{
		$this->listDecoration1 = $listDecoration1;
		$this->listDecoration2 = $listDecoration2;
	}

	/**
	* @return array Array of additional varibles
	* @desc Get additional varibels array
	*/
	function GetAddVariables()
	{
		if (Utils::IsArray($this->addVariables)) return $this->addVariables;
		else return array();
	}

	function GetCaptionVariables()
	{
		if (Utils::IsArray($this->captionVariables)) return $this->captionVariables;
		else return array();
	}
	
	/**
	* @return void
	* @desc Clear all additional varibles
	*/
	function ClearAddVariables()
	{
		$this->captionVariables = array();
		$this->addVariables = array();
	}
	
	/**
	 * Clear List decoration values
	 *
	 */
	function ClearListDecoration()
	{
		$this->listDecoration1 = '';
		$this->listDecoration2 = '';
	}
	
    static public function GetFileContent($tplName)
    {
        GLOBAL $App;
    
        $filename = $App->tplPath.$tplName.'.'.$App->tplExt;
        return file_get_contents($filename);
    }
}

$GLOBALS['Parser'] = new Parser();

?>