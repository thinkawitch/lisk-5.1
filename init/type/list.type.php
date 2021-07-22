<?php
/**
 * Lisk Type List
 * @package lisk
 *
 */
class T_list extends LiskType
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
		$this->listType		= (isset($info['object'])) ? $info['object'] : $App->RaiseError('List type unsetted.');

		$this->crossField	= isset($info['cross_field']) ? $info['cross_field'] : 'name';
		$this->cond			= isset($info['cond']) ? $info['cond'] : null;
		$this->addValues	= isset($info['add_values']) ? $info['add_values']: array();
		$this->autoJump		= isset($info['auto_jump']) ? $info['auto_jump']: null;

		if (is_array($this->listType))
		{
			$this->objectType = 'arr';
			$this->values = $this->listType;
		}
		elseif ($this->listType == 'arr')
		{
			$this->objectType = 'arr';
			$this->values = isset($info['values']) ? $info['values'] : array();
		}
		else
		{
			$matches = array();
			preg_match('/^([a-zA-Z]+)\_([a-zA-Z_0-9]+)$/', $this->listType, $matches);
			$this->objectType=$matches[1];
			$this->objectName=$matches[2];
		}

		$this->type = LiskType::TYPE_LIST;
		$this->tplFile = 'type/list';
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
		$this->InitAutoJump();

		$rez = "<select name=\"{$this->name}\" ".$this->RenderFormParams().'>';

		if (Utils::IsArray($this->values))
		{
			foreach ($this->values as $key=>$name)
			{
				if (is_array($name))
				{
					$key = $name['id'];
					$name = $name['name'];
				}

				$selected = ($key == $this->value && $this->value !== null) ? ' selected="selected"' : null;

				$rez .= "<option value=\"{$key}\" {$selected}>{$name}</option>";
			}
		}
		$rez .= '</select>';
		
		return $rez;
	}

	function RenderFormTplView()
	{
		$this->InitAutoJump();

		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

		$tpl->SetCurrentBlock('form_list_row');
		if (Utils::IsArray($this->values))
		{
			foreach ($this->values as $key=>$name)
			{
				if (is_array($name))
				{
					$key = $name['id'];
					$name = $name['name'];
				}

				$selected = ($key == $this->value && $this->value !== null) ? ' selected="selected"' : '';

				$tpl->SetVariable(array(
					'CAPTION'	=> $name,
					'VALUE'		=> $key,
					'SELECTED'	=> $selected,
				));
				$tpl->ParseCurrentBlock();
			}
		}

		$tpl->SetCurrentBlock('form');
		$tpl->SetVariable(array(
			'NAME'		=> $this->name,
			'PARAMS'	=> $this->RenderFormParams(),
            'SCRIPT'    => $this->autoSave ? $this->RenderAutoSaveScript() : '',
            'AUTOSAVE'  => $this->autoSave ? " autosave='{$this->asId}' " : '',
		));
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
					$str = substr($this->cond,7);
					$str = substr($str,0,-1);
					$condIds = preg_split('/[,]/', $str);
					$rez = array();
					foreach ($condIds as $key)
					{
						$rez[$key] = $arr[$key];
					}
					$arr = $rez;
				}
				$mergeMode = 'sum';
				if (!Utils::IsArray($arr))
				{
					$App->RaiseError('Array <b>LIST_'.strtoupper($this->objectName).'</b> not found.');
				}
				break;

			case 'table':
				$arr = $Db->Select($this->cond, null, "id,{$this->crossField} AS name", $this->objectName);
				$mergeMode = 'merge';
				break;

			case 'data':
				$di = Data::Create($this->objectName, false);
 				$arr = $Db->Select($this->cond, $di->order, "id,{$this->crossField} AS name", $di->table);
 				$mergeMode = 'merge';
 				break;

			case 'arr':
				$arr = $this->values;
				$mergeMode = 'sum';
				break;
		}

		if (Utils::IsArray($arr) && Utils::IsArray($this->addValues))
		{
			//skladivanie massivov ne peresozdaet cifrovie kluchi
			switch ($mergeMode)
			{
				case 'sum':
					$arr = $this->addValues + $arr;
					break;

				case 'merge':
					$arr = Utils::MergeArrays($this->addValues, $arr);
					break;
			}
		}
		if (!Utils::IsArray($arr) && Utils::IsArray($this->addValues))
		{
			$arr = $this->addValues;
		}
		
		$this->values = $arr;
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
					$errMsg = 'Array <b>LIST_'.strtoupper($this->objectName).'</b> not found.';
					$App->RaiseError($errMsg);
				}
				
				//combine arrays
				$newArr = array();
				if (Utils::IsArray($this->addValues)) $newArr = $this->addValues;
				foreach ($arr as $k=>$v) $newArr[$k] = $v;
				
				$arr = $newArr;
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
		
		return isset($arr[$this->value]) ? $arr[$this->value] : '';
	}

	private function InitAutoJump()
	{
		if ($this->autoJump!=null)
		{
			$this->AddFormParam('onchange', 'document.location=location.pathname + \''.$this->autoJump.'\' + this.value');
		}
	}

}

?>