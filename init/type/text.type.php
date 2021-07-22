<?php
/**
 * Lisk Type Text
 * @package lisk
 *
 */
class T_text extends LiskType
{
	public $stripTags = true;
	
	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		$this->type = LiskType::TYPE_TEXT;
		$this->tplFile = 'type/text';
		
		if (isset($info['strip_tags'])) $this->stripTags = $info['strip_tags'];
	}

	function StripTags($value)
	{
		if ($this->stripTags) return strip_tags($value);
		else return $value;
	}

	function Insert(&$values)
	{
		return $this->StripTags(@$values[$this->name]);
	}
	
	function Update(&$values)
	{
		return $this->StripTags(@$values[$this->name]);
	}
	
	function Delete(&$values)
	{
		return true;
	}
	

	function RenderFormTplView()
	{
		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		$tpl->ParseVariable(array(
			'NAME'	=> $this->name,
			'VALUE'	=> $this->value,
			'PARAMS'=> $this->RenderFormParams(),
		    'SCRIPT'    => $this->autoSave ? $this->RenderAutoSaveScript() : '',
			'AUTOSAVE'  => $this->autoSave ? " autosave='{$this->asId}' " : '',
		), 'form');
		return $tpl->Get();
	}

	function RenderFormHtmlView()
	{
		return "<textarea rows=\"3\" name=\"{$this->name}\" ".$this->RenderFormParams()." cols=\"\">{$this->value}</textarea>";
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

	function RenderView($param1=null, $param2=null)
	{
		return ($this->stripTags) ? nl2br($this->value) : $this->value;
	}
}

?>