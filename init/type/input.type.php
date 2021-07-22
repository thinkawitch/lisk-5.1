<?php
/**
 * Lisk Type Input
 * @package lisk
 *
 */
class T_input extends LiskType
{
    private $stripTags;
    
	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		$this->type = LiskType::TYPE_INPUT;
		$this->tplFile = 'type/input';
		
		$this->stripTags = isset($info['strip_tags']) ? $info['strip_tags'] : true;
	}
	
	function Insert(&$values)
	{
		$value = isset($values[$this->name]) ? $values[$this->name] : null;
	    
		if ($value===null) return false;
		
	    return ($this->stripTags) ? strip_tags($value) : $value;
	}
	
	function Update(&$values)
	{
		$value = isset($values[$this->name]) ? $values[$this->name] : null;
	    
		if ($value===null) return false;
		
	    return ($this->stripTags) ? strip_tags($value) : $value;
	}

	function Delete(&$values)
	{
		return true;
	}

	function RenderFormView()
	{
        $safeValue = htmlentities($this->value, ENT_COMPAT, 'utf-8');
        
		switch ($this->formRender)
		{
			case 'tpl':
				$tpl = new Template();
				$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
				$tpl->ParseVariable(array(
					'NAME'      => $this->name,
					'VALUE'     => $safeValue,
					'PARAMS'    => $this->RenderFormParams(),
                    'SCRIPT'    => $this->autoSave ? $this->RenderAutoSaveScript() : '',
                    'AUTOSAVE'  => $this->autoSave ? " autosave='{$this->asId}' " : '',
				),'form');
				return $tpl->Get();
				break;
				
			default:
				return '<input type="text" name="'.$this->name.'" value="'.$safeValue.'" '.$this->RenderFormParams().' />';
				break;
		}
	}

	function RenderView($param1=null, $param2=null)
	{
		return $this->value;
	}
}

?>