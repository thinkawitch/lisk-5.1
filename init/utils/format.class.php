<?php
/**
 * CLASS Format
 * @package lisk
 *
 */

Class Format
{

	/**
	* Format string to URL like view
	*
	* @param string $name
	* @return string
	*/
	public static function ToUrl($name)
	{
		$str = self::ToTraslit($name);
				
		// remove ['] symbols
		$str = str_replace("'", '', $str);
		// remove ["] symbols
		$str = str_replace('"', '', $str);
		
		// replace all symbols except number, letters, and "_", "-"
		return preg_replace('/[^0-9A-z_\-]+/', '_', $str);
	}
	
	/**
	 * Format filename to safe symbols only
	 *
	 * @param string $name
	 */
	public static function Filename($name)
	{
	    //TODO
	    //make transliteration for cyrillic filenames
	    //how?
	    return preg_replace('/[^0-9A-z_\-.]+/', '_', $name);
	}
	
	/**
	 * Translate string from cyrillic to translit, utf-8
	 *
	 * @param string $str
	 */
	public static function ToTraslit($str)
	{
		$encInternal = 'utf-8';
		
		$cyr = array(
		    "а", "б", "в",  "г", "д",  "е",  "ё",   "ж",  "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т",
			"у", "ф", "х",  "ц", "ч",  "ш",  "щ",   "ъ",  "ы", "ь", "э", "ю", "я",
    	);
		$lat = array(
		    "a", "b", "v",  "g", "d",  "e",  "jo",  "zh", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t",
			"u", "f", "kh", "c", "ch", "sh", "sch", "",   "y", "",  "e", "u", "ia",
		);

		// convert to lowercase
		$str = mb_strtolower($str, $encInternal);
		
		// transform to array
		$arr = array();
		$len = mb_strlen($str, $encInternal);
		for ($i=0; $i<$len; $i++)
		{
		    $arr[] = mb_substr($str, $i, 1, $encInternal);
		}
	     	
		//convert letter by letter
		for ($i=0; $i<count($cyr); $i++)
		{
			for ($j=0; $j<$len; $j++)
			{
			    if ($arr[$j] == $cyr[$i]) $arr[$j] = $lat[$i];
			}
		}
		
		return implode('', $arr);
	}

	/**
	 * @return string Formatted price
	 * @param float $price Price which whould be formatted
	 * @desc Format string to money 0000.00 format
	*/
	public static function Price($price)
	{
		if ($price == '') $price = 0;
		return sprintf("%.2f", $price);
	}

	public static function String($str,$value1)
	{
		//$str = "?cl=[id]&back=[back]&[add]";
		$pattern = '/(\[\w+\])/';
		$rez = array();
		preg_match_all($pattern,$str,$rez);
		$rez = $rez[0];
		foreach ($rez as $var)
		{
			$valueKey = substr($var,1);
			$valueKey = substr($valueKey,0,-1);
			$str=str_replace($var, $value1[$valueKey], $str);
		}
		return $str;
	}

	/**
	 * @return string 	Formatted date and time
	 * @param int $date Date and time wich must be formatted
	 * @param string $format Date and time format
	 * @desc Return formatted date and time
	*/
	public static function DateTime($date, $format=null)
	{
		GLOBAL $App;
		if ($format == null) $format = $App->dateFormat.' '.$App->timeFormat;
		return date($format, strtotime($date));
	}

	/**
	 * @return string 	Formatted date
	 * @param int $date Date wich must be formatted
	 * @param string $format Date format
	 * @desc Return formatted date
	*/
	public static function Date($date, $format = null)
	{
		GLOBAL $App;
		if ($format == null) $format = $App->dateFormat;
		
		return date($format, strtotime($date));
	}

	/**
	 * @return string 	Formatted time
	 * @param int $time Time wich must be formatted
	 * @param string $format Time format
	 * @desc Return formatted time
	*/
	public static function Time($time, $format = null)
	{
		GLOBAL $App;
		if ($format == null) $format = $App->timeFormat;
		
		return date($format, strtotime($time));
	}

	/**
	 * @return string Formatted current date and time
	 * @desc Get current date and time and return it formatted
	*/
	public static function DateTimeNow()
	{
		return date('Y-m-d H:i:s', time());
	}

	/**
	 * @return string formatted file date
	 * @param int $size File size which must be formatted
	 * @desc Format file size (add thousand separators and Kb, Mb etc.)
	*/
	public static function FileSize($fileSize)
	{
		if ($fileSize == 0)
		{
			return 'empty file';
		}
		elseif ($fileSize == 1)
		{
			return $fileSize.' Byte';
		}
		elseif ($fileSize < 1024)
		{
			return $fileSize.' Bytes';
		}
		elseif ($fileSize < 1024*1024)
		{
			return (round($fileSize/1024, 2)).' Kb';
		}
		else
		{
            return (round($fileSize/1024/1024, 2)).' Mb';
		}
	}

	public static function Label($str)
	{
		return ucwords(str_replace('_', ' ', $str));
	}

	/**
	 * @return string Formatted time interval
	 * @param int $time Time interval in seconds.
	 * @param string $mode short|long formatting
	 * @desc Format time interval into days, hours, minutes, secons
	*/
	public static function TimeLength($time, $mode='long')
	{
		switch ($mode)
		{
			case 'long':
				$h = ' hour';
				$m = ' minute';
				$s = ' second';
				$d = ' day';
				break;

			case 'short':
				$h = 'h';
				$m = 'm';
				$s = 's';
				$d = 'd';
				break;
		}

		if ($time < 60)
		{
		    $sExt = $time == 1 ? '' : 's';
			return $time.$s.$sExt;
		}
		elseif ($time < 3600)
		{
			$seconds = $time % 60;
			$minutes = ($time - $seconds) / 60;
			$sExt = $seconds == 1 ? '' : 's';
			$mExt = $minutes == 1 ? '' : 's';
			return $minutes.$m.$mExt.', '.$seconds.$s.$sExt;
		}
		elseif ($time < 86400)
		{
			$time = round($time / 60);
			$minutes = $time % 60;
			$hours = round($time / 60);
			$hExt = $hours == 1 ? '' : 's';
			$mExt = $minutes == 1 ? '' : 's';
			return $hours.$h.$hExt.', '.$minutes.$m.$mExt;
		}
		else
		{
			$time = round($time/3600);
			$hours = $time % 24;
			$days = round($time / 24);
			$dExt = $days == 1 ? '' : 's';
			$hExt = $hours == 1 ? '' : 's';
			return $days.$d.$dExt.', '.$hours.$h.$hExt;
		}
	}
	
	/**
	 * Format date into time interval. If date less then current indicates time difference.
	 * Otherwise shows actual date
	 * @param $stamp timestamp of the date,
	 * @param $format format for date
	 */
	public static function DateInterval($stamp, $format='m-d-Y')
	{
		$diff = mktime() - $stamp;
		if ($diff < 84600) return self::TimeLength($diff).' ago';
		return date($format, $stamp);
	}

	/**
	 * Format Credit Card number like ****-****-****-1111
	 *
	 * @param string $ccNumber
	 * @param integer $show number of last digits to show
	 * @param string $char
	 * @return string
	 */
	public static function CCNumber($ccNumber, $show=4, $char='*')
	{
		$len = strlen($ccNumber);
		if (!$len) return '';

		$diff = $len - $show;
		if ($diff>0)
		{
			$hide = str_repeat($char, $diff);
			$ccNumber = $hide.substr($ccNumber, $diff);
		}

		$i = 4;
		$c = 1;
		while (isset($ccNumber{$i}))
		{
			$start = $i - 4*$c;
			$end = $i + $c - 1;
			if (isset($ccNumber{$end}))
			{
				$ccNumber = substr($ccNumber, $start, $end).'-'.substr($ccNumber, $end);
				$c++;
			}
			$i += 4;
		}
		return $ccNumber;
	}

	/**
	 * Highlight the keyword in the search results
	 *
	 * @param string $text Original text
	 * @param string $query Search keyword
	 * @param unknown_type $is_text
	 * @return string
	 */
	public static function SearchResult($text, $query, $is_text=true)
	{
		if (strpos($query, ' ') === false)
		{
			$parse_query = $query;
		}
		else
		{
			$parse_query = '('.$query.'|'.str_replace(' ', '|', $query).')';
		}

		if (!$is_text) return preg_replace('/'.$parse_query.'/siU', '<strong>\\0</strong>', $text);

		$text = strip_tags($text);
		$m = array();
		preg_match_all('/(\w+\s+\w+\s+\w+\s+)?(\w+\s+\w+\s+)?(\w+s+)?(\S+\s)?('.$parse_query.')(\w+\s+\w+)?(\w+)?(\s+\w+\s+\w+\s+\w+)?(\s+\w+\s+\w+)?(\s+\w+)?/si', $text, $m);
		$ptext = '';
		foreach ($m[0] as $p)
		{
			$ptext .= $p.' ... ';
		}
		if (strlen($ptext) > 700)
		{
			$ptext = substr($ptext, 0, 700).' ...';
		}
		$ptext = preg_replace('/'.$parse_query.'/siU', '<strong>\\0</strong>', $ptext);

		return $ptext;
	}

	public static function GetAge($birth_date)
	{
		return floor((time() - strtotime($birth_date)) / (60*60*24*365.25));
	}
	
    /**
     * Insert spaces into string every $cnt chars or after $separator
     *
     * @param string $str
     * @param number $cnt
     * @param string $separator
     * @return string
     */
	public static function StrSpaces($str, $cnt, $separator='')
	{
        if (strlen($str) <= $cnt) return $str;
        
        $return = '';
        
        while (strlen($str) > $cnt)
        {
            // Get pierce of $str
            $temp = substr($str, 0, $cnt);
            
            // Finding $separator
            if (!empty($separator) && ($pos=strrpos($temp, $separator)) !== false)
            {
                $pos++;
                $return .= substr($temp, 0, $pos).' ';
                $str = substr($temp, $pos).substr($str, $cnt);
            }
            else
            {
                $return .= $temp.' ';
                $str = substr($str, $cnt);
            }
        }
        
        // If nothing to add, delete last blank
        if (empty($str)) $return = substr($return, 0, -1);
        
        return $return.$str;
	}
}

$GLOBALS['Format'] = new Format();

?>