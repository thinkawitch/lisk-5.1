<?php
/**
 * Lisk Type Wiki
 * @package lisk
 *
 */
class T_wiki extends LiskType
{
	public $stripTags = true;
	
	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		$this->type = LiskType::TYPE_WIKI;
		$this->tplFile = 'type/wiki';
		
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
			'PARAMS'=> $this->RenderFormParams()
		), 'form');
		return $tpl->Get();
	}

	function RenderFormHtmlView()
	{
		return "<textarea name=\"{$this->name}\" rows=\"3\" cols=\"\" ".$this->RenderFormParams().">{$this->value}</textarea>";
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
	    switch ($param1)
		{
			case 'SRC':
				return $this->RenderSrcView();
				break;
		}
		
		GLOBAL $App;
		$App->Load('wikiparser', 'utils');
		$WP = new WikiParser($this->value);
		return $WP->Parse();
	}
	
	function RenderSrcView()
	{
		return $this->value;
	}
}

?>