<?php
/**
 * CLASS Calendar
 * @package lisk
 *
 */
class Calendar
{
	/**
	 * Current template file name
	 *
	 * @var string
	 */
	public $tplName = 'modules/calendar';
	
	/**
	 * Calendar link (href) on the date cell
	 *
	 * @var string
	 */
	public $link;

	/**
	 * Condition for the calendar select
	 *
	 * @var string
	 */
	public $cond;
	/**
	 * DB table for calendar select
	 *
	 * @var string
	 */
	public $table;

	/**
	 * Current Year
	 *
	 * @var integer
	 */
	public $year;
	/**
	 * Current month number
	 *
	 * @var integer
	 */
	public	$month;
	/**
	 * Current month
	 *
	 * @var integer
	 */
	public	$day;
		
	/**
	 * Number of days in the current month
	 *
	 * @var integer
	 */
	public $curMonthDays;
	
	/**
	 * Number of the day of the week 0-sunday 6-saturday
	 *
	 * @var integer
	 */
	public	$curMonthFirstDay;
		
	/**
	 * Current (selected) date YYYY-MM-DD
	 *
	 * @var string
	 */
	public $currentDate;
		
	/**
	 * containts information about actions
	 * (is there or not) for each day of the month
	 *
	 * @var array
	 */
	public $actions = array();
	
	/**
	 * List of months with short names
	 *
	 * @var array
	 */
	public $ListMonthShort = array(
		'01'	=> 'Jan',
		'02'	=> 'Feb',
		'03'	=> 'Mar',
		'04'	=> 'Apr',
		'05'	=> 'May',
		'06'	=> 'Jun',
		'07'	=> 'Jul',
		'08'	=> 'Aug',
		'09'	=> 'Sep',
		'10'	=> 'Oct',
		'11'	=> 'Nov',
		'12'	=> 'Dec'
	);
		
		
	/**
	 * @var string
	 */
	private $dateFieldName = 'date';
	
	/**
	 * Calendar class
	 *
	 * @param string $link calendar's href
	 * @param string $table select table name
	 * @param string $cond select cond
	 * @return Calendar
	 */
	function __construct($link, $table, $cond=null)
	{
		GLOBAL $App;
		
		$App->Load('list','type');
		
		$this->InitDate();
		$this->cond  = ($cond!=null) ? 'AND '.$cond : null;
		$this->table = $table;
		
		if (STATIC_URLS==false)
		{
			$pos = strpos($link, '?');
			$sufix = ($pos === false) ? '?' : '&';
			$this->link = $link.$sufix;
		}
		else
		{
			$this->link = $link;
		}
	}
	
	/**
	 * Initialize date.
	 *
	 */
	private function InitDate()
	{
		$this->year = (isset($_GET['year']) && strlen($_GET['year'])) ? $_GET['year'] : date('Y');
		if (isset($_POST['year']) && strlen($_POST['year'])) $this->year = $_POST['year'];
		
		$this->month = (isset($_GET['month']) && strlen($_GET['month']))? $_GET['month'] : date('m');
		if (isset($_POST['month']) && strlen($_POST['month'])) $this->month = $_POST['month'];
		
		$this->day = (isset($_GET['day']) && strlen($_GET['day'])) ? $_GET['day'] : date('d');
		if (isset($_POST['day']) && strlen($_POST['day'])) $this->day = $_POST['day'];
		
		$this->curMonthDays = strftime ('%d', mktime (0, 0, 0, $this->month + 1, 0, $this->year));
		$this->curMonthFirstDay = date ('w', mktime (0, 0, 0, $this->month + 0, 1, $this->year));
		
		$this->currentDate = $this->CurrentDate();
	}
	
	/**
	 * Set template file name
	 *
	 * @param string $tplName
	 */
	public function SetTplName($tplName)
	{
		$this->tplName = $tplName;
	}
	
	/**
	 * Returns current date YYYY-MM-DD
	 *
	 * @return string
	 */
	public function CurrentDate()
	{
		$day = (integer)$this->day;
		if ($day<10) $day = '0'.$day;
		
		$month = (integer) $this->month;
		if ($month<10) $month = '0'.$month;
		
		return "{$this->year}-$month-$day";
	}
	
	/**
	 * Make Select for actions and fill in $this->actions
	 *
	 */
	public function SelectValues()
	{
		GLOBAL $Db;
		$sql="SELECT id, SUBSTRING({$this->dateFieldName}, 9, 2) as day
		FROM {$this->table}
		WHERE SUBSTRING({$this->dateFieldName}, 1, 7)='{$this->year}-{$this->month}' {$this->cond}";
		$rows=$Db->Query($sql);
		if (Utils::IsArray($rows))
		{
			foreach ($rows as $row)
			{
				$this->actions[] = (integer) $row['day'];
			}
		}
	}
	
	public function SetDateFieldName($fieldName)
	{
		$this->dateFieldName = $fieldName;
	}
	
	/**
	 * Returns css class name based on date
	 *
	 * @param string $d
	 * @return string
	 */
	protected function GetClass($d)
	{
		$d = (integer) $d;
		$class = in_array($d, $this->actions) ? 'calendar_td1' : 'calendar_td2';
		
		if ($d == $this->day) $class = 'calendar_td_cur';
		
		return $class;
	}
	
	/**
	 * Based on STATIC_URLS const format calendar link
	 *
	 * @param string $year
	 * @param string $month
	 * @param string $day
	 * @return string
	 */
	protected function FormatLink($year,$month,$day)
	{
		if (STATIC_URLS==true)
		{
			return $this->link.$year.'/'.$month.'/'.$day.'/';
		}
		else
		{
			return $this->link.'year='.$year.'&month='.$month.'&day='.$day;
		}
	}
	
	/**
	 * Render Calendar
	 *
	 * @return HTML
	 */
	public function Render()
	{
		GLOBAL $LIST_MONTH;
		
		$this->SelectValues();
		
		$Tpl = new Template();
		$Tpl->Load($this->tplName);

		$emptyBegin = $this->curMonthFirstDay;
		$beginDays = 7 - $this->curMonthFirstDay;
		$days2 = $this->curMonthDays - $beginDays;
		$endDays = $days2 % 7;
		$fullWeeks = ($days2 - $endDays) / 7;
		$emptyEnd = 7 - $endDays;
	
		// prasing empty blocks before the 1st day
		for ($i=0; $i<$emptyBegin; $i++)
		{
			$Tpl->parseVariable(array(
				'DATE'	=> ' ',
				'CLASS'	=> 'calendar_td_empty'
			),'column');
		}

		// Parse begin days
		$d = 0;
		for ($i;$i<7;$i++)
		{
			$d++;
			$Tpl->parseVariable(array(
				'DATE'	=> $d,
				'LINK'	=> $this->FormatLink($this->year, $this->month, $d),
				'CLASS'	=> $this->GetClass($d)
			),'column');
		}
		$Tpl->SetCurrentBlock('row');
		$Tpl->ParseCurrentBlock();
		
		// parse full weeks
		for ($j=0; $j<$fullWeeks; $j++)
		{
			for ($i=0;$i<7;$i++)
			{
				$d++;
				$Tpl->ParseVariable(array(
					'DATE'	=> $d,
					'LINK'	=> $this->FormatLink($this->year, $this->month, $d),
					'CLASS'	=> $this->GetClass($d)
				),'column');
			}
			$Tpl->SetCurrentBlock('row');
			$Tpl->ParseCurrentBlock();
		}
	
		// Parse end days
		for ($i=0; $i<$endDays; $i++)
		{
			$d++;
			$Tpl->ParseVariable(array(
				'DATE'	=> $d,
				'LINK'	=> $this->FormatLink($this->year, $this->month, $d),
				'CLASS'	=> $this->GetClass($d)
			),'column');
		}
	
		// Parse white blocks after the last day
		for ($i=0; $i<$emptyEnd; $i++)
		{
			$Tpl->ParseVariable(array(
				'DATE'	=> ' ',
				'CLASS'	=> 'calendar_td_empty'
			),'column');
		}
		
		$Tpl->SetCurrentBlock('row');
		$Tpl->ParseCurrentBlock();
		
		$next_month_name = '';
		$prev_month_name = '';
		
		if ($this->month == 12)
		{
			$next_month_name = $this->ListMonthShort['01'];
			$next_month_link = $this->FormatLink($this->year+1, '01', '1');
		}
		else
		{
			$next_month = $this->month + 1;
			if ($next_month<10) $next_month = '0'.$next_month;
	
			$next_month_link = $this->FormatLink($this->year, $next_month, '1');
			$next_month_name = $this->ListMonthShort[$next_month];
		}
		
		if ($this->month == 1)
		{
			$prev_month_name = $this->ListMonthShort['12'];
			$prev_month_link = $this->FormatLink($this->year-1, '12', '1');
		}
		else
		{
			$prev_month = $this->month - 1;
			if ($prev_month<10) $prev_month = '0'.$prev_month;
	
			$prev_month_link = $this->FormatLink($this->year, $prev_month, '1');
			$prev_month_name = $this->ListMonthShort[$prev_month];
		}
	
		$Tpl->SetVariable(array(
			'FORM_ACTION'		=> $this->link,
			'PREV_MONTH_LINK'	=> $prev_month_link,
			'NEXT_MONTH_LINK'	=> $next_month_link,
			'CUR_MONTH_NAME'	=> $LIST_MONTH[$this->month],
			'CUR_MONTH_NAME_SHORT'	=> $this->ListMonthShort[$this->month],
			'CUR_YEAR'			=> $this->year,
			'CUR_DAY'			=> $this->day,
			'NEXT_MONTH_NAME'	=> $next_month_name,
			'PREV_MONTH_NAME'	=> $prev_month_name,
			'FIELD_YEAR'		=> $this->RenderYearField(),
			'FIELD_MONTH'		=> $this->RenderMonthField(),
			'FIELD_DAY'			=> $this->RenderDayField()
		));
		
		$Tpl->SetCurrentBlock('calendar');
		$Tpl->ParseCurrentBlock();
	
		$rez=$Tpl->get();
		return $rez;
	}
	
	private function RenderYearField()
	{
		$rows_year = range($this->year-10, $this->year+10);
		$rows_year2 = array();
		foreach($rows_year as $row)
		{
			$rows_year2[$row] = $row;
		}
		$info = array(
			'object'	=> 'arr',
			'form'		=> 'class="calendar_inp_year"'
		);
		$List = new T_list($info);
		$List->name = 'year';
		$List->values = $rows_year2;
		$List->value = $this->year;
		return $List->RenderFormView();
	}
	
	private function RenderMonthField()
	{
		$info = array(
			'object'	=> 'arr',
			'form'		=> 'class="calendar_inp_month"'
		);
		$List = new T_list($info);
		$List->name = 'month';
		$List->values = $this->ListMonthShort;
		$List->value = $this->month;
		return $List->RenderFormView();
	}
	
	private function RenderDayField()
	{
		$rows_day = range(1,31);
		$rows_day2 = array();
		foreach ($rows_day as $row)
		{
			$rows_day2[$row] = $row;
		}
		$info=array(
			'object'	=> 'arr',
			'form'		=> 'class="calendar_inp_day"'
		);
		$List = new T_List($info);
		$List->name = 'day';
		$List->values = $rows_day2;
		$List->value = $this->day;
		return $List->RenderFormView();
	}
	
}

?>