<?php
/**
 * CLASS Export
 * @package lisk
 *
 * Used for export data from tables in list CMS module
 * Accept two formats to export and import data dumps:XML & CSV
 *
 * -----------------
 * Export CSV format
 * -----------------
 * example:
 * 1,some data,"comma test, will work","some text:""Oh, my GOD!"""\r\n
 * 2,some data,"comma test, will work","some text:""Oh, my GOD!"""\r\n
 * 3,some data,"comma, will work","some text:""Oh, my GOD!"""\r\n
 *
 * -----------------
 * Export XML format
 * -----------------
 * example:
 * <DatabaseName>
 * 	<TableName>
 * 		<fieldName1>Value</fieldName1>
 * 		<fieldName2>Value</fieldName2>
 * 		<fieldName3>Value</fieldName3>
 * 		<fieldName4>Value</fieldName4>
 * 	</TableName>
 * 	<TableName>
 * 		<fieldName1>Value</fieldName1>
 * 		<fieldName2>Value</fieldName2>
 * 		<fieldName3>Value</fieldName3>
 * 		<fieldName4>Value</fieldName4>
 * 	</TableName>
 * </DatabaseName>
 *
 */
class Export
{
    const FORMAT_CSV = 1;
    const FORMAT_XML = 2;
    
    const EXPORT_DATABASE = 1; //using now
    const EXPORT_DATAITEM = 2; //TODO
    
	/**
	 * DataItem Object
	 *
	 * @var Data
	 */
	public $dataItem = null;
	
	/**
	 * Store fields names which have DataItem
	 *
	 * @var array
	 */
	public $fieldsNames = array();
	
	/**
	 * Output var store all data for export method
	 *
	 * @var string
	 */
	private $output = null;
	
	private $cond = null;
	
	/**
	 * file extension for exported file
	 *
	 * @var string
	 */
	private $fileExt;
	
	/**
	 * method to generete export data
	 *
	 * @var string
	 */
	private $exportMethod;
	
	/**
	 * Flag, if to put fields names in the first row for csv export
	 *
	 * @var boolean
	 */
	public $csvFields;
	
	/**
	 * Flag, if to export name instead of id
	 *
	 * @var boolean
	 */
	public $realLists;
	
	private $listFieldsTypes;
	
	function __construct($dataItem)
	{
		GLOBAL $Db,$App;
		if (!($dataItem instanceof Data)) $dataItem = Data::Create($dataItem);
		$this->dataItem = $dataItem;
		
		if ($this->dataItem->table=='') $App->RaiseError('Export. DataItem '.$dataItem->name.' doesn\'t exist or no table found');
		
		$this->fieldsNames = $Db->Query('SHOW COLUMNS FROM '.$this->dataItem->table);
		
		$this->listFieldsTypes = array(LiskType::TYPE_LIST, LiskType::TYPE_PROP, LiskType::TYPE_CATEGORY, LiskType::TYPE_RADIO, LiskType::TYPE_FLAG);
	}

	// SYSTEM PUBLIC METHODS
	
	public function GetFieldsFromDB()
	{
		return $this->fieldsNames;
	}
	
	public function SetCond($cond)
	{
		$this->cond = $cond;
	}
    
	/**
	 * Init
	 *
	 * @param integer $format
	 */
	public function Init($format)
	{
	    GLOBAL $App;
	    
	    switch ($format)
		{
			case self::FORMAT_XML:
				$this->fileExt = 'xml';
				$this->exportMethod = 'ExportXml';
				break;
				
			case self::FORMAT_CSV:
				$this->fileExt = 'csv';
				$this->exportMethod = 'ExportCsv';
				break;
				
			default:
			    $App->RaiseError('Incorrect format for '.$this->dataItem->name);
		}
	}
	
	/**
	 * EXPORT METHODS
	 * ==============
	 */
	
	/**
	 * Export data, the data goes directly to stdout
	 *
	 * @param integer $format
	 * @param array $arrFields
	 */
	public function MakeExport($format, $arrFields)
	{
		GLOBAL $Db;
		
		$this->Init($format);
		
		$fields = '';
		foreach ($arrFields as $field)
		{
		    $fields .= '`'.$field.'`,';
		}
		$fields = substr($fields, 0, -1);
		
		//direct output, prevent memory limit problem
	    $this->HeaderExport();
		
	    $sql = 'SELECT '.$fields.' FROM '.$this->dataItem->table;
		if (strlen($this->cond)) $sql .= ' WHERE '.$this->cond;
		
		//get resource, export row by row to prevent memory limit problem
		$res = $Db->MysqlQuery($sql);
		
		if (!$res) exit();
		
		$this->{$this->exportMethod}($res);
		
		mysql_free_result($res);
		exit();
	}
	
	/**
	 * Export to csv format
	 *
	 * @param resource $res
	 */
	private function ExportCsv($res)
	{
	    $firstDone = false;
	    
        while (false!== ($row = mysql_fetch_array($res, MYSQL_ASSOC)))
    	{
    		$row = Utils::StripSlashes($row);
    		
    		$line = '';
    		
        	if ($this->csvFields && !$firstDone)
            {
                // put fields names
                $names = array_keys($row);
    		    $line .= implode(',', $names);
    		    $line .= "\r\n";
                $firstDone = true;
            }
    		
            $i=1;
            $fieldsCount = count(array_keys($row));
            
            foreach ($row as $fieldName=>$field)
    		{
    			if ($i<$fieldsCount) $add = ',';
    			else $add = '';
    			
    			$field = $this->ExportFieldValue($fieldName, $field);
    			
    			$field = str_replace('"', '""', $field);
    			
    			if (strstr($field, '"') or strstr($field, ',') or strstr($field, "\n")) $line .= '"'.$field.'"'.$add;
    			else $line .= $field.$add;
    				
    			$i++;
    		}
    		$line .= "\r\n";
    		
    		echo $line;
    	}
	}
	
	/**
	 * Export to xml format
	 *
	 * @param resource $res
	 */
	private function ExportXml($res)
	{
	    GLOBAL $Db;
		echo '<?xml version="1.0" encoding="utf-8" ?>';
		echo '<'.$Db->dbname.">\n";
		
		while (false!== ($row = mysql_fetch_array($res, MYSQL_ASSOC)))
    	{
    		$row = Utils::StripSlashes($row);
    		
    		echo '<'.$this->dataItem->table.">\n";
    	    
    		foreach ($row as $fieldName=>$fieldValue)
			{
			    $fieldValue = $this->ExportFieldValue($fieldName, $fieldValue);
			    
				echo '<'.$fieldName.'><![CDATA['.$fieldValue.']]></'.$fieldName.">\n";
			}
    		
    		echo '</'.$this->dataItem->table.">\n";
    	}
				
		echo '</'.$Db->dbname.'>';
	}
    
	/**
	 * convert list id value to linked string value
	 *
	 * @param string $fieldName
	 * @param string $fieldValue
	 * @return string
	 */
	private function ExportFieldValue($fieldName, $fieldValue)
	{
	    static $cache;
	    if ($this->realLists && isset($this->dataItem->fields[$fieldName]))
		{
		    $obj = $this->dataItem->fields[$fieldName];
		    if (in_array($obj->type, $this->listFieldsTypes))
		    {
		        if (isset($cache[$fieldName]) && isset($cache[$fieldName][$fieldValue])) return $cache[$fieldName][$fieldValue];
		    
		        if (!isset($cache[$fieldName])) $cache[$fieldName] = array();
		    
		        $obj->value = $fieldValue;
		        $cache[$fieldName][$fieldValue] = $obj->Render();
		        return $cache[$fieldName][$fieldValue];
		    }
		}
		return $fieldValue;
	}
	
    private function HeaderExport()
	{
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$this->dataItem->name.'_export.'.$this->fileExt.'"');
	}
	
}