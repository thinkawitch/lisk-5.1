<?php
/**
 * CLASS Utils
 * @package lisk
 *
 */

Class Utils
{
    private static $mbEncoding = null;
    
    static function TrimArray($a)
	{
		if (is_array($a))
		{
			$arr = array();
			foreach ($a as $k=>$v)
			{
				$arr[$k] = self::TrimArray($v);
			}
			return $arr;
		}

		return trim($a);
	}
    
	/**
	 * Transform array into property field
	 *
	 * @param array $prop
	 * @return string
	 */
	static function PropToStr($prop)
	{
	    $propStr = '';
	    if (self::IsArray($prop))
	    {
	        foreach ($prop as $v) $propStr .= '<'.$v.'>';
	    }
	    return $propStr;
	}

	/**
	 * Transform property field into array
	 *
	 * @param string $str
	 * @return array
	 */
	static function StrToProp($str)
	{
	    return preg_split('/[<>]/', $str, null, PREG_SPLIT_NO_EMPTY);
	}
    
    public static function IsAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    }

    /**
     *  Set mb_string internal encoding to single byte charset set
     *
     */
    public static function FreezeMBEncoding()
    {
        if (self::$mbEncoding != null) self::$mbEncoding = mb_get_info('internal_encoding');
        ini_set('mbstring.internal_encoding', 'iso-8859-1');
    }
    
    /**
     * Restore back mb_string internal encoding
     *
     */
    public static function UnfreezeMBEncoding()
    {
        ini_set('mbstring.internal_encoding', self::$mbEncoding);
    }
    
	/**
	 * Encrypt string with key using xor
	 *
	 * @param string to enrypt $str
	 * @param string key(password) $key
	 * @return string
	 */
	public static function XorEncrypt($str, $key)
	{
		$len = strlen($str);
		$len2 = strlen($key);
		$res = '';
		for($i = 0; $i < $len; $i++)
		{
			$ch1 = ord(substr($str, $i, 1));
			$j = $i;
			while($j >= $len2) $j -= $len2;
			$ch2 = ord(substr($key, $j, 1));
			$res .= sprintf('%02X', ($ch1 ^ $ch2));
		}
		return $res;
	}

	/**
	 * Decrypt string with key using xor
	 *
	 * @param string to decrypt $str
	 * @param string key(password) $key
	 * @return string
	 */
	public static function XorDecrypt($str, $key)
	{
		$len = strlen($str);
		$len2 = strlen($key);
		$j = 0;
		$res = '';
		for($i = 0; $i < $len; $i+=2)
		{
			$ch1 = hexdec(substr($str, $i, 2));
			while($j >= $len2) $j -= $len2;
			$ch2 = ord(substr($key, $j, 1));
			$j++;
			$res .= chr($ch1 ^ $ch2);
		}
		return $res;
	}
	    
     /**
	 * Safe merge 2 arrays
	 *
	 * @param mixed $arr1
	 * @param mixed $arr2
	 * @return array
	 */
	public static function MergeArrays($arr1, $arr2)
	{
		$arr = array();

		if (self::IsArray($arr1) && self::IsArray($arr2)) $arr = array_merge($arr1, $arr2);
		elseif (self::IsArray($arr2)) $arr = $arr2;
		elseif (self::IsArray($arr1)) $arr = $arr1;

		return $arr;
	}
	
	/**
	* Select and return domain name (without www)
	* !Very simple, not safe
	*
	* @param string $url
	* @return string
	*/
	public static function GetDomainName($url)
	{
		$url = strtolower(trim($url));
		$marks = array('http://www.', 'https://www.', 'http://', 'https://', 'www.');
		
		foreach ($marks as $mark)
		{
			$markLen = strlen($mark);
			if (substr($url, 0, $markLen) == $mark)
			{
				$url = substr($url, $markLen);
			}
		}
		
		$parts = preg_split('[/]', $url);
		return $parts[0];
	}

	/**
	* Remove http://, www., domain name from the string
	* !Buggy, not safe
	*
	* @param string $url
	* @return string
	*/
	public static function RemoveDomainName($url)
	{
		$url = preg_replace('/^http(s|):\/\/(www.|)/','',$url);
		$rez = preg_split('[/]',$url);
		return substr($url,strlen($rez[0]));
	}

	/**
	* remove session id from the string
	*
	* @param string $url
	* @return string
	*/
	public static function RemoveSessionId($url)
	{
		$name = session_name();
		return preg_replace('/[?&]('.strtoupper($name).'|'.strtolower($name).')=[wd]+/','',$url);
	}

	/**
	 * Set cookie variable
	 *
	 * @param string $name - variable name
	 * @param string $value - value
	 * @param int $seconds - time you wish cookie to exist (i.e. 60*60*24*365 - 1 year)
	 */
	public static function SetCookie($name, $value, $seconds=null)
	{
		if ($seconds>0) setcookie($name, $value, time()+$seconds, '/');
		else setcookie($name, $value, null, '/');
	}

	/**
	 * Delete cookie variable
	 *
	 * @param string $name - cookie variable name
	 */
	public static function DeleteCookie($name)
	{
		setcookie ($name, '', time() - 3600, '/');
		unset($_COOKIE[$name]);
	}

	/**
	 * Ultimate Randomize method
	 *
	 * @param rows $values any Rows array (f.e. DataItem->values)
	 * @param integer $quantity quantiry of records we need to select
	 * @return rows new rows array conteined random selected values
	 */
	public static function Randomize($values, $quantity)
	{
		$result = array();
		$resultList = array();

		// fix importance filed
		if (self::IsArray($values))
		foreach ($values as $key=>$row)
		{
			if (!isset($row['importance'])) $values[$key]['importance'] = 1;
			else $values[$key]['importance'] = intval($row['importance']);
		}

		// fix quantity > Count
		if (sizeof($values) < $quantity) $quantity = sizeof($values);

		// create full list
		$fullList = array();
		if (self::IsArray($values))
		{
    		foreach ($values as $key=>$row)
    		{
    			$i=1;
    			for ($i=1; $i<=$row['importance']; $i++)
    			{
    				$fullList[] = $key;
    			}
    		}
		}

		$upperBound = sizeof($fullList)-1;

		for ($i=0; $i<$quantity; $i++)
		{
			$isUnique = false;
			$_c = 0;

			while (!$isUnique)
			{
				$_c++;
				if ($_c > 1000)
				{ //prevent iternal loop
					break;
				}
				$next = rand(0, $upperBound);
				$inArray = in_array($next, $resultList);
				if (!$inArray)
				{
					$isUnique = true;
				}
			}
			$resultList[] = $next;
		}

		// create result based on result List
		if (self::IsArray($resultList))
		{
			foreach ($resultList as $key=>$row)
			{
				$result[] = $values[$fullList[$row]];
			}
		}

		return $result;
	}

	/**
	 * Recreate rows for table parsing. Example
	 * input rows arr[0][id] arr[1][id] arr[n][id]
	 * result arr[0][id_0] arr[0][id_1] arr[0][id_2]
	 * @param Rows $arr values
	 * @param int $cols number of values in result rows
	 * @return Rows
	 */
	public static function RecreateRowsTableFix($arr, $cols)
	{
		if (!self::IsArray($arr)) return null;
		
		$result = array();
		$count = count($arr);
		$k = 0;
		for ($i=0; $i<$count; $i+=$cols)
		{
			$result[$i] = array();
			$count1 = $cols;
			for ($j=0; $j<$count1 && $arr[$k*$cols+$j];$j++)
			{
				foreach ($arr[$k*$cols+$j] as $key=> $value)
				{
					$result[$i][$key."_$j"]=$value;
				}
			}
			$k++;
		}
		
		return $result;
	}

	/**
	 * Use this function to get SQL IN condition when you work with cross structures
	 *
	 * @param string $crossName crossList DataItem name (i.e. hot_items)
	 * @param string $cond cond for SQL select (i.e. parent_id=5)
	 * @return string in format (id1,id2,id3) or empty if nothing found
	 */
	public static function CrossToIn($crossName,$cond=null)
	{
		$di = Data::Create($crossName);
		$rows = $di->SelectValues($cond, 'object_id');
		if (self::IsArray($rows))
		{
			$rez = implode(',', $rows);
			return '('.$rez.')';
		}
		return false;
	}

	/**
	 * Returns true if the specified variable is array and it is not empty
	 *
	 * @param mixed $arr
	 * @return boolean
	 */
	public static function IsArray($arr=null)
	{
		return isset($arr) && is_array($arr) && count($arr) > 0;
	}

	/**
	 * Convert tree-like string format "<3><4><7>" to array
	 *
	 * @param string $parents
	 * @return array
	 */
	public static function TreeToArray($parents)
	{
		$parents = str_replace('<', '', $parents);
		$rez = preg_split('/>/', $parents);
		unset($rez[sizeof($rez)-1]);
		return $rez;
	}

	/**
	 * Returns array for using with $Parser->MakeNavigation method
	 *
	 * @param int $id current node/point id
	 * @param string $treeName name of the tree structure
	 * @return array
	 */
	public static function TreeToNavigation($id, $treeName)
	{
		GLOBAL $Db,$App;
		if ($id == '') $id = 1;

		$tree = $App->ReadTree($treeName);
		$nodeName = $tree['node'];

		$NodeObj = Data::Create($nodeName, false);

		$parents = $Db->Get('id='.$id, 'parents', $NodeObj->table);
		$parents = self::TreeToIn($parents."<$id>");
		$names = $Db->select("id IN $parents", 'id','id,name', $NodeObj->table);
		return $names;
	}

	/**
	 * Format tree style (or prop) string <id1><id2><idn>
	 * for DB query with IN operator and returns it as
	 * "(id1,id2,idN)"
	 *
	 * @param string $parents
	 * @return string
	 */
	public static function TreeToIn($parents)
	{
		$str = implode(',', self::TreeToArray($parents));
		return '('.$str.')';
	}


	/**
	 * Calculate tree level base on parents string
	 *
	 * @param string $parents
	 * @return int
	 */
	public static function TreeLevel($parents)
	{
		$parentsArr = self::TreeToArray($parents);
		return sizeof($parentsArr);
	}

	/**
	 * Sort array by parents order
	 *
	 * @param string $parentsString <id1><id2><idn> OR (id1,id2,idN) format
	 * @param array $rows
	 * @return array
	 */
	public static function OrderByParents($parentsString, $rows)
	{
		if (!self::IsArray($rows)) return null;
		
		// format string into (id1,id2,idN) format
		if (substr($parentsString, 0, 1) == '<') $parentsString = self::TreeToIn($parentsString);

		//convert paretns string into array
		$arr = preg_split('/[,]/', substr($parentsString, 1, -1));

		$rez = array();
		foreach ($arr as $v1)
		{
			foreach ($rows as $v2)
			{
				if ($v2['id'] == $v1)
				{
					$rez[] = $v2;
				}
			}
		}
		return $rez;
	}

	/**
	 * Converts rows (hash of hashes) to hash of specified variables
	 *
	 * @param array $list
	 * @param string $keyName result hash key variable name
	 * @param string $valueName value variable name
	 * @return array
	 */
	public static function ListToHash($list, $keyName, $valueName)
	{
		if (!self::IsArray($list)) return null;
		
		$hash = array();
		foreach($list as $item)
		{
			$hash[$item[$keyName]] = $item[$valueName];
		}
		return $hash;
	}
	
	/**
	 * Convert rows to array with fixed keys
	 * @param array $list
	 * @param string $keyName
	 * @return array
	 */
	public static function ListToIdList($list, $keyName)
	{
		if (!self::IsArray($list)) return null;
		
	    $hash = array();
		foreach($list as $item)
		{
			$hash[$item[$keyName]] = $item;
		}
		return $hash;
	}

	/**
	 * stripes slashes from $value if magic quotes is used
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public static function StripSlashes($value)
	{
		if (get_magic_quotes_gpc())
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

	/**
	 * get time with microseconds
	 *
	 * @return float
	 */
	public static function GetMicroTime()
	{
	    list($usec, $sec) = explode(' ', microtime());
	    return ((float)$usec + (float)$sec);
	}

	/**
	 * Calculate and returns formatted time interval between 2 dates, for example used to calculate user's age
	 *
	 * @param date $date1
	 * @param date $date2
	 * @param string $resultFormat
	 * @return string
	 */
	public static function DateDifference($date1, $date2, $resultFormat='days')
	{
		$date1_s = strtotime($date1);
		$date2_s = strtotime($date2);
		$dif = $date1_s - $date2_s;
		$rez_sign = ($dif>0) ? -1 : 1;
		$dif = abs($dif);
		switch ($resultFormat)
		{
			case 'days':
				$result = round($dif/86400);
			break;
		}
		return ($rez_sign*$result);
	}

	public static function NodeSort($parent, $rows, $cl, $nodeViewField)
	{
		static $rez = array();
		if (self::IsArray($rows))
		{
			foreach($rows as $row)
			{
				if ($row['parent_id'] == $parent)
				{
					$rez[$row['id']] = str_repeat('&nbsp;', substr_count($row['parents'],'>')*2).$row[$nodeViewField];
					self::NodeSort($row['id'], $rows, $cl, $nodeViewField);
				}
			}
		}
		return $rez;
	}

	/**
	 * Render tree structure
	 *
	 * @param integer $parent
	 * @param array $tree
	 * @param array $format
	 * @return string
	 */
	public static function TreeStructureRender($parent, $tree, $format=null)
	{
		if (!is_array($format))
		{
			$format = array(
				'level0'	=> '',
				'level1'	=> "<a href=\"[url]\">[name]</a><br>",
				'level2'	=> "&nbsp;&nbsp;<a href=\"[url]\">[name]</a><br>",
			);
		}

		static $html = '';

		foreach ($tree as $row)
		{
			if ($row['parent_id'] == $parent)
			{
				$prop = preg_split('/[<>]/', $row['parents'], null, PREG_SPLIT_NO_EMPTY);
			    $level = (is_array($prop)) ? count($prop) : 0;

			    $html .= Format::String($format['level'.$level], $row);
				self::TreeStructureRender($row['id'], $tree, $format);
			}
		}

		return $html;
	}
	
	/**
	 * Get years old from db date
	 *
	 * @param string $dbDate
	 * @return number
	 */
	public static function GetYearsOld($dbDate)
	{
		//now
		list($y1, $m1, $d1) = sscanf(date('Y-m-d'), '%04d-%02d-%02d');
		//compare
		list($y2, $m2, $d2) = sscanf($dbDate, '%04d-%02d-%02d');

		$yearsOld = 0;

		if ($y1 > $y2)
		{
			$yearsOld = $y1-$y2;
			if ($m1 > $m2)
			{
				//
			}
			elseif ($m1 == $m2)
			{

				if ($d1>$d2)
				{
					//
				}
				elseif ($d2 == $d1)
				{
					// as we have no time in date
				}
				else
				{
					$yearsOld--;
				}
			}
			else
			{
				$yearsOld--;
			}
		}

		return $yearsOld;
	}
}

?>