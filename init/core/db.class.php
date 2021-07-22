<?php
/**
 * CLASS Database
 * @package lisk
 *
 */
class Database
{
	public $table;
	public $dbname;

	private $locked;
	private $limit;
	private $dbLink;

	private $isConnected;
	
	function __construct($host=null, $user=null, $password=null, $dbName=null)
	{
		GLOBAL $App;
		
		$this->locked = array();
		$this->limit = false;
		$this->isConnected = false;
		
		if (!strlen($host)) $host = $App->sqlHost;
		if (!strlen($user)) $user = $App->sqlUser;
		if (!strlen($password)) $password = $App->sqlPassword;
		if (!strlen($dbName)) $dbName = $App->sqlDbname;
		
		$this->Connect($host, $user, $password, $dbName);
	}

	function __destruct()
	{
		$this->Disconnect();
	}

	/**
	* @return void
	* @param string $dbname
	* @desc connect to db
	*/
	//sys
	private function Connect($host, $user, $password, $dbName)
	{
		GLOBAL $App;
		$this->dbLink = mysql_connect($host, $user, $password, true);
		if (!$this->dbLink) $App->RaiseError('Could not connect: '.mysql_error());
		if (!$this->SetDBName($dbName)) $App->RaiseError('Could not open database '.$dbName);
		
		// the old way
	    //$this->MysqlQuery('SET names utf8');
	    
		// the new way, as of mysql 5.0.7
		mysql_set_charset('utf8', $this->dbLink);
		
		$this->isConnected = true;
	}

	/**
	 * Execute query
	 *
	 * @param string $sql
	 * @return resource
	 */
	public function MysqlQuery($sql)
	{
		$startTime = Utils::GetMicroTime();

		$error = null;
		$res = mysql_query($sql, $this->dbLink);
		if(!$res) $error = mysql_error($this->dbLink);

		$this->Debug($sql, sprintf('%0.5f', Utils::GetMicroTime()-$startTime), $error);

		return $res;
	}

	/**
	 * Add debug record
	 *
	 * @param string $sqlQuery
	 * @param float $executingTime
	 * @param string $error
	 */
	private function Debug($sqlQuery, $executingTime, $error)
	{
		GLOBAL $App,$Debug;
		if ($App->debug) $Debug->AddDebug('SQL', $sqlQuery, $executingTime, $error);
		
		if (defined('LISK_PROFILER') && LISK_PROFILER)
		{
			GLOBAL $Profiler;
			$Profiler->AddSqlQuery($sqlQuery, $executingTime, $error);
		}
	}


	/**
	* @return boolean
	* @param string $dbname
	* @desc Process DB error
	*/
	public function SetDBName($dbname)
	{
		$startTime = Utils::GetMicroTime();

		$error = null;
		$res = mysql_select_db($dbname, $this->dbLink);
		if(!$res) $error = mysql_error($this->dbLink);

		$this->dbname = $dbname;

		$this->Debug('SET DATABASE \''.$dbname.'\'', sprintf('%0.5f', Utils::GetMicroTime()-$startTime), $error);

		if (!$res) return false;

		return true;
	}

	/**
	* @return void
	* @param string $table
	* @desc Set current Table
	*/
	public function SetTable($table)
	{
		$this->table = $table;
	}



	/**
	* @return int
	* @param string $table
	* @desc get table next autoincrement
	*/
	public function GetAutoIncrement($table=null)
	{
		if ($table==null) $table = $this->table;

		$rows = $this->Query('SHOW TABLE STATUS LIKE \''.$table.'\'');
		return $rows[0]['Auto_increment'];
	}

	/**
	* @return boolean
	* @param mixed $tables
	* @desc lock tables
	*/
	//sys
	public function Lock($tables)
	{
		$locked = $this->locked;
		if (!is_array($tables)) $tables = explode(',', $tables);

		if (is_array($tables))
		{
			$tableList = '';
			foreach ($tables as $table)
			{
				if ($tableList != '') $tableList = ',';
				$tableList .= ' '.$table.' WRITE';
				if (!in_array($table, $this->locked)) $locked[] = $table;
			}
		}
		else
		{
			$tableList = ' '.$tables.' WRITE';
			if (!in_array($tables, $this->locked)) $locked[] = $tables;
		}

		//execute
		$res = $this->MysqlQuery('LOCK TABLES '.$tableList);

		if (!$res) return false;

		$this->locked = $locked;
		return true;
	}

	/**
	* @return boolean
	* @desc unlock tables
	*/
	public function Unlock()
	{
		if (!Utils::IsArray($this->locked)) return true;

		//execute
		$res = $this->MysqlQuery('UNLOCK TABLES');

		if (!$res) return false;

		$this->locked = array();
		return true;
	}

	/**
	* @param array $params
	* @param string $table
	* @desc insert record
	*/
	public function Insert($params, $table=null)
	{
		//select table
		if ($table == null) $table = $this->table;

		// build sql
		$sql = 'INSERT INTO '.$table.' SET ';
		foreach ($params as $field=>$value)
		{
			$quotes = true;
			// do not take $value in ' ' if it's sql
			if (substr($value, 0, 4) == 'sql:') $quotes = false;
			
			if ($quotes)
			{
			    $value = mysql_real_escape_string($value, $this->dbLink);
				$sql .= '`'.$field.'`=\''.$value.'\',';
			}
			else
			{
				$value = substr($value, 4);
				$sql .= '`'.$field.'`='.$value.',';
			}
		}
		$sql = substr($sql, 0, -1);

		//execute
		$res = $this->MysqlQuery($sql);

		if (!$res) return false;
		
		return mysql_insert_id($this->dbLink);
	}

	/**
	* @param string $cond
	* @param array $params
	* @param string $table
	* @desc update records
	*/
	public function Update($cond, $params, $table=null)
	{
		//select table
		if ($table == null) $table = $this->table;


		//build sql
		$sql = 'UPDATE '.$table.' SET ';
		foreach ($params as $field=>$value)
		{
			$quotes = true;
			// do not take $value in ' ' if it's sql
			if (substr($value, 0, 4) == 'sql:') $quotes = false;
			
		    if ($quotes)
			{
			    $value = mysql_real_escape_string($value, $this->dbLink);
				$sql .= '`'.$field.'`=\''.$value.'\',';
			}
			else
			{
				$value = substr($value, 4);
				$sql .= '`'.$field.'`='.$value.',';
			}
		}

		$sql = substr($sql, 0, -1);
		if ($cond != '') $sql .= ' WHERE '.$cond;

		//execute
		$res = $this->MysqlQuery($sql);

		if (!$res) return false;
        else return true;
	}

	/**
	* @param string $cond
	* @param string $table
	* @desc delete records
	*/
	public function Delete($cond, $table=null)
	{
		//select table
		if ($table == null) $table = $this->table;

		//build sql
		$sql = 'DELETE FROM '.$table;
		if ($cond != '')
		{
			if (is_array($cond))
			{
				$sql .= ' WHERE ';
				foreach ($cond as $field=>$value)
				{
					$value = mysql_real_escape_string($value, $this->dbLink);
					$sql .= '`'.$field.'`=\''.$value.'\' AND ';
				}
				$sql = substr($sql, 0, -5);
			}
			else
			{
				$sql .= ' WHERE '.$cond;
			}
		}

		//execute
		$res = $this->MysqlQuery($sql);

		if (!$res) return false;
        else return true;
	}

	/**
	 *
	 * @param Array $params
	 * @param String $table
	 */
    public function Replace($params, $table=null)
	{
		//select table
		if ($table == null) $table = $this->table;

		//build sql
		$sql = 'REPLACE '.$table.' SET ';
		foreach ($params as $field=>$value)
		{
			$quotes = true;
			// do not take $value in ' ' if it's sql
			if (substr($value, 0, 4) == 'sql:') $quotes = false;
			
		    if ($quotes)
			{
			    $value = mysql_real_escape_string($value, $this->dbLink);
				$sql .= '`'.$field.'`=\''.$value.'\',';
			}
			else
			{
				$value = substr($value, 4);
				$sql .= '`'.$field.'`='.$value.',';
			}
		}

		$sql = substr($sql, 0, -1);

		//execute
		$res = $this->MysqlQuery($sql);

		if (!$res) return false;
        else return true;
	}

	/**
	* @return array
	*
	* @param string $cond
	* @param string $order
	* @param order fields
	* @param string $table
	* @desc select records
	*/
	public function Select($cond=null, $order=null, $fields=null, $table=null)
	{
		GLOBAL $Paging;

		//define type of return value
		if ($fields == null)
		{
			$fields = '*';
			$return = array();
		}
		else
		{
			if (Utils::IsArray($fields))
			{
				$fields = implode(',', $fields);
				$return = array();
			}
			else
			{
				if (strpos($fields, ',') !== false) $return = array();
				else $return = '';
			}
		}


		//select table
		if ($table == null) $table = $this->table;


		$sql = 'SELECT '.$fields.' FROM '.$table;

		// add cond to SQL
		if ($cond != '')
		{
			if (Utils::IsArray($cond))
			{
				$sql .= ' WHERE ';
				foreach ($cond as $field=>$value)
				{
					$value = mysql_real_escape_string($value, $this->dbLink);
					$sql .= '`'.$field.'`=\''.$value.'\' AND ';
				}
				$sql = substr($sql, 0, strlen($sql)-5);
			}
			else
			{
				$sql .= ' WHERE '.$cond;
			}
		}

		// add order to SQL
		if ($order != null ) $sql .= ' ORDER BY '.$order;

		// add Paging
		if ($Paging->IsOn())
		{
			$Paging->itemsTotal = ($Paging->itemsTotal != null) ? $Paging->itemsTotal : $this->Get($cond, 'COUNT(id)', $table);
			$Paging->Calculate();
			$this->SetLimit($Paging->offset, $Paging->GetItemsPerPage());
		}

		// Process Limits
		if ($this->limit!=false)
		{
			$sql .= ' LIMIT '.$this->limit['offset'].', '.$this->limit['quantity'];
			$this->ResetLimit();
		}

		//execute
		$res = $this->MysqlQuery($sql);

		// error processing
		if (!$res) return false;

		//fetch result
		if (is_array($return))
		{
			while (false !== ($row = mysql_fetch_array($res, MYSQL_ASSOC)))
			{
				$return[] = self::StripSlashes($row);
			}
			if (sizeof($return) == 0) $return = false;
		}
		else
		{
			while (false !== ($row = mysql_fetch_array($res, MYSQL_ASSOC)))
			{
				$return[] = self::StripSlashes($row[$fields]);
			}
			if ($return == '') $return = false;
		}

		mysql_free_result($res);

		return $return;
	}

	/**
	* @return array or value
	* @param string $cond
	* @param mixed $fields
	* @param string $table
	* @desc get oner row/value
	*/
	public function Get($cond=null, $fields=null, $table=null)
	{
		//define type of return value
		if ($fields == null || $fields == '*')
		{
			$fields = '*';
			$return = array();
		}
		else
		{
			if (strpos($fields, ',') !== false) $return = array();
			else $return = '';
		}

		//select table
		if ($table == null) $table = $this->table;

		//build sql
		$sql = 'SELECT '.$fields.' FROM '.$table;

		if (strlen($cond)) $sql .= ' WHERE '.$cond;

		//execute
		$res = $this->MysqlQuery($sql.' LIMIT 1');

		if (!$res) return false;

		//fetch result
		if (is_array($return))
		{
			$return = self::StripSlashes(mysql_fetch_array($res, MYSQL_ASSOC));
			if (!is_array($return)) $return = false;
		}
		else
		{
			$row = mysql_fetch_array($res, MYSQL_ASSOC);

			if (preg_match('/,/', $fields)) $return = self::StripSlashes($row);
			elseif (isset($row[$fields])) $return = self::StripSlashes($row[$fields]);
			else $return = false;
		}

		mysql_free_result($res);
		return $return;
	}

	/**
	* @return mixed
	* @param string $sql
	* @param array $params
	* @desc run custom SQL query
	*/
	public function Query($sql, Array $params=null)
	{
	    //prepare query with params
	    if ($params) $sql = $this->PrepareSqlWithParams($sql, $params);
	    
		//execute
		$res = $this->MysqlQuery($sql);

		if (!$res) return false;

		$sqlType = '';
		$sqlWords = str_replace('(', '', $sql);
		sscanf($sqlWords, '%s', $sqlType);
        $sqlType = strtolower($sqlType);
        
		if ($sqlType == 'insert')
		{
			$return = mysql_insert_id($this->dbLink);
		}
		elseif ($sqlType == 'replace' || $sqlType == 'update' || $sqlType == 'delete')
		{
		    $return = mysql_affected_rows($this->dbLink);
		}
		elseif ($sqlType == 'select' || $sqlType == 'show' || $sqlType == 'describe')
		{
			//fetch result
			while (false !== ($row = mysql_fetch_array($res, MYSQL_ASSOC)))
			{
				$return[] = self::StripSlashes($row);
			}
			if (!isset($return) || count($return) == 0) $return = false;
			mysql_free_result($res);
		}
		return (isset($return)) ? $return : true;
	}
	
	public function PrepareSqlWithParams($sql, Array $params)
	{
	    GLOBAL $App;
	    
	    if (!Utils::IsArray($params))
	    {
	        //there are no params passed
	        return $sql;
	    }
	    
	    $chunks = preg_split('/[?]/', $sql);
	    
	    if (!Utils::IsArray($chunks))
	    {
	        //sql query has no params specified
	        return $sql;
	    }
	    
	    $qtyChunks = count($chunks);
	    $qtyParams = count($params);
	    
	    if ($qtyParams != $qtyChunks-1)
        {
            //quantity of params is incorrect
	        return $sql;
        }
        
        //pass the parameters
        for ($i=1; $i < $qtyChunks; $i++)
        {
            $p = '\'' . mysql_real_escape_string($params[$i-1], $this->dbLink). '\'';
            $chunks[$i] = $p . $chunks[$i];
        }
        
        return implode('', $chunks);
	}

	/**
	* @return void
	* @desc disconnect from db
	*/
	public function Disconnect()
	{
		if (!$this->isConnected) return;
		
		$this->Unlock();
		mysql_close($this->dbLink);
		
		$this->isConnected = false;
	}

	/**
	* @return void
	* @param int $from
	* @param int $quantity
	* @desc set limit
	*/
	public function SetLimit($offset,$quantity)
	{
		$this->limit = array(
			'offset'	=> $offset,
			'quantity'	=> $quantity
		);
	}

	public function ResetLimit()
	{
		$this->limit = false;
	}
	
    public function GetTableFields($table)
	{
	    $rows = $this->Query('SHOW COLUMNS FROM '.$table);
	    if (!Utils::IsArray($rows)) return array();
	    
	    $fields = array();
	    foreach ($rows as $row)
	    {
	        $fields[] = $row['Field'];
	    }
	    return $fields;
	}
	
	/**
	 * Get active connection
	 *
	 * @return resource
	 */
	public function GetDbLink()
	{
        return $this->dbLink;
	}
	
	/**
	 * Escape SQL arguments
	 *
	 * @param mixed $var
	 * @return mixed
	 */
	public static function Escape($var)
	{
	    if (is_array($var))
        {
			$list = array();
			foreach ($var as $key => $value) $list[$key] = self::Escape($value);
			return $list;
		}
		else
		{
		    // ! warning, with simultaneous multiple connections escaping might be wrong
		    GLOBAL $Db;
		    $q = mysql_real_escape_string($var, $Db->GetDbLink());
		    return "'$q'";
		}
	}
	
	/**
	 * 
	 * Remove escape slashes from external resources, ie db
	 * @param mixed $value
	 */
	public static function StripSlashes($value)
	{
		if (get_magic_quotes_runtime())
		{
			if (is_array($value))
			{
				foreach ($value as $key => $val)
				{
					if (is_array($val)) $value[$key] = self::StripSlashes($val);
					else $value[$key] = stripslashes($val);
				}
			}
			else $value = stripslashes($value);
		}
		return $value;
	}
}

// Initializing $Db class object
$GLOBALS['Db'] = new Database();

?>