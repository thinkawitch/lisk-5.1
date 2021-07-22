<?php
/**
 * CLASS Profiler
 * @package lisk
 *
 */
class LiskProfiler
{
	public $minTimeToLog			= 0;
	public $minSqlTimeToLogQueries	= 0;
	
	public $table					= 'sys_profiler';
	
	private $startTime;
	private $sqlTime				= 0;
	private $sqlQueries				= array();
	
	public function __construct()
	{
		$this->startTime = Utils::GetMicroTime();
	}
	
	public function Process()
	{
		GLOBAL $Db,$Auth;
		
		$userId = $Auth->isAuthorized ? $Auth->user['id'] : 0;
		
		$url = '/'.Navigation::GetCurUrl();
		
		$endTime = Utils::GetMicroTime();
		$totalTime = $endTime - $this->startTime;
		$renderTime = $totalTime - $this->sqlTime;
		
		//do not log if time is smaller than min. time
		if ($totalTime < $this->minTimeToLog) return;
		
		$Db->Insert(array(
			'date'			=> Format::DateTimeNow(),
			'pageurl'		=> $url,
			'total_time'	=> $totalTime,
			'render_time'	=> $renderTime,
			'sql_time'		=> $this->sqlTime,
			'user_id'		=> $userId,
			'sql_log'		=> $this->RenderSqlLog()
		),$this->table);
	}
	
	public function AddSqlQuery($sqlQuery,$executingTime,$error)
	{
		$this->sqlTime += $executingTime;
		
		$this->sqlQueries[] = array(
			'query'	=> $sqlQuery,
			'time'	=> $executingTime,
			'error'	=> $error
		);
	}
	
	private function RenderSqlLog()
	{
		$queriesTotal = sizeof($this->sqlQueries);
		
		if ($this->sqlTime < $this->minSqlTimeToLogQueries || $queriesTotal < 1) return '';
		
		$result = '<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#666666"><tr><td colspan="2" bgcolor="#EEEEEE">Queries: <strong>'.$queriesTotal.'</strong> | SQL Time: <strong>'.$this->sqlTime.'</strong></td></tr>';
		
		foreach ($this->sqlQueries as $row)
		{
			$result.= '<tr><td bgcolor="#FFFFFF">';
			$result.= $row['query'];
			if (strlen($row['error'])) $result.= '<br /><span style="color:#FF0000">'.$row['error'].'</span>';
			$result.= '</td><td width="1%" nowrap="nowrap" bgcolor="#EEEEEE">';
			$result.= $row['time'];
			$result.= '</td></tr>';
		}

		$result.= '</table>';
		
		return $result;
	}
}

?>