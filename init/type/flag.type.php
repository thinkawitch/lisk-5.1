<?php
/**
 * Lisk Type Flag
 * @package lisk
 *
 */
class T_flag extends LiskType
{

	public $defaultChecked;

	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		
		$this->defaultChecked = isset($info['default_checked']) ? $info['default_checked'] : false;
		
		$this->type = LiskType::TYPE_FLAG;
		$this->tplFile = 'type/flag';
	}

	function Insert(&$values)
	{
	    $fieldValue = @$values[$this->name];
	    $fieldChecked = @$values[$this->name.'_checked'];
	    
		return $fieldValue == '' ? ($fieldChecked ? 1 : 0 ) : $fieldValue;
	}
	
	function Update(&$values)
	{
		$fieldValue = @$values[$this->name];
	    $fieldChecked = @$values[$this->name.'_checked'];
	    
		return $fieldValue == '' ? ($fieldChecked ? 1 : 0 ) : $fieldValue;
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
				$checked = ($this->value>0) ? ' checked="checked"' : '';
				if ($this->defaultChecked && $this->value=='')
				{
					$checked = ' checked="checked"';
				}
				$params = $this->RenderFormParams();
				return <<<EOD
<input type="hidden" name="{$this->name}" value="" />
<input type="checkbox" name="{$this->name}_checked" value="1" {$params} {$checked} />
EOD;
				break;
		}
	}

	function RenderFormTplView()
	{
		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

		$checked = ($this->value>0) ? ' checked="checked"' : '';
		if ($this->defaultChecked && $this->value=='')
		{
			$checked = ' checked="checked"';
		}

		$tpl->ParseVariable(array(
			'NAME'		=> $this->name,
			'CHECKED'	=> $checked,
			'PARAMS'	=> $this->RenderFormParams(),
			'SCRIPT'    => $this->autoSave ? $this->RenderAutoSaveScript() : '',
            'AUTOSAVE'  => $this->autoSave ? " autosave='{$this->asId}' " : '',
			),'form');
		$tpl->ParseCurrentBlock();
		
		return $tpl->Get();
	}

	function RenderView($param1=null, $param2=null)
	{
		switch ($this->formRender)
		{
			case 'tpl':
				$tpl = new Template();
		        $tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
				$blockName = ($this->value>0) ? 'view_yes' : 'view_no';
				$tpl->TouchBlock($blockName);
				return $tpl->Get();
				break;
				
			default:
				return ($this->value>0) ? 'On' : 'Off';
				break;
		}
	}

}
?>