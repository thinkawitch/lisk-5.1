<?php


$GLOBALS['LIST_STAT_ACTION_0'] = array(
	'' => '',
);


$GLOBALS['DATA_STATS_ACTIONS_SELECT'] = array(
	'fields' => array (
		'object' => array (
			'type' => 'list',
			'add_values' => array(
				'' => 'Please Select...'
			),
			'object' => 'def_stat_action_objects',
			'form' => array(
			    'id' => 'stats_object',
			    'onchange' => 'changeStatsActions()',
			),
		),
		'_action' => array (
			'type' => 'list',
			'name' => 'Action',
			'add_values' => array(
				'' => 'All...'
			),
			'object' => 'def_stat_action',
			'form' => array(
			    'id' => 'stats_action',
			),
		),
		'year' => array (
			'type' => 'list',
			'add_values' => array (
				'' => 'All...'
			),
			'object' => 'def_stats_years',
		),
		'month' => array (
			'type' => 'list',
			'add_values' => array (
				'' => 'All...'
			),
			'object' => 'def_month',
		),
		'day' => array (
			'type' => 'list',
			'add_values' => array (
				'' => 'All...'
			),
			'object' => 'def_stats_days',
		),
	),
);

$GLOBALS['DATA_STATS_ACTIONS'] = array (
	'table' => 'stat_actions',
	'order' => 'id DESC',
	'fields' => array (
		'id' => 'hidden',
		'object' => array (
			'type' => 'list',
			'object' => 'def_stat_action_objects',
		),
		' action' => array (
			'type' => 'list',
			'name' => 'Action',
			'add_values' => array(
				'' => 'All...'
			),
			'object' => 'def_stat_action',
		),
		'date' => 'datetime',
	),
	'list_fields'	=> 'object,action,date',
);

$GLOBALS['DATA_STATS_ACTIONS_REPORT'] = array (
	'fields' => array (
		'start_date' => array (
			'type' 			=> 'date',
			'max_year'		=> date('Y'),
			'min_year'		=> 2009
		),
		'end_date' => array (
			'type'			=> 'date',
			'max_year'		=> date('Y'),
			'min_year'		=> 2009
		),
		'stats' => 'hidden',
	),
);


$GLOBALS['LIST_STATS_REPORT_PERIOD'] = array (
	1	=> "1 day",
	2	=> "2 days",
	7	=> "week",
	14	=> "2 weeks",
	30	=> "month",
);


/**
 * Stats Actions for CMS
 *
 */
class CMSStatAction
{
	/**
	 * Current object
	 *
	 * @var const ACTION_OBJECT (int)
	 */
	public $object;
	/**
	 * Current Action
	 *
	 * @var const ACTION (int)
	 */
	public $action;
	public $year;
	public $month;
	public $day;
	
	private $chartFileSuffix;
	
	function __construct()
	{
	    $this->chartFileSuffix = '?unique='.uniqid('u');
	}
	
	/**
	 * Initizaliaze filter for render
	 *
	 * @param int $object
	 * @param int $action
	 * @param int $year
	 * @param int $month
	 * @param int $day
	 * @return false if no actions for period
	 */
	function InitFilter($object, $action, $year, $month, $day)
	{
		GLOBAL $Db, $DATA_STATS_ACTIONS, $DATA_STATS_ACTIONS_SELECT, $LIST_STATS_YEARS, $LIST_STATS_DAYS;
		
		// init this values
		$this->object = (int)$object; // selected object
		$this->action = (int)$action; // selected action
		$this->year = (int)$year; // selected year
		$this->month = (int)$month; // selected month
		$this->day = (int)$day; // selected day
		
		// form global data definitions for selected object
		$DATA_STATS_ACTIONS_SELECT['fields']['_action']['object'] = 'def_stat_action_' . $this->object;
		$DATA_STATS_ACTIONS['fields']['action']['object'] = 'def_stat_action_' . $this->object;
		
		// finding min & max year for year list field
		$rows = $Db->query("SELECT MIN(SUBSTRING(date, 1, 4)) as min_year, MAX(SUBSTRING(date, 1, 4)) AS max_year FROM stat_actions");
		
		// if no actions in database return false
		if (!$rows) return false;
		
		// building years list for year field
		$min_year = 0 + $rows[0]['min_year'];
		$max_year = 0 + $rows[0]['max_year'];
		$LIST_STATS_YEARS = array ();
		$array = range($min_year, $max_year);
		if (is_array($array) && count($array)==1) $array = array(date('Y'));
		foreach ($array as $a) $LIST_STATS_YEARS[$a] = $a;
			
		// building days list for day field
		$LIST_STATS_DAYS = array ();
		$array = range(1, 31);
		foreach ($array as $a)
		{
			$a = sprintf('%02d', $a);
			$LIST_STATS_DAYS[$a] = $a;
		}
		
		return true;
	}

	/**
	 * Render Stats Actions with graphic
	 *
	 * @return html result
	 */
	function Render()
	{
		GLOBAL $Parser, $Db, $App, $Auth, $LIST_STAT_ACTION_OBJECTS;
		
		// building array of all objects & actions for javascript for object field
		$result = array();
		foreach (array_keys($LIST_STAT_ACTION_OBJECTS) as $key)
		{
			$result1 = array ();
			foreach ($GLOBALS['LIST_STAT_ACTION_' . $key] as $key1 => $action)
			{
			    $action = preg_replace('/[\r\n]/', ' ', $action);
			    $action = str_replace("'", '', $action);
				$result1[] = "[$key1, '$action']";
			}
			$result[] = "[$key, [" . implode(', ', $result1) . ']]';
		}
		$sactions = "[[0, [[0, 'All...']]], " . implode(', ', $result) . ']';
		
		// forming titles for chart
		//$objectTitle = @$LIST_STAT_ACTION_OBJECTS[$this->object];
		//$actionTitle = @$GLOBALS['LIST_STAT_ACTION_' . $this->object][$this->action];
		
		$data = Data::Create('stats_actions_select');
		
		$data->value = array (
			'object'	=> $this->object,
			'_action'	=> $this->action,
			'year'		=> $this->year,
			'month'		=> $this->month,
			'day'		=> $this->day,
		);

		$year	= sprintf('%04d', $this->year);
		$month	= sprintf('%02d', $this->month);
		$day	= sprintf('%02d', $this->day);
		
		$cond = 'object = '.$this->object;
		if ($this->action) $cond .= ' AND action = '.$this->action;
		
		// building query for different year / month / day
		if ($this->day)
		{
			$query = "SELECT SUM(quantity) AS stats, SUBSTRING(date, 12, 2) AS hour, SUBSTRING(date, 1, 13) AS subdate FROM stat_actions WHERE $cond AND SUBSTRING(date, 1, 10) = '$year-$month-$day' GROUP BY subdate";
		}
		elseif ($this->month)
		{
			$query = "SELECT SUM(quantity) AS stats, SUBSTRING(date, 9, 2) AS day, SUBSTRING(date, 1, 10) AS subdate FROM stat_actions WHERE $cond AND SUBSTRING(date, 1, 7) = '$year-$month' GROUP BY subdate";
		}
		elseif ($this->year)
		{
			$query = "SELECT SUM(quantity) AS stats, SUBSTRING(date, 6, 2) AS month, SUBSTRING(date, 1, 7) AS subdate FROM stat_actions WHERE $cond AND SUBSTRING(date, 1, 4) = '$year' GROUP BY subdate";
		}
		else
		{
			$query = "SELECT SUM(quantity) AS stats, SUBSTRING(date, 1, 4) AS year, SUBSTRING(date, 1, 4) AS subdate FROM stat_actions WHERE $cond GROUP BY subdate";
		}

		$values = $Db->Query($query);
		
		$chartValues = array();
		
		// forming final values for parse & chart
		$parseValues = array();
		if ($values)
		{
		    foreach ($values as $key => $value)
		    {
		        $matches = array();
    			// values for parsing
    			$subdate = '';
    			$url = "module_stat_action.php?object={$this->object}&_action={$this->action}";
    			if (preg_match('/^(\d\d\d\d)/', $value['subdate'], $matches))
    			{
    				$url .= "&year={$matches[1]}";
    				$subdate .= "<a href=\"{$url}\">{$matches[1]}</a>";
    			}
    			$matches = array();
    			if (preg_match('/^\d\d\d\d-(\d\d)/', $value['subdate'], $matches))
    			{
    				$url .= "&month={$matches[1]}";
    				$subdate .= " <a href=\"{$url}\">{$GLOBALS['LIST_MONTH'][$matches[1]]}</a>";
    			}
    			$matches = array();
    			if (preg_match('/^\d\d\d\d-\d\d-(\d\d)/', $value['subdate'], $matches))
    			{
    				$url .= "&day={$matches[1]}";
    				$subdate .= " <a href=\"{$url}\">{$matches[1]}</a>";
    			}
    			$matches = array();
    			if (preg_match('/^\d\d\d\d-\d\d-\d\d (\d\d)/', $value['subdate'], $matches))
    			{
    				$url .= "&day={$matches[1]}";
    				$subdate .= " {$matches[1]} h";
    			}
    			$value['subdate'] = $subdate;
    			$parseValues[] = $value;
    			
    			// building values for chart
    			$chartValue = array ();
    			if (isset($value['year']) && $value['year']) $chartValue['x'] = $value['year'];
    			if (isset($value['month']) && $value['month']) $chartValue['x'] = $GLOBALS['LIST_MONTH'][$value['month']];
    			if (isset($value['day']) && $value['day']) $chartValue['x'] = $value['day'];
    			if (isset($value['hour']) && $value['hour']) $chartValue['x'] = $GLOBALS['LIST_HOURS'][(int)$value['hour']];
    			
    			$chartValue['y'] = $value['stats'];
    			$chartValues[] = $chartValue;
		    }
		}
		
		foreach ($chartValues as $key => $value) $values1[$value['x']] = $value['y'];
		
		if (isset($values[0]['hour']))
		{
			$chartValues = array();
			for ($i = 0; $i < 24; $i ++)
			{
				$label = $GLOBALS['LIST_HOURS'][$i];
				$chartValues[] = array(
					'x' => $label,
					'y' => isset($values1[$label]) ? (int)$values1[$label] : 0
				);
			}
		}
		elseif (isset($values[0]['day']))
		{
			$chartValues = array();
			for ($i = 1; $i < 31; $i ++)
			{
				$label = sprintf('%02d', $i);
				$chartValues[] = array(
					'x' => $label,
					'y' => isset($values1[$label]) ? (int)$values1[$label] : 0
				);
			}
		}
		

		// parsing values
		$result = array();
		$result['filter'] = $Parser->MakeView(array ('sactions' => $sactions), 'modules/stat_action/stat_action', 'script');
		$result['filter'] .= $Parser->MakeDynamicForm($data, 'modules/stat_action/stat_action');
		
		// if not null object selected then build chart & stats list
		if ($this->object)
		{
			// Building a Chart
			$width = 560;
			$height = 300;
			$filename = 'files/chart/'.$Auth->user['id'].'.png';
			
			$App->Load('libchart', 'mod');
		    Libchart::Prepare();
		    
		    $chart = new VerticalBarChart($width, $height);
		    
		    $dataSet = new XYDataSet();
		    foreach($chartValues as $value)
    	    {
    			$dataSet->addPoint(new Point($value['x'], $value['y']));
    		}
        	$chart->setDataSet($dataSet);
        	
        	$chart->setTitle('Date / Actions');
    	    $chart->render($App->sysRoot.$filename);
    	    chmod($App->sysRoot.$filename, 0666);
    	
        	Libchart::Restore();
        	
            $result['chart'] = "<img src=\"{$App->httpRoot}{$filename}{$this->chartFileSuffix}\" width=\"$width\" height=\"$height\" />";

            $Parser->SetListDecoration('ListTD1', 'ListTD2');
            $result['list'] = $Parser->MakeList($parseValues, 'modules/stat_action/list', 'cms_list');
		}
		
		// return
		return $Parser->MakeView($result, 'modules/stat_action/stat_action', 'default');
	}
	
	/**
	 * Render actions report
	 *
	 * @param $_POST $values
	 * @return html result
	 */
	function RenderReport($values=null, $renderForm=true)
	{
		GLOBAL $Parser, $Db, $DATA_STATS_ACTIONS_REPORT, $LIST_STAT_ACTION_OBJECTS;
		
		foreach ($LIST_STAT_ACTION_OBJECTS as $key => $object)
		{
			$DATA_STATS_ACTIONS_REPORT['fields']['object' . $key] = array(
				'type' => 'prop',
				'label' => $object,
				'object' => 'def_stat_action_' . $key
			);
		}
		
		$renderArray = array();
		
		$cond = '1';
		
		if (!$values)
		{
            $rows = $Db->Query('SELECT MIN(SUBSTRING(date, 1, 4)) as min_year, MAX(SUBSTRING(date, 1, 4)) AS max_year, MIN(date) AS start_date, MAX(date) AS end_date FROM stat_actions');
			if (!$rows) return false;
			
			$dataValues = array(
				'start_date'  => $rows[0]['start_date'],
				'end_date'    => $rows[0]['end_date']
			);

			$start_date = $rows[0]['start_date'];
			$end_date = $rows[0]['end_date'];
			
			
			//add all objects and actions to renderArray
			$objs = $GLOBALS['LIST_STAT_ACTION_OBJECTS'];
			foreach ($objs as $objK=>$objName)
			{
                $objActions = $GLOBALS['LIST_STAT_ACTION_'.$objK];
                foreach ($objActions as $aK=>$aN)
                {
                    $renderArray[]=array(
						'object'	=> $objK,
						'action'	=> $aK
					);
                }
			}
		}
		else
		{
			$start_date = "{$values['start_date_year']}-{$values['start_date_month']}-{$values['start_date_day']}";
			$end_date = mktime(0,0,0,$values['end_date_month'],$values['end_date_day']+1,$values['end_date_year']);
			$end_date = date("Y-m-d", $end_date);

			$dataValues['start_date'] = $start_date;
			$dataValues['end_date'] = $end_date;

			$cond = array();
			foreach (array_keys($values) as $fieldName)
			{
				$matches = array();
				$checked = array();
			    if (preg_match('/^object(\d+)$/', $fieldName, $matches))
				{
				    $objKey = $matches[1];
				    
				    if (Utils::IsArray($values[$fieldName]))
				    {
				        foreach ($values[$fieldName] as $actionKey)
				        {
				            $checked[] = $actionKey;
				            $cond[] = "(object = $objKey AND action = $actionKey)";
				            
				            $renderArray[]=array(
    							'object'	=> $objKey,
    							'action'	=> $actionKey
    						);
				        }
				    }
					
					if ($checked) $dataValues[$fieldName] = '<' . implode('><', $checked) . '>';
				}
			}
			

			if (Utils::IsArray($cond)) $cond = '(' . implode(' OR ', $cond) . ') ';
			else $cond = '1';
		}
		
		$result = null;
        
		if ($renderForm)
		{
		    $data  = new Data('stats_actions_report');
		    $data->value = $dataValues;
		    $result['filter'] = $Parser->MakeDynamicForm($data, 'modules/stat_action/stat_action');
		}
		
		$query = "SELECT SUM(quantity) AS stats, `object`, `action` FROM stat_actions WHERE $cond AND `date` >= '$start_date' AND `date` <= '$end_date' GROUP BY (`object` * 100 + `action`)";
		$rows = $Db->Query($query);
		
		if (Utils::IsArray($renderArray))
		{
    		foreach ($renderArray as $key=>$row)
    		{
    			$count = 0;
    			if (Utils::IsArray($rows))
    			{
    				foreach ($rows as $row2)
    				{
    					if ($row['object']==$row2['object'] && $row['action']==$row2['action'])
    					{
    						$count = $row2['stats'];
    					}
    				}
    			}
    			$renderArray[$key]['stats'] = $count;
    			$renderArray[$key]['object'] = $LIST_STAT_ACTION_OBJECTS[$row['object']];
    			$renderArray[$key]['action'] = $GLOBALS['LIST_STAT_ACTION_' . $row['object']][$row['action']];
    		}
		}
			
		$rows = $renderArray;
		
		$Parser->SetListDecoration('ListTD1', 'ListTD2');
		if ($rows) $result['list'] = $Parser->MakeList($rows, 'modules/stat_action/report_list', 'list1');
		
		
		return $result;
	}
	
}

?>