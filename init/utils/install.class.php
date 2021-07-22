<?php
/**
 * CLASS Install
 * @package lisk
 *
 */
class Install
{
	
	/**
	 * Each sql statement must be written in one line
	 * works faster than ExecuteDump()
	 *
	 * @param string $filename
	 */
	public static function ExecuteSimpleDump($filename)
	{
		GLOBAL $App, $Db, $ProgressBar;
		$isDebug = $App->debug;
		$App->debug = false;
		
		$ProgressBar->Label('Preparing database');
		
		$sqlQueries = file($filename);
		
		if ($sqlQueries)
		{
		    $totalSteps = count($sqlQueries);
			foreach ($sqlQueries as $k=>$query)
			{
				if (strlen($query))
				{
					$Db->Query($query);
					$ProgressBar->Step($totalSteps, $k, '%&nbsp;complete', ($k==0)?true:false);
				}
			}
		}
		
		$App->debug = $isDebug;
	}

	/**
	 * Execute sql queries stored in file
	 *
	 * @param string $filename
	 */
	public static function ExecuteDump($filename)
	{
		GLOBAL $App, $Db, $ProgressBar;
		$isDebug = $App->debug;
		$App->debug=false;
		
		set_time_limit(600);
		//ignore_user_abort(true);
				
		$ProgressBar->Label('Preparing database');
		
		$sqlQueries = Install::PMA_readFile($filename, '');
		
		$ProgressBar->Label('Generating transaction');
		
		if ($sqlQueries) {
			$pieces = array();
			
			Install::PMA_splitSqlFile($pieces, $sqlQueries);
			
			foreach($pieces as $k=>$arr) {
				$Db->Query($arr['query']);
				$ProgressBar->Step(count($pieces), $k, '%&nbsp;complete', ($k==0)?true:false);
			}
		}
		
		$App->debug = $isDebug;

	}
	
	/**
	 * Removes comment lines and splits up large sql files into individual queries
	 *
	 * Last revision: September 23, 2001 - gandon
	 *
	 * @param   array    the splitted sql commands
	 * @param   string   the sql commands
	 *
	 * @return  boolean  always true
	 *
	 * @access  public
	 */
	public static function PMA_splitSqlFile(&$ret, $sql)
	{
	    // do not trim, see bug #1030644
	    //$sql          = trim($sql);
	    $sql          = rtrim($sql, "\n\r");
	    $sql_len      = strlen($sql);
	    $char         = '';
	    $string_start = '';
	    $in_string    = FALSE;
	    $nothing      = TRUE;
	    $time0        = time();
	    
	    GLOBAL $ProgressBar;
	    $reinitPersent = true;
	    
	    for ($i = 0; $i < $sql_len; ++$i) {
	        $char = $sql[$i];
	
	        // We are in a string, check for not escaped end of strings except for
	        // backquotes that can't be escaped
	        if ($in_string) {
	            for (;;) {
	                $i         = strpos($sql, $string_start, $i);
	                // No end of string found -> add the current substring to the
	                // returned array
	                if (!$i) {
	                    $ret[] = $sql;
	                    return TRUE;
	                }
	                // Backquotes or no backslashes before quotes: it's indeed the
	                // end of the string -> exit the loop
	                else if ($string_start == '`' || $sql[$i-1] != '\\') {
	                    $string_start      = '';
	                    $in_string         = FALSE;
	                    break;
	                }
	                // one or more Backslashes before the presumed end of string...
	                else {
	                    // ... first checks for escaped backslashes
	                    $j                     = 2;
	                    $escaped_backslash     = FALSE;
	                    while ($i-$j > 0 && $sql[$i-$j] == '\\') {
	                        $escaped_backslash = !$escaped_backslash;
	                        $j++;
	                    }
	                    // ... if escaped backslashes: it's really the end of the
	                    // string -> exit the loop
	                    if ($escaped_backslash) {
	                        $string_start  = '';
	                        $in_string     = FALSE;
	                        break;
	                    }
	                    // ... else loop
	                    else {
	                        $i++;
	                    }
	                } // end if...elseif...else
	            } // end for
	        } // end if (in string)
	       
	        // lets skip comments (/*, -- and #)
	        else if (($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ') || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*')) {
	            $i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
	            // didn't we hit end of string?
	            if ($i === FALSE) {
	                break;
	            }
	            if ($char == '/') $i++;
	        }
	
	        // We are not in a string, first check for delimiter...
	        else if ($char == ';') {
	            // if delimiter found, add the parsed part to the returned array
	            $ret[]      = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
	            $nothing    = TRUE;
	            $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
	            $sql_len    = strlen($sql);
	            if ($sql_len) {
	                $i      = -1;
	            } else {
	                // The submited statement(s) end(s) here
	                return TRUE;
	            }
	        } // end else if (is delimiter)
	
	        // ... then check for start of a string,...
	        else if (($char == '"') || ($char == '\'') || ($char == '`')) {
	            $in_string    = TRUE;
	            $nothing      = FALSE;
	            $string_start = $char;
	        } // end else if (is start of string)
	
	        elseif ($nothing) {
	            $nothing = FALSE;
	        }
	
	        // loic1: send a fake header each 30 sec. to bypass browser timeout
	        $time1     = time();
	        if ($time1 >= $time0 + 30) {
	            $time0 = $time1;
	            @header('X-pmaPing: Pong');
	        } // end if
	        
	        $ProgressBar->StepBlock($sql_len, $i, $reinitPersent);
	        if ($reinitPersent) $reinitPersent = false;
	        
	    } // end for
	
	    // add any rest to the returned array
	    if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
	        $ret[] = array('query' => $sql, 'empty' => $nothing);
	    }
	
	    return TRUE;
	}
	
	/**
	 * Reads (and decompresses) a (compressed) file into a string
	 *
	 * @param   string   the path to the file
	 * @param   string   the MIME type of the file, if empty MIME type is autodetected
	 *
	 * @global  array    the phpMyAdmin configuration
	 *
	 * @return  string   the content of the file or
	 *          boolean  FALSE in case of an error.
	 */
	public static function PMA_readFile($path, $mime = '')
	{
	    global $cfg;
	
	    if (!file_exists($path)) {
	        return FALSE;
	    }
	    switch ($mime) {
	        case '':
	            $file = @fopen($path, 'rb');
	            if (!$file) {
	                return FALSE;
	            }
	            $test = fread($file, 3);
	            fclose($file);
	            if ($test[0] == chr(31) && $test[1] == chr(139)) return Install::PMA_readFile($path, 'application/x-gzip');
	            if ($test == 'BZh') return Install::PMA_readFile($path, 'application/x-bzip');
	            return Install::PMA_readFile($path, 'text/plain');
	        case 'text/plain':
	            $file = @fopen($path, 'rb');
	            if (!$file) {
	                return FALSE;
	            }
	            $content = fread($file, filesize($path));
	            fclose($file);
	            break;
	        case 'application/x-gzip':
	            if ($cfg['GZipDump'] && @function_exists('gzopen')) {
	                $file = @gzopen($path, 'rb');
	                if (!$file) {
	                    return FALSE;
	                }
	                $content = '';
	                while (!gzeof($file)) {
	                    $content .= gzgetc($file);
	                }
	                gzclose($file);
	            } else {
	                return FALSE;
	            }
	           break;
	        case 'application/x-bzip':
	            if ($cfg['BZipDump'] && @function_exists('bzdecompress')) {
	                $file = @fopen($path, 'rb');
	                if (!$file) {
	                    return FALSE;
	                }
	                $content = fread($file, filesize($path));
	                fclose($file);
	                $content = bzdecompress($content);
	            } else {
	                return FALSE;
	            }
	           break;
	        default:
	           return FALSE;
	    }
	    return $content;
	}
	
	
	

}

class ProgressBar
{
	public $status = 'off';
	
	public function SwitchOn()
	{
		$this->status = 'on';
	}
	
	public function SwitchOff()
	{
		$this->status = 'off';
	}
	
	public function CheckStatus()
	{
		if ($this->status == 'off')  return false;
		else return true;
	}
	
	public function Step($total, $current, $label='%&nbsp;complete', $reinit=false)
	{
		
		if (!$this->CheckStatus()) return;
		
		static $oldPercent = 0;
		
		if ($reinit) $oldPercent = 0;
		
		$percent = ($current/$total) * 100;
		$percent = sprintf("%01.2f", $percent);
		
		
		if ($percent - $oldPercent >= 1)
		{
			$percent = floor($percent);
			//This div will show loading percents
			//		   echo '<div class="percents">' . $percent . '%&nbsp;complete</div>';
			$this->Label($percent.$label);
			//This div will show progress bar
			//echo '<div class="blocks" style="left: '.$d.'px">&nbsp;</div>';
			//		   flush();
			//		   ob_flush();
			$oldPercent = $percent;
		}
	}
	
	public function StepBlock($total, $current, $reinit=false)
	{
		
		if (!$this->CheckStatus()) return;
		
		static $oldPercent = 0;
		
		if ($reinit) $oldPercent = 0;
		
		$percent = ($current / $total) * 100;
		$percent = floor($percent);
		
		if ($percent - $oldPercent >= 1)
		{
			$left = 12 + $oldPercent * 4;
			$width = floor($percent - $oldPercent) * 4;
			//This div will show progress bar
			echo "<div class=\"blocks\" style=\"left: {$left}px; width: {$width}px\">&nbsp;</div>\n";
			
			ob_flush();
			flush();
			sleep(1);
			
			$oldPercent = $percent;
		}
	}
	
	public function Label($label)
	{
		if (!$this->CheckStatus()) return;
		
		echo "<div class=\"percents\">$label</div>\r\n";
		
		ob_flush();
		flush();
	}
	
	public function Header($title)
	{
		if (!$this->CheckStatus()) return;
		
		echo <<<HEADER
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css"><!--
body, div {
font-family: Arial;
font-size:12px;
}

div {
 margin: 1px;
 height: 20px;
 padding: 1px;
 border: 1px solid #000;
 width: 400px;
 background: #fff;
 color: #000;
 float: left;
 clear: right;
 top: 38px;
 z-index: 9
}

.percents {
 background: #FFF;
 border: 1px solid #A3A3A9;
 margin: 1px;
 height: 20px;
 position:absolute;
 width:400px;
 z-index:10;
 left: 10px;
 top: 38px;
 text-align: center;
 line-height: 20px;
}

.blocks {
 background: #D3D3D9;
 border: 0px none #A3A3A9;
 margin: 0px;
 padding: 0px;
 height: 22px;
 height: "22px";
 position: absolute;
 z-index:10;
 left: 11px;
 top: 40px;
 filter: alpha(opacity=50);
 -moz-opacity: 0.5;
 opacity: 0.5;
 -khtml-opacity: .5
}

-->
</style>
</head>
<body style="margin:10px;padding:0px;">
HEADER;

		
		echo str_pad($title, 4096)."<br />\r\n";
		
		if (ob_get_level() == 0)
		{
		   ob_start();
		}
	}
	
	public function Footer($url=null)
	{
		if (!$this->CheckStatus()) return;
		
		ob_end_flush();
		echo <<<FOOTER
<div class="percents" style="z-index:12">Done.</div>
<br />\n
<br />\n
<br />\n
<a href="{$url}">Next Step </a>
<script type="text/javascript" language="javascript">
location.href='{$url}';
</script>
</body>
</html>
FOOTER;
	}
	
}

$GLOBALS['ProgressBar'] = new ProgressBar();

?>