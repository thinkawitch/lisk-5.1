<?php
/**
 * CLASS MysqlDump
 * based on Sypex Dumper Lite 1.08
 * @package lisk
 *
 */

class MysqlDump
{
    // Ограничение размера данных доставаемых за одно обращения к БД (в мегабайтах)
    // Нужно для ограничения количества памяти пожираемой сервером при дампе очень объемных таблиц
    const LIMIT = 1;
    // Кодировка соединения с MySQL
    // auto - автоматический выбор (устанавливается кодировка таблицы), cp1251 - windows-1251, и т.п.
    const CHARSET = 'auto';
    // Кодировка соединения с MySQL при восстановлении
    // На случай переноса со старых версий MySQL (до 4.1), у которых не указана кодировка таблиц в дампе
    // При добавлении 'forced->', к примеру 'forced->cp1251', кодировка таблиц при восстановлении будет принудительно заменена на cp1251
    // Можно также указывать сравнение нужное к примеру 'cp1251_ukrainian_ci' или 'forced->cp1251_ukrainian_ci'
    const RESTORE_CHARSET = 'utf8';
    // Типы таблиц у которых сохраняется только структура, разделенные запятой
    const ONLY_CREATE = 'MRG_MyISAM,MERGE,HEAP,MEMORY';
    
    const NO_COMPRESSION =0;
    const COMPRESS_GZIP = 1;
    const COMPRESS_BZIP2 = 2;
    
    private $compressMethod = self::NO_COMPRESSION;
    private $compressLevel = 0; //0 - no compression, 9 - max compression
    
    private $set;
    
    private $mysql_version;
    private $only_create;
    private $forced_charset;
    private $restore_charset;
    private $restore_collate;
    
    private $filename;
    
    private $tabs;
    private $records;
    private $size;
    private $comp;
    
    public $dbHost;
    public $dbUser;
    public $dbPass;
    public $dbName;
    
	function __construct()
	{
		$this->tabs = 0;
		$this->records = 0;
		$this->size = 0;
		$this->comp = 0;

		$m = array();
		preg_match('/^(\d+)\.(\d+)\.(\d+)/', mysql_get_server_info(), $m);
		$this->mysql_version = sprintf('%d%02d%02d', $m[1], $m[2], $m[3]);

		$this->only_create = explode(',', self::ONLY_CREATE);
		$this->forced_charset  = false;
		$this->restore_charset = $this->restore_collate = '';
		$matches = array();
		if (preg_match('/^(forced->)?(([a-z0-9]+)(\_\w+)?)$/', self::RESTORE_CHARSET, $matches))
		{
			$this->forced_charset  = $matches[1] == 'forced->';
			$this->restore_charset = $matches[3];
			$this->restore_collate = !empty($matches[4]) ? ' COLLATE ' . $matches[2] : '';
		}
		
		$this->set['comp_method'] = self::NO_COMPRESSION;
	}
	
	public function UseGzipCompression($level=6)
	{
	    $this->compressMethod = self::COMPRESS_GZIP;
	    $this->compressLevel = $level;
	}
	
    public function UseBzip2Compression()
	{
	    $this->compressMethod = self::COMPRESS_BZIP2;
	    $this->compressLevel = 0;
	}
    
    function Backup($filename, $tables=null)
    {
		GLOBAL $App;
		$Db2 = new Database($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName);
	    //set_error_handler(array('MysqlDump', 'SXD_errorHandler'));
	    $appDebug = $App->debug;
	    $App->debug = false;
	    
		$this->set['last_action']     = 0;
		$this->set['last_db_backup']  = $this->dbName;
		$this->set['tables_exclude']  = !empty($tables) && $tables{0} == '^' ? 1 : 0;
		$this->set['tables']          = isset($tables) ? $tables : '';
		$this->set['comp_method']     = $this->compressMethod;
		$this->set['comp_level']      = $this->compressLevel;

		$this->set['tables']          = explode(',', $this->set['tables']);
		if (!empty($tables))
		{
		    foreach($this->set['tables'] AS $table)
		    {
    			$table = preg_replace('/[^\w*?^]/', '', $table);
				$pattern = array('/\?/', '/\*/');
				$replace = array('.', '.*?');
				$tbls[] = preg_replace($pattern, $replace, $table);
    		}
		}
		else
		{
			$this->set['tables_exclude'] = 1;
		}

		$db = $this->set['last_db_backup'];

		if (!$db) $App->SetError('No database!');
		
		$tables = array();
        $result = $Db2->MysqlQuery('SHOW TABLES');
        $all = 0;
        while (false!==($row = mysql_fetch_array($result)))
        {
			$status = 0;
			if (!empty($tbls))
			{
			    foreach($tbls AS $table)
			    {
    				$exclude = preg_match('/^\^/', $table) ? true : false;
    				if (!$exclude)
    				{
    					if (preg_match("/^{$table}$/i", $row[0]))
    					{
    					    $status = 1;
    					}
    					$all = 1;
    				}
    				if ($exclude && preg_match("/{$table}$/i", $row[0]))
    				{
    				    $status = -1;
    				}
    			}
			}
			else $status = 1;
			
			if ($status >= $all) $tables[] = $row[0];
        }
   
		$tabs = count($tables);
		// Определение размеров таблиц
		$result = $Db2->MysqlQuery('SHOW TABLE STATUS');
		$tabinfo = array();
		$tab_charset = array();
		$tab_type = array();
		$tabinfo[0] = 0;
		$info = '';
		while (false!==($item = mysql_fetch_assoc($result)))
		{
			//print_r($item);
			if (in_array($item['Name'], $tables))
			{
				$item['Rows'] = empty($item['Rows']) ? 0 : $item['Rows'];
				$tabinfo[0] += $item['Rows'];
				$tabinfo[$item['Name']] = $item['Rows'];
				$this->size += $item['Data_length'];
				$tabsize[$item['Name']] = 1 + round(self::LIMIT * 1048576 / ($item['Avg_row_length'] + 1));
				if ($item['Rows']) $info .= '|' . $item['Rows'];
				$m = array();
				if (!empty($item['Collation']) && preg_match("/^([a-z0-9]+)_/i", $item['Collation'], $m))
				{
					$tab_charset[$item['Name']] = $m[1];
				}
				$tab_type[$item['Name']] = isset($item['Engine']) ? $item['Engine'] : $item['Type'];
			}
		}
		
		$info = $tabinfo[0] . $info;
		
        $fp = $this->fn_open($filename, 'w');
		
		$this->fn_write($fp, "#SKD101|{$db}|{$tabs}|" . date('Y.m.d H:i:s') ."|{$info}\n\n");
		$t = 0;
	
		$Db2->MysqlQuery('SET SQL_QUOTE_SHOW_CREATE = 1');
		// Кодировка соединения по умолчанию
		if ($this->mysql_version > 40101 && self::CHARSET != 'auto')
		{
			$Db2->MysqlQuery("SET NAMES '" . self::CHARSET . "'");
			$last_charset = self::CHARSET;
		}
		else $last_charset = '';
		
        foreach ($tables AS $table)
        {
			// Выставляем кодировку соединения соответствующую кодировке таблицы
			if ($this->mysql_version > 40101 && $tab_charset[$table] != $last_charset)
			{
				if (self::CHARSET == 'auto')
				{
					$Db2->MysqlQuery("SET NAMES '" . $tab_charset[$table] . "'");
					$last_charset = $tab_charset[$table];
				}
			}
			
        	// Создание таблицы
			$result = $Db2->MysqlQuery("SHOW CREATE TABLE `{$table}`");
        	$tab = mysql_fetch_array($result);
			$tab = preg_replace('/(default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP|DEFAULT CHARSET=\w+|COLLATE=\w+|character set \w+|collate \w+)/i', '/*!40101 \\1 */', $tab);
        	$this->fn_write($fp, "DROP TABLE IF EXISTS `{$table}`;\n{$tab[1]};\n\n");
        	// Проверяем нужно ли дампить данные
        	if (in_array($tab_type[$table], $this->only_create))
        	{
				continue;
			}
        	// Опредеделяем типы столбцов
            $NumericColumn = array();
            $result = $Db2->MysqlQuery("SHOW COLUMNS FROM `{$table}`");
            $field = 0;
            while (false!==($col = mysql_fetch_row($result)))
            {
            	$NumericColumn[$field++] = preg_match('/^(\w*int|year)/', $col[1]) ? 1 : 0;
            }
			$fields = $field;
            $from = 0;
			$limit = $tabsize[$table];
			
			if ($tabinfo[$table] > 0)
			{
    			$i = 0;
    			$this->fn_write($fp, "INSERT INTO `{$table}` VALUES");
                while (($result = $Db2->MysqlQuery("SELECT * FROM `{$table}` LIMIT {$from}, {$limit}")) && ($total = mysql_num_rows($result)))
                {
                		while (false!==($row = mysql_fetch_row($result)))
                		{
                        	$i++;
        					$t++;
    
    						for ($k = 0; $k < $fields; $k++)
    						{
                        		if ($NumericColumn[$k]) $row[$k] = isset($row[$k]) ? $row[$k] : 'NULL';
                        		else $row[$k] = isset($row[$k]) ? "'" . mysql_escape_string($row[$k]) . "'" : 'NULL';
                        	}
    
        					$this->fn_write($fp, ($i == 1 ? '' : ',') . "\n(" . implode(', ', $row) . ')');
                   		}
    					mysql_free_result($result);
    					if ($total < $limit)
    					{
    					    break;
    					}
        				$from += $limit;
                }
    
    			$this->fn_write($fp, ";\n\n");
			}
		}
		$this->tabs = $tabs;
		$this->records = $tabinfo[0];
		$this->comp = $this->set['comp_method'] * 10 + $this->set['comp_level'];
        
        $this->fn_close($fp);
		
        $App->debug = $appDebug;
	}
	

	function Restore($filename)
	{
	    GLOBAL $App;
	    $Db2 = new Database($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName);
	    //set_error_handler(array('MysqlDump', 'SXD_errorHandler'));
	    $appDebug = $App->debug;
	    $App->debug = false;

		$this->set['last_action']     = 1;
		$this->set['last_db_restore'] = $this->dbName;
		$file						  = $filename;
		
		$db = $this->set['last_db_restore'];

		if (!$db) $App->RaiseError('ОШИБКА! Не указана база данных!');
		
	    if (!file_exists($file))  $App->RaiseError('ОШИБКА! Файл не найден!');
		
	    // Определение формата по имени файла
		$matches = array();
		if (preg_match('/^(.+?)\.sql(\.(bz2|gz))?$/', $file, $matches))
		{
			if (isset($matches[3]) && $matches[3] == 'bz2')
			{
			    $this->set['comp_method'] = self::COMPRESS_BZIP2;
			}
			elseif (isset($matches[3]) && $matches[3] == 'gz')
			{
				$this->set['comp_method'] = self::COMPRESS_GZIP;
			}
			else
			{
				$this->set['comp_method'] = 0;
			}
			$this->set['comp_level'] = '';
		}
		else
		{
		    $this->set['comp_method'] = $this->compressMethod;
		    $this->set['comp_level'] = '';
		}
		
		$fp = $this->fn_open($file, 'r');
		$this->file_cache = $sql = $table = $insert = '';
        $is_skd = $query_len = $execute = $q =$t = $i = $aff_rows = 0;
		$limit = 300;
        $index = 4;
		$tabs = 0;
		$cache = '';
		$info = array();

		// Установка кодировки соединения
		if ($this->mysql_version > 40101 && (self::CHARSET != 'auto' || $this->forced_charset))
		{   // Кодировка по умолчанию, если в дампе не указана кодировка
			$Db2->MysqlQuery("SET NAMES '" . $this->restore_charset . "'");
			$last_charset = $this->restore_charset;
		}
		else
		{
			$last_charset = '';
		}
		$last_showed = '';
		while (($str = $this->fn_read_str($fp)) !== false)
		{
			if (empty($str) || preg_match("/^(#|--)/", $str))
			{
				if (!$is_skd && preg_match("/^#SKD101\|/", $str))
				{
				    $info = explode('|', $str);
					$is_skd = 1;
				}
        	    continue;
        	}
			$query_len += strlen($str);
            
			$m = array();
			if (!$insert && preg_match("/^(INSERT INTO `?([^` ]+)`? .*?VALUES)(.*)$/i", $str, $m))
			{
				if ($table != $m[2])
				{
				    $table = $m[2];
					$tabs++;
					$cache .= "Таблица `{$table}`.";
					$last_showed = $table;
					$i = 0;
				}
        	    $insert = $m[1] . ' ';
				$sql .= $m[3];
				$index++;
				$info[$index] = isset($info[$index]) ? $info[$index] : 0;
				$limit = round($info[$index] / 20);
				$limit = $limit < 300 ? 300 : $limit;
				if ($info[$index] > $limit)
				{
					$cache = '';
				}
        	}
			else
			{
        		$sql .= $str;
				if ($insert)
				{
				    $i++;
    				$t++;
				}
        	}

			if (!$insert && preg_match("/^CREATE TABLE (IF NOT EXISTS )?`?([^` ]+)`?/i", $str, $m) && $table != $m[2])
			{
				$table = $m[2];
				$insert = '';
				$tabs++;
				$is_create = true;
				$i = 0;
			}
			if ($sql)
			{
			    if (preg_match('/;$/', $str))
			    {
            		$sql = rtrim($insert . $sql, ';');
					if (empty($insert))
					{
						if ($this->mysql_version < 40101)
						{
				    		$sql = preg_replace('/ENGINE\s?=/', 'TYPE=', $sql);
						}
						elseif (preg_match('/CREATE TABLE/i', $sql))
						{
							// Выставляем кодировку соединения
							$charset = array();
							if (preg_match('/(CHARACTER SET|CHARSET)[=\s]+(\w+)/i', $sql, $charset))
							{
								if (!$this->forced_charset && $charset[2] != $last_charset)
								{
									if (self::CHARSET == 'auto')
									{
										$Db2->MysqlQuery("SET NAMES '" . $charset[2] . "'");
										//$cache .= tpl_l("Установлена кодировка соединения `" . $charset[2] . "`.", C_WARNING);
										$last_charset = $charset[2];
									}
								}
								// Меняем кодировку если указано форсировать кодировку
								if ($this->forced_charset)
								{
									$sql = preg_replace('/(\/\*!\d+\s)?((COLLATE)[=\s]+)\w+(\s+\*\/)?/i', '', $sql);
									$sql = preg_replace('/((CHARACTER SET|CHARSET)[=\s]+)\w+/i', '\\1' . $this->restore_charset . $this->restore_collate, $sql);
								}
							}
							elseif (self::CHARSET == 'auto')
							{ // Вставляем кодировку для таблиц, если она не указана и установлена auto кодировка
								$sql .= ' DEFAULT CHARSET=' . $this->restore_charset . $this->restore_collate;
								if ($this->restore_charset != $last_charset)
								{
									$Db2->MysqlQuery("SET NAMES '" . $this->restore_charset . "'");
									$last_charset = $this->restore_charset;
								}
							}
						}
						if ($last_showed != $table)
						{
						    //$cache .= tpl_l("Таблица `{$table}`.");
						    $last_showed = $table;
						}
					}
					elseif ($this->mysql_version > 40101 && empty($last_charset))
					{ // Устанавливаем кодировку на случай если отсутствует CREATE TABLE
						$Db2->MysqlQuery("SET $this->restore_charset '" . $this->restore_charset . "'");
						$last_charset = $this->restore_charset;
					}
            		$insert = '';
            	    $execute = 1;
            	}
            	if ($query_len >= 65536 && preg_match('/,$/', $str))
            	{
            		$sql = rtrim($insert . $sql, ',');
            	    $execute = 1;
            	}
    			if ($execute)
    			{
            		$q++;
            		$Db2->MysqlQuery($sql);
					if (preg_match('/^insert/i', $sql))
					{
            		    $aff_rows += mysql_affected_rows();
            		}
            		$sql = '';
            		$query_len = 0;
            		$execute = 0;
            	}
			}
		}
		
		$this->tabs = $tabs;
		$this->records = $aff_rows;
		$this->size = filesize($this->filename);
		$this->comp = $this->set['comp_method'] * 10 + $this->set['comp_level'];
		
		$this->fn_close($fp);
		
		$App->debug = $appDebug;
	}

	function fn_open($name, $mode)
	{
	    $this->filename = $name;
	    
		if ($this->set['comp_method'] == self::COMPRESS_BZIP2)
		{
			//$this->filename = "{$name}.sql.bz2";
		    return bzopen($this->filename, "{$mode}b{$this->set['comp_level']}");
		}
		elseif ($this->set['comp_method'] == self::COMPRESS_GZIP)
		{
			//$this->filename = "{$name}.sql.gz";
		    return gzopen($this->filename, "{$mode}b{$this->set['comp_level']}");
		}
		else
		{
			//$this->filename = "{$name}.sql";
			return fopen($this->filename, "{$mode}b");
		}
	}

	function fn_write($fp, $str)
	{
		if ($this->set['comp_method'] == self::COMPRESS_BZIP2) bzwrite($fp, $str);
		elseif ($this->set['comp_method'] == self::COMPRESS_GZIP) gzwrite($fp, $str);
		else fwrite($fp, $str);
	}

	function fn_read($fp)
	{
		if ($this->set['comp_method'] == self::COMPRESS_BZIP2) return bzread($fp, 4096);
		elseif ($this->set['comp_method'] == self::COMPRESS_GZIP) return gzread($fp, 4096);
		else return fread($fp, 4096);
	}

	function fn_read_str($fp)
	{
		$string = '';
		$this->file_cache = ltrim($this->file_cache);
		$pos = strpos($this->file_cache, "\n", 0);
		if ($pos < 1)
		{
			while (!$string && ($str = $this->fn_read($fp)))
			{
    			$pos = strpos($str, "\n", 0);
    			if ($pos === false)
    			{
    			    $this->file_cache .= $str;
    			}
    			else
    			{
    				$string = $this->file_cache . substr($str, 0, $pos);
    				$this->file_cache = substr($str, $pos + 1);
    			}
    		}
			if (!$str)
			{
			    if ($this->file_cache)
			    {
					$string = $this->file_cache;
					$this->file_cache = '';
				    return trim($string);
				}
			    return false;
			}
		}
		else
		{
  			$string = substr($this->file_cache, 0, $pos);
  			$this->file_cache = substr($this->file_cache, $pos + 1);
		}
		return trim($string);
	}

	function fn_close($fp)
	{
		if ($this->set['comp_method'] == self::COMPRESS_BZIP2) bzclose($fp);
		elseif ($this->set['comp_method'] == self::COMPRESS_GZIP) gzclose($fp);
		else fclose($fp);
		
		@chmod($this->filename, 0666);
	}

	static function SXD_errorHandler($errno, $errmsg, $filename, $linenum, $vars)
	{
	    if ($errno == 2048) return true;
    	if (preg_match('/chmod\(\).*?: Operation not permitted/', $errmsg)) return true;
        $errmsg = addslashes($errmsg);
        
        GLOBAL $App;
        $App->RaiseError("{$errmsg} ({$errno})");
	}
}

?>