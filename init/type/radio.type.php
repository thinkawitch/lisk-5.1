<?php
/**
 * Lisk Type Radio
 * @package lisk
 *
 */
class T_radio extends LiskType
{
	
	public $listType;
	
	public $cond;
	public $crossField;
	public $addValues;
		
	public $objectType;
	public $objectName;
		
	public $autoJump;
		
	public $values = array();		// array of values when deal with 'list_arr'
	
	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		GLOBAL $App;
		
		$this->listType		= isset($info['object']) ? $info['object']: $App->RaiseError('Radio type unsetted.');
		
		$this->crossField	= isset($info['cross_field']) ? $info['cross_field'] : 'name';
		$this->cond			= isset($info['cond']) ? $info['cond'] : null;
		$this->addValues	= isset($info['add_values']) ? $info['add_values'] : array();
		
	    if (is_array($this->listType))
		{
			$this->objectType = 'arr';
			$this->values = $this->listType;
		}
		else if ($this->listType == 'arr')
		{
		    $this->objectType = 'arr';
		}
		else
		{
			$matches = array();
			preg_match('/^([a-z]+)\_([a-z_0-9]+)$/', $this->listType, $matches);
			$this->objectType = $matches[1];
			$this->objectName = $matches[2];
		}
		
		$this->type = LiskType::TYPE_RADIO;
		$this->tplFile = 'type/radio';
	}
	
	function Insert(&$values)
	{
		return @$values[$this->name];
	}
	
	function Update(&$values)
	{
		return @$values[$this->name];
	}
	
	function Delete(&$values)
	{
		return true;
	}
	
	function RenderFormView()
	{
		$this->GetValues();
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
		if (!Utils::IsArray($this->values)) return '';

        $rez = '';
	    foreach ($this->values as $key=>$name)
		{
			if (is_array($name))
			{
				$key = $name['id'];
				$name = $name['name'];
			}
			$selected = ($key == $this->value && $this->value !== null) ? ' checked="checked"' : null;
			$rez .= '<input name="'.$this->name.'" type="radio" value="'.$key.'" '.$this->RenderFormParams().' '.$selected.' />'.$name.'<br />';
		}
		return $rez;
	}

	function RenderFormTplView()
	{

		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		
		$tpl->SetCurrentBlock('form_radio_row');
		if (Utils::IsArray($this->values))
		{
			foreach ($this->values as $key=>$name)
			{
				if (is_array($name))
				{
					$key = $name['id'];
					$name = $name['name'];
				}
				$tpl->SetVariable(array(
					'CAPTION'	=> $name,
					'VALUE'		=> $key,
					'NAME'		=> $this->name,
					'CHECKED'	=> ($key == $this->value && $this->value !== null) ? ' checked="checked"' : null,
					'PARAMS'	=> $this->RenderFormParams(),
				));
				$tpl->ParseCurrentBlock();
			}
		}
		
		$tpl->SetcurrentBlock('form');
		$tpl->ParseCurrentBlock();
		
		return $tpl->Get();
	}
	
	function GetValues()
	{
		GLOBAL $Db,$App;

		switch ($this->objectType)
		{
			case 'def':
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
				if (!Utils::IsArray($arr))
				{
					$App->RaiseError('Array <b>LIST_'.strtoupper($this->objectName).'</b> not found.');
				}
				break;
				
			case 'table':
				$arr = $Db->Select($this->cond, null, "id,{$this->crossField} AS name", $this->objectName);
				break;
				
			case 'data':
				$di = Data::Create($this->objectName, false);
 				$arr = $Db->Select($this->cond, $di->order, "id,{$this->crossField} AS name", $di->table);
 				break;
					
			case 'arr':
				$arr = $this->values;
				break;
		}

		$this->values = Utils::MergeArrays($this->addValues, $arr);
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
		GLOBAL $Db,$App;
		switch ($this->objectType)
		{
			case 'def':
				$arr = $GLOBALS['LIST_'.strtoupper($this->objectName)];
				if (!Utils::IsArray($arr))
				{
					$errMsg ='Array <b>LIST_'.strtoupper($this->objectName).'</b> not found.';
					$App->RaiseError($errMsg);
				}
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
				break;
		}
		
		return $arr[$this->value];
	}
	
}

?>