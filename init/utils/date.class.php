<?php
/**
 * CLASS DateContainer
 * @package lisk
 *
 *	naprimer, kogda nuzhno dobavit' k date 3 mesiaca ili 12 dnei
 *  $dc = DateContainer::CreateFromDbDate('2009-11-27');
 *  $dc->AddMonth(3);
 *  $dc->AddDay(12);
 */

class DateContainer
{
	public $year;
	public $month;
	public $day;
	public $hour;
	public $minute;
	public $second;
	
	const TYPE_DB_DATE = -1;
	const TYPE_TIMESTAMP = -2;

	function __construct($year, $month, $day=null, $hour=null, $minute=null, $second=null)
	{
	    switch ($year)
	    {
	            
	        case self::TYPE_DB_DATE:
	            $this->InitFromDbDate($month);
	            break;
	            
	        case self::TYPE_TIMESTAMP:
	            $this->InitFromTimestamp($month);
	            break;
	            
	        default:
	            $this->InitFull($year, $month, $day, $hour, $minute, $second);
	            break;
	    }
	}
	
	/**
	 * @param string $date
	 * @return DateContainer
	 */
	static function CreateFromDbDate($date)
	{
	    return new DateContainer(DateContainer::TYPE_DB_DATE, $date);
	}
	
	/**
	 * @param int $ts
	 * @return DateContainer
	 */
	static function CreateFromTimestamp($ts)
	{
	    return new DateContainer(DateContainer::TYPE_TIMESTAMP, $ts);
	}
	

	private function InitFromDbDate($strDate)
	{
		list($this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second) = sscanf($strDate, '%04d-%02d-%02d %02d:%02d:%02d');
	}

	private function InitFromTimestamp($time)
	{
		$dbDate = date('Y-m-d H:i:s', $time);
		$this->InitFromDbDate($dbDate);
	}

	private function InitFull($year, $month, $day, $hour, $minute, $second)
	{
		$time = mktime($hour, $minute, $second, $month, $day, $year);

		list($this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second)
			= sscanf(date('Y-m-d H:i:s', $time), '%04d-%02d-%02d %02d:%02d:%02d');
	}
	
    private function InitFromDc(DateContainer $dc)
	{
		$this->year = $dc->year;
		$this->month = $dc->month;
		$this->day = $dc->day;
		$this->hour = $dc->hour;
		$this->minute = $dc->minute;
		$this->second = $dc->second;
	}

	function IsObjectInCorrectFormat()
	{
		return $this->year>1970
			&& ($this->month>=1 && $this->month<=12)
			&& ($this->day>=1 && $this->day<=31)
			&& ($this->hour>=0 && $this->hour<=23)
			&& ($this->minute>=0 && $this->minute<=59)
			&& ($this->second>=0 && $this->second<=59);
	}


	function ToTimestamp()
	{
		return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
	}

	function ToDbDateTime()
	{
		$year = sprintf('%04d', $this->year);
		$month = sprintf('%02d', $this->month);
		$day = sprintf('%02d', $this->day);
		$hour = sprintf('%02d', $this->hour);
		$minute = sprintf('%02d', $this->minute);
		$second = sprintf('%02d', $this->second);

		return $year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second;
	}
	
	function ToDbDate()
	{
		$year = sprintf('%04d', $this->year);
		$month = sprintf('%02d', $this->month);
		$day = sprintf('%02d', $this->day);

		return $year.'-'.$month.'-'.$day;
	}

	function Duplicate()
	{
		return new DateContainer($this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second);
	}
	
	function AddSecond($second=1)
	{
		$this->second += $second;
		$obj = $this->Duplicate();
		
		$this->InitFromDc($obj);
	}
	
	function SubtractSecond($second=1)
	{
		$this->second -= $second;
		$obj = $this->Duplicate();
		
		$this->InitFromDc($obj);
	}
	
	function AddDay($day=1)
	{
		$this->day += $day;
		$obj = $this->Duplicate();
		
		$this->InitFromDc($obj);
	}
	
	function SubtractDay($day=1)
	{
		$this->day -= $day;
		$obj = $this->Duplicate();
		
		$this->InitFromDc($obj);
	}
	
    function AddMonth($month=1)
	{
		$this->month += $month;
		$obj = $this->Duplicate();
		
		$this->InitFromDc($obj);
	}
	
	function SubtractMonth($month=1)
	{
		$this->month -= $month;
		$obj = $this->Duplicate();
		
		$this->InitFromDc($obj);
	}
}

class DateUtils
{
	static function DbDateToTimestamp($str)
	{
		$dc = new DateContainer(DateContainer::TYPE_DB_DATE, $str);
		return $dc->ToTimestamp();
	}

	static function TimestampToDbDateTime($str)
	{
		$dc = new DateContainer(DateContainer::TYPE_TIMESTAMP, $str);
		return $dc->ToDbDateTime();
	}

	static function CompareDate(DateContainer $dc1, DateContainer $dc2)
	{
		return ($dc1->year == $dc2->year && $dc1->month == $dc2->month && $dc1->day == $dc2->day);
	}

	static function TimestampDifference(DateContainer $dc1, DateContainer $dc2)
	{
		return $dc1->ToTimestamp() - $dc2->ToTimestamp();
	}
	
	static function DaysDifference(DateContainer $dc1, DateContainer $dc2)
	{
	    return ($dc1->ToTimestamp() - $dc2->ToTimestamp()) / 86400;
	}
}

?>