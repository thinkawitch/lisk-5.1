<?php
/**
 * CLASS Template
 * @package lisk
 *
 * @example
 * - <lisk:field name="%name%_confirmation" render="form" /> -> will be generated automaticaly if object %name% is assigned
 * - <lisk:field />
 * - <lisk:include />
 * - <lisk:content />
 */


/**
 * Evaluates inline php code inserted into tpl
 *
 * @param array $matches
 * @return sting
 */
function TplEval($matches)
{
	$matches[1] = preg_replace('/\#\#(.*)\#\#/e', "str_replace(
	\"'\", '\\'',
<<<EOD
$1
EOD
);", $matches[1]);
	
    $result = null;
	eval(' $result = ' . preg_replace('@\$([A-Za-z][A-Za-z0-9_]+)@sm', '$GLOBALS[\'\1\']', $matches[1]).';');
	return preg_replace_callback(Template::EVAL_REG_EXP, 'TplEval', $result);
}


/**
 * Template Class
 *
 */
class Template extends IntegratedTemplate
{
    /**
     * @var boolean
     */
    public $usePrecompile = false;
    /**
     * @var boolean
     */
    public $forcePrecompile = false;
    /**
     * @var string
     */
    private $precompileDir = '_ready/';

	/**
    * Constructor
    * @param string $root
    */
	function __construct($root=null)
	{
		GLOBAL $App;
		if ($root==null) $root = $App->tplPath;
		parent::__construct($root);
	}
	
    function GetSystemTemplateFile($name)
	{
	    GLOBAL $App;
	    
	    if (file_exists($App->tplPath.$name.'.'.$App->tplExt)) $tplName = $name;
	    else $tplName = 'system/'.$name;
	    
		return $tplName;
	}
	
	/**
    * Load template file with name of script executing
    * @param bool $removeUnknownVariables
    * @param bool $removeEmptyBlocks
    * @see loadTemplatefile
    */
	function Load($tplName=true, $removeUnknownVariables=true, $removeEmptyBlocks=true)
	{
		if (is_bool($tplName))
		{
			$removeEmptyBlocks = $removeUnknownVariables;
			$removeUnknownVariables = $tplName;
			$tplName = substr(basename($_SERVER['PHP_SELF']), 0, -4);
		}
		$this->LoadTemplatefile($tplName, $removeUnknownVariables, $removeEmptyBlocks);
	}

	/**
    * Load template file
    * @param string $filename
    * @param bool $removeUnknownVariables
    * @param bool $removeEmptyBlocks
    */
	function LoadTemplatefile($filename, $removeUnknownVariables=true, $removeEmptyBlocks=true)
	{
		GLOBAL $App;

		$regularName = $this->fileRoot.$filename.'.'.$App->tplExt;
		
		if ($this->usePrecompile)
		{
		    $this->removeUnknownVariables = $removeUnknownVariables;
		    $this->removeEmptyBlocks = $removeEmptyBlocks;
		    
		    if ($this->forcePrecompile || !file_exists($this->fileRoot.$this->precompileDir.$filename.'.'.$App->tplExt)) $this->Precompile($filename);
		    else $this->LoadPrecompiled($filename);
		}
		else
		{
		    if (!file_exists($regularName)) $App->RaiseError('Template file <b>'.$regularName.'</b> is not found');
		    
		    parent::LoadTemplatefile($regularName, $removeUnknownVariables, $removeEmptyBlocks);
		}
	}
	
	function Precompile($filename)
	{
	    GLOBAL $App, $FileSystem;
	    
	    $src = $this->fileRoot.$filename.'.'.$App->tplExt;
	    $dst = $this->fileRoot.$this->precompileDir.$filename.'.'.$App->tplExt;
	    $regularName = $this->fileRoot.$filename.'.'.$App->tplExt;
	    
	    $dir = dirname($dst);
	    if (!file_exists($dir)) $FileSystem->CreateDir($dir);
	    
	    $template = trim($this->GetFile($src));
	    if ($template=='') $App->RaiseError('Trying to compile empty template '.$src);
	    
	    $this->template = '<!-- BEGIN __global__ -->' . $template . '<!-- END __global__ -->';
	    $this->lastTemplatefile = $regularName;
	    $this->Init();

	    $cache = array(
	        'liskTags' => $this->liskTags,
	        'blockinner' => $this->blockinner,
	        'blockvariables' => $this->blockvariables,
	        'blockdata' => $this->blockdata,
	        'blocklist' => $this->blocklist,
	    );
	    
	    //save compiled template
	    if (false !== ($f = fopen($dst, 'w+')))
	    {
	        fwrite($f, serialize($cache));
	        fclose($f);
	    }
	}
	
	function LoadPrecompiled($filename)
	{
	    GLOBAL $App;
	    $regularName = $this->fileRoot.$filename.'.'.$App->tplExt;
	    
	    if ($this->lastTemplatefile==$regularName)
	    {
	        $this->Free();
	        return;
	    }
	    
	    $path = $this->fileRoot.$this->precompileDir.$filename.'.'.$App->tplExt;
	    if (!file_exists($path)) $App->RaiseError('Precompiled template not found '.$path);
	    
	    $cache = @unserialize(file_get_contents($path));
	    
	    if (!Utils::IsArray($cache)) $App->RaiseError('Precompiled template is empty '.$path);
	    
	    $this->Free();
	    
	    $this->blocklist = $cache['blocklist'];
        $this->blockdata = $cache['blockdata'];
        $this->blockvariables = $cache['blockvariables'];
        $this->blockinner = $cache['blockinner'];
        $this->liskTags = $cache['liskTags'];
        $this->lastTemplatefile = $regularName;
        
	    unset($cache);
	}

	

	/**
    * set variable into template block
    * @param mixed $placeholder
    * @param string $variable
    */
	function SetVariable($placeholder, $variable='')
	{
		if (is_array($placeholder))
		{
			$hash = array();
			foreach ($placeholder as $key=>$val)
			{
				$hash[strtoupper($key)] = $val;
			}
			return parent::SetVariable($hash);
		}
		else
		{
			return parent::SetVariable(strtoupper($placeholder), $variable);
		}
	}

	/**
    * Parse variable into template block
    * @param array $arr
    * @param string $block_name
    * @see setVariable()
    */
	function ParseVariable($arr, $blockName)
	{
		if ($this->SetCurrentBlock($blockName))
		{
			$this->SetVariable($arr);
			$this->ParseCurrentBlock();
		}
	}

	/**
    * Reads a file from disk and returns its content.
    * @param string $page
    * @return string - parsed template
    */
	function Show($page='')
	{
		if ($page!='') echo trim(preg_replace_callback(Template::EVAL_REG_EXP, 'TplEval', $page));
		else parent::Show();
	}
	
	/**
	 * Find all variables into tpl blocks
	 *
	 */
	function BuildBlockvariablelist()
	{
		parent::BuildBlockvariablelist();
	
		foreach ($this->blocklist as $name => $content)
		{
            $regs = array();
		    //lets deal with lisk:field
			preg_match_all($this->liskFieldRegExp, $content, $regs);
			
			if (0 != count($regs[0]))
			{
				foreach ($regs[0] as $k => $var)
				{
					//this is inner content of lisk tag
					$str = $regs[1][$k];
					
					$params = $this->GetLiskTagAttributes($str);
					
				    //name is required attribute, only named are allowed
                    if (isset($params['name']) && strlen($params['name']))
					{
						$this->liskTags[$name][$var] = $params;
    				}
				}
			}
		}
	}
}

/*********************************************************/

class IntegratedTemplate
{
    const EVAL_REG_EXP = '@<\?\s(.+?)\s\?>@sm';
    
	protected $liskFieldRegExp		= '/<lisk:field\s+([^>]*)>/';
	protected $liskIncludeRegExp	= '/<lisk:include\s+([^>]*)>/';
	protected $liskContentRegExp	= '/<lisk:content\s+([^>]*)>/';
	protected $liskAttributeRegExp1	= '/([\w\d\-]+)="([^"]*)"/';
	protected $liskAttributeRegExp2	= '/([\w\d\-]+)=\'([^\']*)\'/';
	
	/**
    * Contains the error objects
    * @var      array
    * @access   public
    * @see      halt(), $printError, $haltOnError
    */
	public $err = array();

	//Clear cache on get()?
	public $clearCache = false;

	public $openingDelimiter = '{';
	public $closingDelimiter = '}';

	/**
    * RegExp matching a block in the template.
    * Per default "sm" is used as the regexp modifier, "i" is missing.
    * That means a case sensitive search is done.
    */
	public $blocknameRegExp = '[0-9A-Za-z_-]+';

	/**
    * RegExp matching a variable placeholder in the template.
    * Per default "sm" is used as the regexp modifier, "i" is missing.
    * That means a case sensitive search is done.
    */
	public $variablenameRegExp = '[\^\|0-9A-Za-z_\s-]+';

	/**
    * RegExp used to find variable placeholder, filled by the constructor.
    */
	public $variablesRegExp = '';

	/**
    * RegExp used to strip unused variable placeholder.
    * @brother  $variablesRegExp
    */
	public $removeVariablesRegExp = '';

	/**
    * Controls the handling of unknown variables, default is remove.
    */
	public $removeUnknownVariables = true;

	/**
    * Controls the handling of empty blocks, default is remove.
    */
	public $removeEmptyBlocks = true;

	/**
    * RegExp used to find blocks an their content, filled by the constructor.
    */
	public $blockRegExp = '';

	/**
    * Name of the current block.
    */
	public $currentBlock = '__global__';

	/**
    * Content of the template.
    */
	public $template = '';

	/**
    * Array of all blocks and their content.
    */
	public $blocklist = array();

	/**
    * Array with the parsed content of a block.
    */
	public $blockdata = array();

	/**
    * Array of variables in a block.
    */
	public $blockvariables = array();

	/**
    * Array of inner blocks of a block.
    */
	public $blockinner = array();

	public $touchedBlocks = array();
	public $variableCache = array();
	public $clearCacheOnParse = false;
	public $fileRoot = '';
	public $flagBlocktrouble = false;
	public $flagGlobalParsed = false;
	public $flagCacheTemplatefile = true;
	public $lastTemplatefile = '';
	
	/**
	 * Contains found lisk:field
	 *
	 * @var array
	 */
	public $liskTags = array();
	
	/**
	 * Process lisk:content and lisk:include blocks
	 *
	 * @var boolean
	 */
	public $processLiskInclude = true;

	/**
	* @return void
	* @param string $root
	* @desc constructor
	*/
	function __construct($root = '')
	{
		$this->variablesRegExp = '@' . $this->openingDelimiter . '(' . $this->variablenameRegExp . ')' . $this->closingDelimiter . '@sm';
		$this->removeVariablesRegExp = '@' . $this->openingDelimiter . '\s*(' . $this->variablenameRegExp . ')\s*' . $this->closingDelimiter . '@sm';

		$this->blockRegExp = '@<!--\s+BEGIN\s+(' . $this->blocknameRegExp . ')\s+-->(.*)<!--\s+END\s+\1\s+-->@sm';

		$this->setRoot($root);
	} // end constructor

	/**
	* @return void
	* @param string $block
	* @desc Print a certain block with all replacements done.
	* @see get()
	*/
	function Show($block = '__global__')
	{
		echo $this->Get($block);
	} // end func show

	/**
    * Returns a block with all replacements done.
    *
    * @param    string     name of the block
    * @return   string
    * @access   public
    * @see      show()
    */
	function Get($block = '__global__')
	{
		if ('__global__' == $block && !$this->flagGlobalParsed)
		$this->Parse('__global__');

		if (!isset($this->blocklist[$block])) return '';
		
		$data = (isset($this->blockdata[$block])) ? $this->blockdata[$block] : '';
		
		if ($this->clearCache)
		{
			unset($this->blockdata[$block]);
		}
		
		//replace all lisk:content
		$data = $this->ParseLiskContent($data);
		
		//lets deal with lisk:include
		$data = $this->ParseLiskInclude($data);
		
		//lets deal with lisk:snippet
		$data = $this->ExecLiskSnippet($data);
		
		return trim($data);
	}

	function ExecLiskSnippet($data)
	{
		$LiskSnippet = new LiskSnippet($data);
		$LiskSnippet->ExecuteSnippets();
		return $LiskSnippet->page;
	}
	
	/**
	 * Replace Lisk tags with rendered html code
	 *
	 * @param string $block
	 * @param array $regs
	 * @param array $values
	 */
	private function ParseLiskTags($block, &$regs, &$values)
	{
	    if (!Utils::IsArray($regs)) return;
		if (!isset($this->liskTags[$block])) return;
		if (!Utils::IsArray($this->liskTags[$block])) return;
		
	    foreach ($this->liskTags[$block] as $params)
		{
			if (isset($params['_regsKey']) && isset($values[$params['_regsKey']]))
			{
				$obj = $values[$params['_regsKey']];
				
				if ($obj instanceof LiskType)
				{
					$obj->SetLiskTagParameters($params);
					$param1 = isset($params['render']) ? strtoupper($params['render']) : null;
					$param2 = isset($params['thumbnail']) ? strtoupper($params['thumbnail']) : null;
					$values[$params['_regsKey']] = $obj->Render($param1, $param2);
				}
			}
		}
	}
	
	/**
	 * Parse lisk:content
	 *
	 * @param string $data
	 * @return string
	 */
	function ParseLiskContent($data)
	{
		if (!$this->processLiskInclude) return $data;
		
		$regs = array();
		preg_match_all($this->liskContentRegExp, $data, $regs);
		if (0 != count($regs[0]))
		{
			$di = Data::Create('content');
			static $contentCache = array();
			foreach ($regs[0] as $k => $var)
			{
				//this is inner content of lisk:content
				$str = $regs[1][$k];
				
				$params = $this->GetLiskTagAttributes($str);
				
				//name is required attribute
				if (isset($params['name']))
				{
				    $name = $params['name'];
					//add block to cache
					if (!isset($contentCache[$name]))
					{
						$contentCache[$name] = $di->GetValue('`key`='.Database::Escape($name), 'content');
					}
					$data = preg_replace('@'.preg_quote($var).'@', $contentCache[$name], $data);
				}
			}
		}
	
		return $data;
	}
	
	/**
	 * Parse lisk:include
	 *
	 * @param string $data
	 * @return string
	 */
	private function ParseLiskInclude($data)
	{
		GLOBAL $App, $Parser;
		
		if (!$this->processLiskInclude) return $data;
		
		$regs = array();
		preg_match_all($this->liskIncludeRegExp, $data, $regs);
		if (0 != count($regs[0]))
		{
			foreach ($regs[0] as $k => $var)
			{
				//this is inner content of lisk tag
				$str = $regs[1][$k];
				
				$params = $this->GetLiskTagAttributes($str);
						
				//tplname is required attribute
				if (isset($params['tplname']))
				{
					$toReplace = '';
					if ($params['tplname'] && $params['blockname'])
					{
						$toReplace = $Parser->GetHTML($params['tplname'],$params['blockname']);
					}
					else
					{
						$filename = $App->sysRoot.$this->fileRoot.$params['tplname'].'.'.$App->tplExt;
						$toReplace = file_get_contents($filename);
					}
					
					$data = preg_replace('@'.preg_quote($var).'@', $toReplace, $data);
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * @param string $innerStr
	 * @return array
	 */
	protected function GetLiskTagAttributes($innerStr)
	{
	    $attribs = array();
	    $matches = array();
					
		//type1, with "
    	preg_match_all($this->liskAttributeRegExp1, $innerStr, $matches, PREG_SET_ORDER);
					
        if (Utils::IsArray($matches))
        {
            foreach ($matches as $mParams)
            {
			    $attribs[$mParams[1]] = $mParams[2];
			}
		}
        else
        {
            //type2, with '
            $matches = array();
            preg_match_all($this->liskAttributeRegExp2, $innerStr, $matches, PREG_SET_ORDER);
            if (Utils::IsArray($matches))
    		{
                foreach ($matches as $mParams)
    			{
                    $attribs[$mParams[1]] = $mParams[2];
                }
    	    }
        }
	    
	    return $attribs;
	}

	/**
    * Parses the given block.
    *
    * @param    string    name of the block to be parsed
    * @access   public
    * @see      parseCurrentBlock()
    */
	function Parse($block = '__global__', $flag_recursion = false, $touched=false)
	{
		if (!isset($this->blocklist[$block])) return false;

		if ('__global__' == $block)
		$this->flagGlobalParsed = true;

		$regs = array();
		$values = array();
		
		$vCache = $this->variableCache;
		
		if ($this->clearCacheOnParse)
		{
			foreach ($this->variableCache as $name => $value)
			{
				$regs[] = '@' . $this->openingDelimiter . $name . $this->closingDelimiter . '@';
				$values[] = ($value instanceof LiskType) ? $value->Render() : $value;
			}
			$this->variableCache = array();
		}
		else
		{
        	foreach ($this->blockvariables[$block] as $allowedvar => $v)
        	{
				if (isset($this->variableCache[$allowedvar]))
				{
					$regs[]   = '@'.$this->openingDelimiter . $allowedvar . $this->closingDelimiter . '@';
					$value = $this->variableCache[$allowedvar];
					$values[] = ($value instanceof LiskType) ? $value->Render() : $value;
					unset($this->variableCache[$allowedvar]);
				}
			}
			
			//and now for lisk tags
			foreach ($vCache as $name=>$value)
			{
			    if (!is_object($value)) continue;
				
				//if this is lisk tag
				if (isset($this->liskTags[$block]) && Utils::IsArray($this->liskTags[$block]))
				{
					foreach ($this->liskTags[$block] as $tag=>$params)
					{
						if (strtoupper($params['name'])==strtoupper($name))
						{
							$regs[]   = '@'.preg_quote($tag).'@';
							$values[] = $value;
							
							$this->liskTags[$block][$tag]['_regsKey'] = count($regs) - 1;
						}
						
						if (strtoupper($name.'_confirmation') == strtoupper($params['name']))
						{
							//add object for confirmation field
							$newObj = clone $value ;
							
							$newObj->name = strtolower($name.'_CONFIRMATION');
							$newObj->label = $value->label.' Confirmation';
							
							$regs[]   = '@'. preg_quote($tag) .  '@';
							$values[] = $newObj;
							$this->liskTags[$block][$tag]['_regsKey'] = count($regs)-1;
						}
					}
				}
			}
		}
		
		//parse lisk:field
		$this->ParseLiskTags($block, $regs, $values);
		
		//lets fix the $ problem
		if (Utils::IsArray($values))
		{
			foreach ($values as &$value)
			{
				if ($value instanceof LiskType) $value->value = addcslashes($value->value, '$\\');
				else $value = addcslashes($value, '$\\');
			}
		}
		
		//remove garbage objects
		foreach ($values as $k=>$v)
		{
			if (is_object($v)) unset($values[$k], $regs[$k]);
		}

		$outer = (0 == count($regs)) ? $this->blocklist[$block] : preg_replace($regs, $values, $this->blocklist[$block]);
		$empty = (0 == count($values)) ? true : false;

		//to parse inner block with variables not found
		static $countParser = 0;
		$countParser++;
		if($countParser<=1)
		{
			if (isset($this->blockinner[$block]))
			{
				foreach ($this->blockinner[$block] as $k => $innerblock)
				{
					$this->Parse($innerblock, true);
					
					if (isset($this->blockdata[$innerblock]))
					{
                        if ('' != $this->blockdata[$innerblock]) $empty = false;

					    $placeholder = $this->openingDelimiter . '__' . $innerblock . '__' . $this->closingDelimiter;
					    $outer = str_replace($placeholder, $this->blockdata[$innerblock], $outer);
					}
					
					$this->blockdata[$innerblock] = '';
				}
			}
		}
		$countParser = 0;

		if ($this->removeUnknownVariables) $outer = preg_replace($this->removeVariablesRegExp, '', $outer);

		if ($empty)
		{
			if (!$this->removeEmptyBlocks)
			{
				$this->blockdata[$block ] .= $outer;
			}
			else
			{
				// if block is touched - it should be parsed anyway
				// --------------------------------------------------------
				if ($touched)
				{
					if (isset($this->blockdata[$block])) $this->blockdata[$block] .= $outer;
					else $this->blockdata[$block] = $outer;
				}
				// --------------------------------------------------------
			}
		}
		else
		{
			if (isset($this->blockdata[$block])) $this->blockdata[$block] .= $outer;
			else $this->blockdata[$block] = $outer;
		}

		// added variables clean after tpl parsing
		// next(external) tpl parsing is going without OLD variables
		// --------------------------------------------------------
		if ($flag_recursion==false)
		{
			$this->variableCache = array();
		}
		// --------------------------------------------------------

		return $empty;
	} // end func parse

	
	/**
    * Parses the current block
    * @see      parse(), setCurrentBlock(), $currentBlock
    * @access   public
    */
	function ParseCurrentBlock()
	{
		return $this->Parse($this->currentBlock);
	} // end func parseCurrentBlock

	/**
    * Sets a variable value.
    *
    * The function can be used eighter like setVariable( "varname", "value")
    * or with one array $variables["varname"] = "value" given setVariable($variables)
    * quite like phplib templates set_var().
    *
    * @param    mixed     string with the variable name or an array %variables["varname"] = "value"
    * @param    string    value of the variable or empty if $variable is an array.
    * @param    string    prefix for variable names
    * @access   public
    */
	function SetVariable($variable, $value='')
	{
		if (is_array($variable))
		{
			$this->variableCache = Utils::MergeArrays($this->variableCache, $variable);
		}
		else
		{
			$this->variableCache[$variable] = $value;
		}

	} // end func setVariable

	/**
    * Sets the name of the current block that is the block where variables are added.
    *
    * @param    string      name of the block
    * @return   boolean     false on failure, otherwise true
    * @access   public
    */
	function SetCurrentBlock($block = '__global__')
	{
		if (!isset($this->blocklist[$block]))
		{
			//return new IT_Error("Can't find the block '$block' in the template.", __FILE__, __LINE__);
			GLOBAL $App;
			$App->RaiseError("Can't find the block [$block] in the [{$this->lastTemplatefile}].");
			return false;
		}
		
		$this->currentBlock = $block;
        return true;

	} // end func setCurrentBlock

	/**
    * Preserves an empty block even if removeEmptyBlocks is true.
    *
    * @param    string      name of the block
    * @return   boolean     false on false, otherwise true
    * @access   public
    * @see      $removeEmptyBlocks
    */
	function TouchBlock($block)
	{
		$this->Parse($block, false, true);
		return true;
	} // end func touchBlock

	/**
    * Clears all datafields of the object and rebuild the internal blocklist
    *
    * LoadTemplatefile() and setTemplate() automatically call this function
    * when a new template is given. Don't use this function
    * unless you know what you're doing.
    *
    * @access   public
    * @see      free()
    */
	function Init()
	{
		$this->Free();
		$this->FindBlocks($this->template);
		$this->BuildBlockvariablelist();
	} // end func init

	/**
    * Clears all datafields of the object.
    *
    * Don't use this function unless you know what you're doing.
    *
    * @access   public
    * @see      init()
    */
	function Free()
	{
		$this->err = array();

		$this->currentBlock = '__global__';

		$this->variableCache    = array();
		$this->touchedBlocks    = array();

		$this->flagBlocktrouble = false;
		$this->flagGlobalParsed = false;

	} // end func free

	/**
    * Sets the template.
    *
    * You can eighter load a template file from disk with LoadTemplatefile() or set the
    * template manually using this function.
    *
    * @param        string      template content
    * @param        boolean     remove unknown/unused variables?
    * @param        boolean     remove empty blocks?
    * @see          LoadTemplatefile(), $template
    * @access       public
    */
	function SetTemplate($template, $removeUnknownVariables = true, $removeEmptyBlocks = true)
	{
		$this->removeUnknownVariables = $removeUnknownVariables;
		$this->removeEmptyBlocks = $removeEmptyBlocks;

		if ('' == $template && $this->flagCacheTemplatefile)
		{
			$this->variableCache = array();
			$this->blockdata = array();
			$this->touchedBlocks = array();
			$this->currentBlock = '__global__';
		}
		else
		{
			$this->template = '<!-- BEGIN __global__ -->' . $template . '<!-- END __global__ -->';
			$this->Init();
		}

		if ($this->flagBlocktrouble) return false;

		return true;
	} // end func setTemplate

	/**
    * Reads a template file from the disk.
    *
    * @param    string      name of the template file
    * @param    bool        how to handle unknown variables.
    * @param    bool        how to handle empty blocks.
    * @access   public
    * @return   boolean    false on failure, otherwise true
    * @see      $template, setTemplate(), $removeUnknownVariables, $removeEmptyBlocks
    */
	function LoadTemplatefile($filename, $removeUnknownVariables = true, $removeEmptyBlocks = true)
	{
		$template = '';
		if (!$this->flagCacheTemplatefile || $this->lastTemplatefile != $filename) $template = $this->GetFile($filename);
			
		$this->lastTemplatefile = $filename;

		return $this->SetTemplate($template, $removeUnknownVariables, $removeEmptyBlocks, true);
	} // end func LoadTemplatefile

	/**
    * Sets the file root. The file root gets prefixed to all filenames passed to the object.
    *
    * Make sure that you override this function when using the class
    * on windows.
    *
    * @param    string
    * @see      IntegratedTemplate()
    * @access   public
    */
	function SetRoot($root)
	{
		if ('' != $root && '/' != substr($root, -1)) $root .= '/';

		$this->fileRoot = $root;

	} // end func setRoot

	/**
    * Build a list of all variables within of a block
    */
	function BuildBlockvariablelist()
	{
		foreach ($this->blocklist as $name => $content)
		{
		    $regs = array();
			preg_match_all( $this->variablesRegExp, $content, $regs );

			if (0 != count($regs[1]))
			{
				foreach ($regs[1] as $var) $this->blockvariables[$name][$var] = true;
			}
			else
			{
				$this->blockvariables[$name] = array();
			}
		}

	} // end func buildBlockvariablelist

	/**
    * Returns a list of all
    */
	function GetGlobalVariables()
	{
		$regs   = array();
		$values = array();

		foreach (array_keys($this->blockvariables['__global__']) as $allowedvar)
		{
			if (isset($this->variableCache[$allowedvar]))
			{
				$regs[]   = '@'.$this->openingDelimiter.$allowedvar.$this->closingDelimiter.'@';
				$values[] = $this->variableCache[$allowedvar];
				unset($this->variableCache[$allowedvar]);
			}
		}

		return array($regs, $values);
	} // end func getGlobalvariables

	/**
    * Recusively builds a list of all blocks within the template.
    *
    * @param    string    string that gets scanned
    * @see      $blocklist
    */
	function FindBlocks($string)
	{
		$blocklist = array();
        $regs = array();
        
		if (preg_match_all($this->blockRegExp, $string, $regs, PREG_SET_ORDER))
		{

			foreach ($regs as $match)
			{

				$blockname = $match[1];
				$blockcontent = $match[2];

				if (isset($this->blocklist[$blockname]))
				{
					//new IT_Error("The name of a block must be unique within a template. Found '$blockname' twice. Unpredictable results may appear.", __FILE__, __LINE__);
					$this->flagBlocktrouble = true;
				}

				$this->blocklist[$blockname] = $blockcontent;
				$this->blockdata[$blockname] = '';

				$blocklist[] = $blockname;
                
				
				$inner = $this->FindBlocks($blockcontent);
				foreach ($inner as $name)
				{

					$pattern = sprintf('@<!--\s+BEGIN\s+%s\s+-->(.*)<!--\s+END\s+%s\s+-->@sm', $name, $name);

					$this->blocklist[$blockname] = preg_replace(
					        $pattern,
					        $this->openingDelimiter . '__' . $name . '__' . $this->closingDelimiter,
					        $this->blocklist[$blockname]
					);
					$this->blockinner[$blockname][] = $name;
				}
				
			}
		}
		
		return $blocklist;
	} // end func findBlocks

	/**
    * Reads a file from disk and returns its content.
    * @param    string    Filename
    * @return   string    Filecontent
    */
	function GetFile($filename)
	{
		if ('/' == substr($filename, 0, 1) && '/' == substr($this->fileRoot, -1)) $filename = substr($filename, 1);
        
		$content = null;
        
		if (($fh = fopen($filename, 'r')) !== false)
		{
		    $content = fread($fh, filesize($filename));
		    fclose($fh);
		}

		return $content;
	} // end func getFile

} // end class IntegratedTemplate

$GLOBALS['Tpl'] = new Template();

?>