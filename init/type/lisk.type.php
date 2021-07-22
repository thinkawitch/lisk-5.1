<?php
/**
 * Lisk Type
 * base class for all lisk types
 * @package lisk
 *
 */

abstract class LiskType
{
	//sytem types constants
	const TYPE_HIDDEN    = 'hidden';
	const TYPE_CATEGORY  = 'category';
	const TYPE_FLAG      = 'flag';
	const TYPE_PASSWORD  = 'password';
	const TYPE_PROP      = 'prop';
    const TYPE_PROP_BIG	 = 'prop_big';
	const TYPE_RADIO     = 'radio';
	const TYPE_TREE      = 'tree';
	
	//public types constant
	const TYPE_DATE         = 'date';
	const TYPE_DATETIME     = 'datetime';
	const TYPE_TIME         = 'time';
	const TYPE_INPUT        = 'input';
	const TYPE_TEXT         = 'text';
	const TYPE_HTML         = 'html';
	const TYPE_WIKI         = 'wiki';
	const TYPE_CREOLE		= 'creole';
	const TYPE_FILE         = 'file';
	const TYPE_FILE_MULTI   = 'file_multi';
	const TYPE_IMAGE        = 'image';
	const TYPE_LIST         = 'list';
	const TYPE_PROP_LIST    = 'prop_list';
	const TYPE_SUGGEST_LIST = 'suggest_list';
    const TYPE_STARS        = 'stars';
	
	/**
	 * Object name
	 *
	 * @var string
	 */
	public $name;
	
	/**
	 * Object Label (nice name)
	 *
	 * @var string
	 */
	public $label;
	
	/**
	 * form params / attributes
	 * stores as name=>value
	 *
	 * @var array
	 */
	protected $formParams;
		
	/**
	 * FormRender mode [tpl|not]
	 *
	 * @var string
	 */
	public $formRender;
	
	/**
	 * Check parameters as described in DI structure
	 *
	 * @var string
	 */
	public $check;
	
	/**
	 * Custom check error message
	 *
	 * @var string
	 */
	public $checkMsg;
	
	/**
	 * js formatted check string
	 *
	 * @var string
	 */
	public $jsCheckString;
	
	/**
	 * default value (used in Data::Insert)
	 *
	 * @var any type
	 */
	public $defValue;
	
	/**
	 * Object value
	 *
	 * @var any type
	 */
	public $value;
	
	/**
	 * Defines if is the object is required for form entering (JS check)
	 *
	 * @var boolean
	 */
	public $isRequired = false;
	
	/**
	 * Field hint, used in dynamicform & form parser methods
	 * to display field hint
	 *
	 * @var string
	 */
	public $hint;
	
    /**
     * DI object
     *
     * @var Data
     */
    public $dataItem;

	/**
	 * Type name
	 *
	 * @var string/const
	 */
	public $type;
	
	/**
	 * Defines if the object is system and can't be edited via DIConstructor
	 *
	 * @var boolean
	 */
	public $isSystem = false;
	
    /**
     *  Turn on autosave field in form
     *
     * @var boolean
     */
    public $autoSave = false;

    /**
     * Auto save ID record
     *
     * @var integer
     */
    public $asId;

    /**
     * Auto save callback JS function
     *
     * @var string
     */
    public $asCallback;
    
	/**
     * Auto save handler url
     *
     * @var string
     */
    public $asHandlerUrl;
    
	/**
	 * Tpl file for tpl render
	 *
	 * @var string
	 */
	protected $tplFile = '';

	function __construct(array $info, Data $dataItem=null)
	{
	    GLOBAL $App;
	    
        if (is_object($dataItem)) $this->dataItem = $dataItem;
        
		$this->name     = isset($info['name']) ? $info['name'] : null;
		$this->label    = isset($info['label'])? $info['label']: Format::Label($this->name);
		
		$this->formParams = array();
		if (isset($info['form']) && Utils::IsArray($info['form']))
		{
		    foreach ($info['form'] as $fn=>$fv) $this->AddFormParam($fn, $fv);
		}
		
		$this->check		= isset($info['check']) ? $info['check'] : null;
		$this->checkMsg		= isset($info['check_msg']) ? $info['check_msg'] : null;
		
		$this->formRender	= (INIT_NAME=='cp') ? 'tpl' : (isset($info['render']) ? $info['render'] : '') ;
		$this->hint			= isset($info['hint']) ? $info['hint'] : null;
		$this->isSystem     = (isset($info['is_system']) && $info['is_system'] == true) ? true : false;
		
		$this->defValue		= isset($info['def_value']) ? $info['def_value'] : null;
		$this->value        = isset($info['value']) ? $info['value'] : null;
		
		//autosave
		$this->autoSave     = (isset($info['autosave']) && $info['autosave'] == true) ? true : false;
        $this->asId         = uniqid($this->name);
        $this->asCallback   = isset($info['callback']) ? $info['callback'] : null;
        
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
        $this->asHandlerUrl = isset($info['autosave_handler']) ? $info['autosave_handler'] : 'http://'.$host.$App->httpRoot.'cp/autosave.php';
        
        $this->formRender   = ($this->autoSave) ? 'tpl' : $this->formRender;
        
		$this->InitCheck();
	}

	/**
	 * Format JSCheckString based on check parametrs
	 *
	 */
	public function InitCheck()
	{
		if (!strlen($this->check)) return;
		
		$arr = preg_split('/[|]/',$this->check);
		$empty = 0;
		$min = '';
		$max = '';
		$regExp = '';
		foreach ($arr as $checkParam)
		{
			switch (substr($checkParam,0,3))
			{
				case 'emp':
					// empty
					$empty=1;
					$this->isRequired = true;
					break;
					
				case 'max':
					// max characters
					$max = preg_split('/:/',$checkParam);
					$max = $max[1];
					break;
					
				case 'min':
					// min characters
					$min = preg_split('/:/',$checkParam);
					$min = $min[1];
					break;
					
				case 'reg':
					// custom regular expression
					$pos = strpos($checkParam, ':');
					$regExp = substr($checkParam, $pos+1);
					break;
					
				case 'pre':
					// preset check (email,login,etc)
					$regExp = preg_split('/:/',$checkParam);
					$regExp = $regExp[1];
					break;
					
				case 'not':
					// value is not equal to ...
					$split = preg_split('/:/',$checkParam);
					$arr = preg_split('/,/', $split[1]);
					$regExp = $checkParam;
					break;
			}
		}
		$className = get_class($this);
		$funcName = strtoupper($className{2}).substr($className,3);
		$this->jsCheckString = "['{$this->name}', '$funcName', '{$this->label}','$regExp','$min','$max','','{$this->checkMsg}','{$empty}']";
		
	}
	
	abstract public function RenderFormView();
	abstract public function RenderView($param1=null, $param2=null);
	
	public function Render($param1=null, $param2=null)
	{
		switch (strtoupper($param1))
		{
			case 'FORM':
				return $this->RenderFormView();
				break;
				
			default:
				return $this->RenderView($param1, $param2);
				break;
		}
	}

    public function RenderAutoSaveScript()
    {
        if(!$this->autoSave || !$this->dataItem->value['id'])
        {
            $this->autoSave = false;
            return false;
        }
        
        $tpl = new Template();
        $tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
        $tpl->SetCurrentBlock('script');
        $tpl->ParseVariable(array(
            'AUTOSAVE_UID'  => $this->asId,
            'CALLBACK'      => $this->asCallback ? ','.$this->asCallback : '',
            'DATAITEM_NAME' => $this->dataItem->name,
            'DATAITEM_ID'   => $this->dataItem->value['id'],
            'DATAITEM_FIELD'=> $this->name,
            'HANDLER_URL'   => $this->asHandlerUrl,
        ), 'script');
        return $tpl->Get('script');
    }
	
	abstract public function Insert(&$values);
	abstract public function Update(&$values);
	abstract public function Delete(&$values);
		
	public function SetLiskTagParameters($params)
	{
		if (!Utils::IsArray($params)) return;
		
		$exclude = array();
		
		foreach ($params as $name=>$value)
		{
			switch (strtoupper($name))
			{
				case 'RENDERTPL':
						$value = strtolower($value);
						$this->formRender = 'tpl';
						//set another template
						if (strlen($value) && $value!='tpl' && $value!='default') $this->tplFile = $value;
						$exclude[] = $name;
						break;
						
				case 'THUMBNAIL':
				case 'RENDER':
				case 'NAME':
				case '_REGSKEY':
				        $exclude[] = $name;
						break;
						
				case 'FORMAT':
			            if ($this instanceof T_datetime || $this instanceof T_date || $this instanceof T_time ) $this->format = $value;
			            $exclude[] = $name;
			            break;
			}
		}
		
		//and for last let's generate all other attributes as is
		foreach ($params as $name=>$value)
		{
            if (!in_array($name, $exclude))
			{
			    $this->AddFormParam($name, $value);
			}
		}
	}
	
	public function AddFormParam($name, $value)
	{
	    $this->formParams[strtolower($name)] = $value;
	}
	
	public function RemoveFormParam($name)
	{
	    unset($this->formParams[strtolower($name)]);
	}
	
	public function ClearFormParams()
	{
	    $this->formParams = array();
	}
	
	/**
	 * Render form params of element
	 *
	 * @return string
	 */
	public function RenderFormParams()
	{
	    if (!Utils::IsArray($this->formParams)) return '';
	    
	    $params = '';
	    foreach ($this->formParams as $name=>$value)
	    {
	        $params .= ' '.$name.'="'.addcslashes($value, '"').'"';
	    }
	    return $params;
	}
}

?>