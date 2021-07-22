<?php
/**
 * Lisk Type Prop
 * @package lisk
 *
 */
class T_prop extends LiskType
{

	public $listType;

	public $cond;
    public $crossField;
    public $addValues;

	public $objectType;
	public $objectName;

	public $values = array();		// array of values when deal with 'prop_arr'

	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		GLOBAL $App;
		$this->listType		= (isset($info['object'])) ? $info['object']: $App->RaiseError('Prop object type unsetted.');

		$this->crossField	= isset($info['cross_field']) ? $info['cross_field'] : 'name';
		$this->cond			= isset($info['cond']) ? $info['cond'] : null;
		$this->addValues	= isset($info['add_values']) ? $info['add_values'] : array();

		if (Utils::IsArray($this->listType))
		{
			$this->objectType = 'arr';
			$this->values = $this->listType;
		}
		elseif ($this->listType == 'arr') $this->objectType = 'arr';
		else
		{
			$matches = array();
			preg_match('/^([a-zA-Z]+)\_([a-zA-Z_0-9]+)$/', $this->listType, $matches);
			$this->objectType = $matches[1];
			$this->objectName = $matches[2];
		}
		
		$this->type = LiskType::TYPE_PROP;
		$this->tplFile = 'type/prop';
	}
	
    function Insert(&$values)
	{
	    $rez = '';
		if (isset($values[$this->name]) && Utils::IsArray($values[$this->name]))
		{
			foreach ($values[$this->name] as $val)
			{
					$rez .= '<'.$val.'>';
			}
		}
		return $rez;
	}
	
	function Update(&$values)
	{
        $rez = '';
		if (isset($values[$this->name]) && Utils::IsArray($values[$this->name]))
		{
			foreach ($values[$this->name] as $val)
			{
					$rez .= '<'.$val.'>';
			}
		}
		return $rez;
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

	function RenderView($param1=null, $param2=null)
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
				$temp = $Db->Select($this->cond, null, "id,{$this->crossField} AS name", $this->objectName);
				if (Utils::IsArray($temp))
				{
					foreach($temp as $key => $row)
					{
						$arr[$row['id']] = $row['name'];
					}
				}
				break;

			case 'data':
				$dataItem = Data::Create($this->objectName, false);
 				$temp = $Db->Select($this->cond, null, "id,{$this->crossField} AS name", $dataItem->table);
				if (Utils::IsArray($temp))
				{
					foreach($temp as $key => $row)
					{
						$arr[$row['id']] = $row['name'];
					}
				}
 				break;

			case 'arr':
				$arr = $this->values;
				break;
		}

		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

		if (!Utils::IsArray($arr)) return '';
		
		//$values = preg_split('/[<>]/', $this->value, -1, PREG_SPLIT_NO_EMPTY);
		$values = is_array($this->value) ? $this->value : preg_split('/[<>]/', $this->value, null, PREG_SPLIT_NO_EMPTY);
		foreach ($values as $key => $value)
		{
			if (isset($arr[$value])) $values[$key] = $arr[$value];
		}

		if (empty($values))
		{
			$tpl->TouchBlock('view_empty');
		}
		else
		{
			$cnt = 0;
			foreach ($arr as $name)
			{
				$cnt++;
				if (in_array($name, $values))
				{
					$tpl->SetCurrentBlock('yes');
					$tpl->SetVariable(array(
						'YES'	=> ''
					));
				}
				else
				{
					$tpl->SetCurrentBlock('no');
					$tpl->SetVariable('NO', '');

				}
				$tpl->SetVariable(array(
					'NAME'	=> $name
				));
				$tpl->ParseCurrentBlock();

				if ($cnt != count($arr)) $tpl->TouchBlock('view_separator');

				$tpl->SetCurrentBlock('view_row');
				$tpl->SetVariable(array(
					'ROW'	=> $name
				));
				$tpl->ParseCurrentBlock();
			}
			$tpl->SetCurrentBlock('view');
			$tpl->ParseCurrentBlock();
		}
		
		return $tpl->Get();
	}

	function RenderFormTplView()
	{
		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

		$value = is_array($this->value) ? $this->value : preg_split('/[<>]/', $this->value, null, PREG_SPLIT_NO_EMPTY);
		
		$tpl->SetCurrentBlock('form_prop_row');
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
					'NAME'		=> $this->name,
					'VALUE'		=> $key,
					'CAPTION'	=> $name,
					'CHECKED'	=> in_array($key, $value) ? ' checked="checked"' : null
				));
				$tpl->ParseCurrentBlock();
			}
		}

		$tpl->SetcurrentBlock('form');
		$tpl->SetVariable(array(
			'NAME'		=> $this->name,
			'PARAMS'	=> $this->RenderFormParams(),
		));
		$tpl->ParseCurrentBlock();
		return $tpl->Get();
	}

	function RenderFormHtmlView()
	{

	    $value = is_array($this->value) ? $this->value : preg_split('/[<>]/', $this->value, null, PREG_SPLIT_NO_EMPTY);
	    
		$rez = '<input type="hidden" name="'.$this->name.'" value="" />';
		if (Utils::IsArray($this->values))
		{
			foreach ($this->values as $key=>$name)
			{
				if (is_array($name))
				{
					$key = $name['id'];
					$name = $name['name'];
				}
				$checked = in_array($key, $value) ? ' checked="checked"' : null;
				$rez .= '<label><input type="checkbox" name="'.$this->name.'[]" value="'.$key.'" '.$checked.' '.$this->RenderFormParams().'/>'.$name.'</label><br />';
			}
		}
		return $rez;
	}

	function GetValues()
	{
		GLOBAL $Db,$App;

		switch ($this->objectType)
		{
			case 'def':
				$arr = $GLOBALS['LIST_'.strtoupper($this->objectName)];
				if (!Utils::IsArray($arr))
				{
					$App->RaiseError('Array <b>LIST_'.strtoupper($this->objectName).'</b> not found.');
				}
				break;

			case 'table':
				$arr = $Db->Select($this->cond,''," id,{$this->crossField} AS name", $this->objectName);
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

}

?>