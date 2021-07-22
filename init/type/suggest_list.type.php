<?php
/**
 * Lisk Type Suggest List
 * @package lisk
 *
 */
class T_suggest_list extends LiskType
{
	public $listType;

	public $cond;
	public $crossField;

	public $objectType;
	public $objectName;

	public $values = array(); // array of values when deal with 'list_arr'

	public $handlerGetSuggestion;
	public $handlerGetCaption;

	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		GLOBAL $App;
		$this->listType		= (isset($info['object'])) ? $info['object'] : $App->RaiseError('Suggest_List type unsetted.');

		$this->crossField	= isset($info['cross_field']) ? $info['cross_field'] : 'name';
		$this->cond			= isset($info['cond']) ? $info['cond'] : null;

		if (Utils::IsArray($this->listType))
		{
			$this->objectType = 'arr';
			$this->values = $this->listType;
		}
		elseif ($this->listType == 'arr')
		{
			$this->objectType = 'arr';
			$this->values = isset($info['values']) ? $info['values'] : array();
		}
	    elseif ($this->listType == 'custom')
		{
			$this->objectType = 'custom';
			$this->handlerGetSuggestion = $info['handler_get_suggestion'];
			$this->handlerGetCaption = $info['handler_get_caption'];
		}
		else
		{
			$matches = array();
			preg_match('/^([a-zA-Z]+)\_([a-zA-Z_0-9]+)$/', $this->listType, $matches);
			$this->objectType = $matches[1];
			$this->objectName = $matches[2];
		}

		$this->type = LiskType::TYPE_SUGGEST_LIST;
		$this->tplFile = 'type/suggest_list';
	}

	function Insert(&$values)
	{
		return isset($values[$this->name]) ? $values[$this->name] : null;
	}
	
	function Update(&$values)
	{
		return isset($values[$this->name]) ? $values[$this->name] : null;
	}
	
	function Delete(&$values)
	{
		return true;
	}

	function RenderFormView()
	{
		switch ($this->formRender)
		{
			case 'tpl':
				return $this->RenderFormTplView();
				break;
				
			default:
				return $this->RenderFormHtmlView();
				break;
		}
	}

	function RenderFormHtmlView()
	{
	    return $this->RenderFormTplView();
	}

	function RenderFormTplView()
	{
	    $caption = $this->GetCaption();
	    
		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

		$tpl->SetCurrentBlock('form');
		$tpl->Setvariable(array(
			'name'		=> $this->name,
		    'value'		=> $this->value,
		    'caption'	=> $caption,
			'params'	=> $this->RenderFormParams(),
		    'type'		=> $this->dataItem->name
		));
		$tpl->ParseCurrentBlock();
		return $tpl->Get();
	}
    
	private function GetCaption()
	{
        GLOBAL $Db,$App;

		switch ($this->objectType)
		{
			case 'def':
				$arr = $GLOBALS['LIST_'.strtoupper($this->objectName)];
				if (!Utils::IsArray($arr))
				{
					$errMsg = 'Array <b>LIST_'.strtoupper($this->objectName).'</b> not found.';
					$App->RaiseError($errMsg);
				}
				return isset($arr[$this->value]) ? $arr[$this->value] : '';
				break;

			case 'table':
				return $Db->Get('id='.Database::Escape($this->value), $this->crossField, $this->objectName);
				break;

			case 'data':
				$di = Data::Create($this->objectName, false);
 				return $Db->Get('id='.Database::Escape($this->value), $this->crossField, $di->table);
 				break;

			case 'arr':
				$arr = $this->values;
				return isset($arr[$this->value]) ? $arr[$this->value] : '';
				break;
				
            case 'custom':
				return call_user_func($this->handlerGetCaption, $this->value);
				break;
		}
		
		return '';
	}
	
	function GetSuggestion($query, $limit)
	{
		GLOBAL $App;
		$suggestion = '';

		switch ($this->objectType)
		{
			case 'def':
			    if (!isset($GLOBALS['LIST_'.strtoupper($this->objectName)])) $App->RaiseError('Array <b>LIST_'.strtoupper($this->objectName).'</b> not found.');
			    
				$arr = $GLOBALS['LIST_'.strtoupper($this->objectName)];
				if ($this->cond != null && strtolower(substr($this->cond, 0, 5)) == 'id in')
				{
					$str = substr($this->cond, 7);
					$str = substr($str, 0, -1);
					$condIds = preg_split('/,/', $str);
					$rez = array();
					foreach ($condIds as $key)
					{
						$rez[$key] = $arr[$key];
					}
					$arr = $rez;
				}
				//TODO
				break;

			case 'table':
				$suggestion = $this->GetSuggestionFromTable($query, $limit, $this->objectName);
				break;

			case 'data':
				$di = Data::Create($this->objectName, false);
				$suggestion = $this->GetSuggestionFromTable($query, $limit, $di->table);
 				break;

			case 'arr':
				$arr = $this->values;
				//TODO
				break;
				
		    case 'custom':
				return call_user_func($this->handlerGetSuggestion, $query, $limit);
				break;
		}
        
		return $suggestion;
	}
	
	private function GetSuggestionFromTable($query, $limit, $table)
	{
	    GLOBAL $Db;
	    $suggestion = '';
	    
	    $cond = $this->cond;
		if (strlen($cond)) $cond .= ' AND ';
		$cond .= $this->crossField.' LIKE '.Database::Escape('%'.$query.'%');
		
		$sql = "SELECT id,{$this->crossField} AS name FROM $table WHERE $cond ORDER BY name LIMIT 0,$limit";
		$rows = $Db->Query($sql);
        if (Utils::IsArray($rows))
        {
            foreach ($rows as $row)
            {
            	$obj = new stdClass();
            	$obj->id = $row['id'];
            	$obj->value = $row['name'];
            	
            	$suggestion[] = $obj;
            }
        }
        
        return json_encode($suggestion);
	}

	function RenderView($param1=null, $param2=null)
	{
		switch ($param1)
		{
			case 'KEY':
				return $this->value;
				break;
				
			default:
				return $this->RenderDefaultView();
		}
	}
	
	function RenderDefaultView()
	{
		return $this->GetCaption();
	}
}

?>