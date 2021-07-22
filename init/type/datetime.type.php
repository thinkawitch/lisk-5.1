<?php
/**
 * Lisk Type Datetime
 * @package lisk
 *
 */
class T_datetime extends LiskType
{
	public $format;
	public $yearRange;
	public $minYear;
	public $maxYear;

	function __construct(array $info, Data $dataItem)
	{
		parent::__construct($info, $dataItem);

		$this->format = isset($info['format']) ? $info['format']: null;
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

		$this->type = LiskType::TYPE_DATETIME;
		$this->tplFile = 'type/datetime';
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
	    if (isset($hash[$name.'_year']) && isset($hash[$name.'_month']) && isset($hash[$name.'_day'])
	       && isset($hash[$name.'_hour']) && isset($hash[$name.'_minute']))
	    {
	        return $hash[$name.'_year'].'-'.$hash[$name.'_month'].'-'.$hash[$name.'_day'].' '.$hash[$name.'_hour'].':'.$hash[$name.'_minute'].':00';
	    }
	    else return '';
	}

	public function Insert(&$values)
	{
		$rez = self::GetValueFromHash($this->name, $values);
	    if (!strlen($rez) && isset($values[$this->name])) $rez = $values[$this->name];

		return $rez;
	}

	public function Update(&$values)
	{
		$rez = self::GetValueFromHash($this->name, $values);
	    if (!strlen($rez) && isset($values[$this->name])) $rez = $values[$this->name];

		return $rez;
	}

	public function Delete(&$values)
	{
		return true;
	}

	public function RenderFormView()
	{
		GLOBAL $App;
		
		$App->Load('date', 'type');
		$App->Load('time', 'type');
		
		$date = new T_date(array(
			'name'		=> $this->name,
			'params'	=> $this->RenderFormParams(),
			'min_year' => $this->minYear,
			'max_year' => $this->maxYear,
		), $this->dataItem);
		$date->value = $this->value;
		$date = $date->RenderFormTplView();
		
		
		$time = new T_time(array(
			'name'		=> $this->name,
			'params'	=> $this->RenderFormParams(),
		), $this->dataItem);
		$time->value = $this->value;
		$time = $time->RenderFormTplView();

		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		$tpl->ParseVariable(array(
			'date'	=> $date,
			'time'	=> $time
		), 'form');
		return $tpl->Get();
	}

	public function RenderView($param1=null, $param2=null)
	{
		if (!strlen($this->value)) $this->value = date('Y-m-d H:i:s');
		return Format::DateTime($this->value, $this->format);
	}
}

?>