<?php
/**
 * Lisk Type Wiki
 * @package lisk
 *
 */
class T_creole extends LiskType
{
	private $bcActive;
	private $bcHandler;
	private $bcField;
	private $bcSince;
	
	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		$this->type = LiskType::TYPE_CREOLE;
		$this->tplFile = 'type/creole';
		
		Application::LoadCss('[/]css/creole.css');
		Application::LoadJs('[/]js/lisk/type/creole.js');
		
		if (isset($info['backward_compatible']))
		{
			$this->bcHandler = $info['backward_compatible']['handler'];
			$this->bcField = isset($info['backward_compatible']['field']) ? $info['backward_compatible']['field'] : 'date';
			$this->bcSince = $info['backward_compatible']['since'];
			
			$this->bcActive = is_callable($this->bcHandler);
		}
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
	
	function RenderFormTplView()
	{
		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		$tpl->ParseVariable(array(
			'name'	=> $this->name,
			'value'	=> $this->value,
			'params'=> $this->RenderFormParams(),
			'asid' => $this->asId 
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
	    switch (strtoupper($param1))
		{
			case 'SRC':
				return $this->RenderSrcView();
				break;
		}
		
		//check for backward compatibility
		if ($this->bcActive)
		{
			$compareVal = strtotime($this->bcSince);
			$curVal = strtotime($this->dataItem->fields[$this->bcField]->value);
			//var_dump("$compareVal | {$this->bcSince}");
			//var_dump("$curVal | {$this->dataItem->fields[$this->bcField]->value}");
			if ($curVal <= $compareVal)
			{
				return call_user_func($this->bcHandler, $this->value);
			}
		}
		
		GLOBAL $App;
		$App->Load('creoleparser', 'utils');
		
		$creole = new creole(array('extension' => 'lisk_creole_extension_callback'));
		$html = $creole->parse($this->value);
		
		return '<div class="creole">'.$html.'</div>';
	}
	
	function RenderSrcView()
	{
		return $this->value;
	}
	
	public static function RenderOldWikiWay($value)
	{	
		GLOBAL $App;
		$App->Load('wikiparser', 'utils');
		$WP = new WikiParser($value);
		return $WP->Parse();
	}
}

?>