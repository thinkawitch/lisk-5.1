<?php
/**
 * Lisk Type Hidden
 * @package lisk
 *
 */
class T_hidden extends LiskType
{

	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		
		$this->type = LiskType::TYPE_HIDDEN;
		$this->tplFile = 'type/hidden';
	}

	
    function Insert(&$values)
	{
		return isset($values[$this->name]) ? $values[$this->name] : false;
	}
	
	function Update(&$values)
	{
		return isset($values[$this->name]) ? $values[$this->name] : false;
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
				$tpl = new Template();
		        $tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
				$tpl->ParseVariable(array(
					'NAME'	=> $this->name,
					'VALUE'	=> $this->value,
					'PARAMS' => $this->RenderFormParams(),
				), 'form');
				return $tpl->Get();
				break;
				
			default:
				return "<input type=\"hidden\" name=\"{$this->name}\" value=\"{$this->value}\" ".$this->RenderFormParams().' />';
				break;
		}
	}
	
	function RenderView($param1=null, $param2=null)
	{
		return $this->value;
	}
}

?>