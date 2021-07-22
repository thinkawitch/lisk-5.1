<?php
/**
 * Lisk Type Date
 * @package lisk
 *
 */

class T_date extends LiskType
{
	public $format;
		
	public $yearRange;
	public $minYear;
	public $maxYear;
		
	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		
		GLOBAL $App;
		$this->format = isset($info['format']) ? $info['format'] : $App->dateFormat;
		
		// initialize year range
		$this->yearRange = isset($info['year_range']) ? $info['year_range'] : 100;
		
		if (isset($info['min_year']) && isset($info['max_year']))
		{
			$this->minYear = $info['min_year'];
			$this->maxYear = $info['max_year'];
		}
		else
		{
		    $curYear = date('Y');
			$this->minYear = $curYear - $this->yearRange;
			$this->maxYear = $curYear + $this->yearRange;
		}
		
		$this->type = LiskType::TYPE_DATE;
		$this->tplFile = 'type/date';
	}
	
	/**
	 * get value from hash
	 *
	 * @param string $name
	 * @param array $hash
	 * @return string
	 */
	public static function GetValueFromHash($name, $hash)
	{
	    if (isset($hash[$name.'_year']) && isset($hash[$name.'_month']) && isset($hash[$name.'_day']))
	    {
	        return $hash[$name.'_year'].'-'.$hash[$name.'_month'].'-'.$hash[$name.'_day'];
	    }
	    else return '';
	}
	
	function Insert(&$values)
	{
	    $rez = self::GetValueFromHash($this->name, $values);
	    if (!strlen($rez) && isset($values[$this->name])) $rez = $values[$this->name];
		
		return $rez;
	}
	
	function Update(&$values)
	{
		$rez = self::GetValueFromHash($this->name, $values);
	    if (!strlen($rez) && isset($values[$this->name])) $rez = $values[$this->name];
	    
		return $rez;
	}

	function Delete(&$values)
	{
		return true;
	}
	
	
    public function Render($param1=null, $param2=null)
	{
		$param1 = strtoupper($param1);
		
		switch ($param1)
		{
			case 'FORM':
				return $this->RenderFormView();
				break;
				
			case 'INLINE':
				return $this->RenderInlineForm();
				break;
				
			default:
				return $this->RenderView($param1, $param2);
				break;
		}
	}
	
	
	function RenderFormView()
	{
		switch ($this->formRender)
		{
			case 'tpl':
				return $this->RenderFormTplView();
				break;
				
			default:
				return "<input  name=\"{$this->name}\" value=\"{$this->value}\" ".$this->RenderFormParams().' />';
				break;
		}
	}
	
    function RenderFormTplView()
	{
		$tpl = new Template();
	    $tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

		// get current time if value is null
		if (!isset($this->value)) $this->value = date('Y-m-d');
		$ts = strtotime($this->value);

		$value = array();
		$value['year'] = date('Y', $ts);
		$value['month'] = date('m', $ts);
		$value['day'] = date('d', $ts);

		$tpl->SetCurrentBlock('form_year_row');
		$range = range($this->minYear, $this->maxYear);
		foreach ($range as $year)
		{
			$tpl->SetVariable(array(
				'caption'	=> $year,
				'value'		=> $year,
				'selected'	=> (isset($value['year']) && $year==$value['year']) ? ' selected' : null
			));
			$tpl->ParseCurrentBlock();
		}
		
		$tpl->SetCurrentBlock('form_year');
		$tpl->SetVariable(array(
			'name'		=> $this->name,
			'params'	=> $this->RenderFormParams(),
		));
		$tpl->ParseCurrentBlock();
		
		//MONTH
		GLOBAL $LIST_MONTH;
		$tpl->SetCurrentBlock('form_month_row');
		foreach ($LIST_MONTH as $key=>$caption)
		{
			$tpl->SetVariable(array(
				'caption'	=> $caption,
				'value'		=> $key,
				'selected'	=> (isset($value['month']) && $key==$value['month']) ?  ' selected' : null
			));
			$tpl->ParseCurrentBlock();
		}
		
		$tpl->SetCurrentBlock('form_month');
		$tpl->SetVariable(array(
			'name'		=> $this->name,
			'params'	=> $this->RenderFormParams()
		));
		$tpl->ParseCurrentBlock();
		
		//DAY
		$tpl->SetCurrentBlock('form_day_row');
		$range = range(1, 31);
		foreach ($range as $day)
		{
			$day = sprintf('%02d', $day);
			$tpl->SetVariable(array(
				'caption'	=> $day,
				'value'		=> $day,
				'selected'	=> (isset($value['day']) && $day==$value['day']) ?  ' selected' : null
			));
			$tpl->ParseCurrentBlock();
		}
			
		$tpl->SetCurrentBlock('form_day');
		$tpl->SetVariable(array(
			'name'		=> $this->name,
			'params'	=> $this->RenderFormParams()
		));
		$tpl->ParseCurrentBlock();
			
		$tpl->SetCurrentBlock('form');
		$tpl->SetVariable(array(
			'value' => date('Y-m-d', $ts),
			'min_date' => $this->minYear.'-01-01',
			'max_date' => $this->maxYear.'-12-31',
			'name' => $this->name
		));
		$tpl->ParseCurrentBlock();
		
		return $tpl->Get();
	}
	
	function RenderInlineForm()
	{
	    $tpl = new Template();
	    $tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		
		// get current time if value is null
		if (!isset($this->value)) $this->value = date('Y-m-d');
		$ts = strtotime($this->value);

		$view = array(
		    'value' => date('Y-m-d', $ts),
			'min_date' => $this->minYear.'-01-01',
			'max_date' => $this->maxYear.'-12-31',
			'name' => $this->name
		);
		
		$tpl->SetCurrentBlock('inline');
		$tpl->SetVariable($view);
		$tpl->parseCurrentBlock();
		
		return $tpl->Get();
	}
	
	function RenderView($param1=null, $param2=null)
	{
		// get current date if value is null
		if (!isset($this->value)) $this->value = date('Y-m-d');
		return Format::Date($this->value, $this->format);
	}
}

?>