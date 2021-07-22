<?php
/**
 * Lisk Type Time
 * @package lisk
 *
 */
class T_time extends LiskType
{
	public $format;
	
	const H24_FORMAT = 1;
	const H12_FORMAT = 2;
	public $timeFormat;
	
	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		
		$this->format = (isset($info['format'])) ? $info['format'] : null;
		$this->timeFormat = (isset($info['12_hour_format'])) ? self::H12_FORMAT : self::H24_FORMAT;
		
		$this->type = LiskType::TYPE_TIME;
		$this->tplFile = 'type/time';
	}
	
	/**
	 * get value from hash
	 *
	 * @param string $name
	 * @param array $hash
	 * @return string
	 */
	public static function GetValueFromHash($name, $hash, $timeFormat=null)
	{
	    
	    if ($timeFormat==self::H12_FORMAT)
	    {
	        if (isset($hash[$name.'_hour']) && isset($hash[$name.'_minute']) && isset($hash[$name.'_suffix']))
    	    {
    	        if ($hash[$name.'_suffix'] == 'pm') $hash[$name.'_hour'] += 12;
    	        return $hash[$name.'_hour'].':'.$hash[$name.'_minute'].':00';
    	    }
	    }
	    else
	    {
            if (isset($hash[$name.'_hour']) && isset($hash[$name.'_minute']))
    	    {
    	        return $hash[$name.'_hour'].':'.$hash[$name.'_minute'].':00';
    	    }
	    }
	    
        return '';
	}

	function Insert(&$values)
	{
		$rez = self::GetValueFromHash($this->name, $values, $this->timeFormat);
	    if (!strlen($rez) && isset($values[$this->name])) $rez = $values[$this->name];
		
		return $rez;
	}
	
	function Update(&$values)
	{
		$rez = self::GetValueFromHash($this->name, $values, $this->timeFormat);
	    if (!strlen($rez) && isset($values[$this->name])) $rez = $values[$this->name];
		
		return $rez;
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
				return "<input type=\"text\" name=\"{$this->name}\" value=\"{$this->value}\" ".$this->RenderFormParams()." />";
				break;
		}
	}
	
	function RenderFormTplView()
	{
		if ($this->timeFormat == self::H12_FORMAT) return $this->RenderForm12();
		else return $this->RenderForm24();
	}
	
	function RenderForm24()
	{
	    GLOBAL $LIST_HOURS;

		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		
		if (!isset($this->value) || $this->value == '') $this->value = date('H:i');
		
		$value_time = strtotime($this->value);
		$value = array();
		$value['hour'] = date('H', $value_time);
		$value['minute'] = date('i', $value_time);
		
		//HOUR
		$tpl->SetCurrentBlock('form_hour_row');
		$range = $LIST_HOURS;
		foreach ($range as $hour=>$hour_name)
		{
			$hour = sprintf('%02d', $hour);
			$tpl->SetVariable(array(
				'CAPTION'	=> $hour_name,
				'VALUE'		=> $hour,
				'SELECTED'	=> ($hour == $value['hour']) ? ' selected' : null
			));
			$tpl->ParseCurrentBlock();
		}
		
		$tpl->SetCurrentBlock('form_hour');
		$tpl->SetVariable(array(
			'NAME'		=> $this->name,
			'PARAMS'	=> $this->RenderFormParams()
		));
		$tpl->ParseCurrentBlock();
		
		
		//MINUTE
		$tpl->SetCurrentBlock('form_minute_row');
		$range = range(0, 59);
		foreach ($range as $minute)
		{
			$minute = sprintf('%02d', $minute);
			$tpl->SetVariable(array(
				'CAPTION'	=> $minute,
				'VALUE'		=> $minute,
				'SELECTED'	=> ($minute == $value['minute']) ? ' selected': null
			));
			$tpl->ParseCurrentBlock();
		}
				
		$tpl->SetCurrentBlock('form_minute');
		$tpl->SetVariable(array(
			'NAME'		=> $this->name,
			'PARAMS'	=> $this->RenderFormParams(),
		));
		$tpl->ParseCurrentBlock();
		
		
		$tpl->setCurrentBlock('form');
		$tpl->setVariable(array(
			'NAME'		=> $this->name
		));
		$tpl->ParseCurrentBlock();
		
		return $tpl->Get();
	}
	
    function RenderForm12()
	{
	    GLOBAL $LIST_HOURS_12, $LIST_HOURS_12_SUFFIX;

		$tpl = new Template();
	    $tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		
		if (!isset($this->value) || $this->value == '') $this->value = date('H:i');
		
		$value_time = strtotime($this->value);
		$value = array();
		$value['hour'] = date('h', $value_time);
		$value['minute'] = date('i', $value_time);
		$value['suffix'] = date('a', $value_time);
		
		//HOUR
		$tpl->SetCurrentBlock('form_hour_row');
		$range = $LIST_HOURS_12;
		foreach ($range as $hour=>$hour_name)
		{
			$selected = null;
			if ($hour == $value['hour']) $selected = ' selected';
			if ($value['hour'] == 12 && $hour == 0) $selected = ' selected';
			$hour = sprintf('%02d', $hour);
			
			$tpl->SetVariable(array(
				'CAPTION'	=> $hour_name,
				'VALUE'		=> $hour,
				'SELECTED'	=> $selected,
			));
			$tpl->ParseCurrentBlock();
		}
		
		$tpl->SetCurrentBlock('form_hour');
		$tpl->SetVariable(array(
			'NAME'		=> $this->name,
			'PARAMS'	=> $this->RenderFormParams(),
		));
		$tpl->ParseCurrentBlock();
		
		
		//MINUTE
		$tpl->setCurrentBlock('form_minute_row');
		$range = range(0, 59);
		foreach ($range as $minute)
		{
			$minute = sprintf('%02d', $minute);
			$tpl->SetVariable(array(
				'CAPTION'	=> $minute,
				'VALUE'		=> $minute,
				'SELECTED'	=> ($minute == $value['minute']) ? ' selected': null
			));
			$tpl->ParseCurrentBlock();
		}
		
		//SUFFIX
		$tpl->SetCurrentBlock('form_suffix_row');
		$range = $LIST_HOURS_12_SUFFIX;
		foreach ($range as $sk=>$sn)
		{
			$tpl->SetVariable(array(
				'CAPTION'	=> $sn,
				'VALUE'		=> $sk,
				'SELECTED'	=> ($sk == $value['suffix']) ? ' selected' : null
			));
			$tpl->ParseCurrentBlock();
		}
		
		$tpl->SetCurrentBlock('form_suffix');
		$tpl->SetVariable(array(
			'NAME'		=> $this->name,
			'PARAMS'	=> $this->RenderFormParams(),
		));
		$tpl->ParseCurrentBlock();
				
		$tpl->SetCurrentBlock('form_minute');
		$tpl->SetVariable(array(
			'NAME'		=> $this->name,
			'PARAMS'	=> $this->RenderFormParams(),
		));
		$tpl->ParseCurrentBlock();
		
		$tpl->SetCurrentBlock('form');
		$tpl->SetVariable(array(
			'NAME'		=> $this->name
		));
		$tpl->ParseCurrentBlock();
		return $tpl->Get();
	}
	
	function RenderView($param1=null, $param2=null)
	{
		// get current time if value is null
		if (!isset($this->value) || $this->value == '') $this->value = date('H:i');
		
		return Format::Time($this->value, $this->format);
	}
	
}
?>