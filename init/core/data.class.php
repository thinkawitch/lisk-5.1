<?php
/**
 * CLASS Data
 * @package lisk
 *
 */
class Data
{
	public $name;			// *current data name
	protected $initFields;		// *flag boolean init fields or not

	// main fields
	public $table;				// current data DB table name
	public $order;				// default ORDER BY ...
	public $fields;			// Fields
	public $label;			// Label (custom human name)
	public $listFields;		// list fields, f.e. CMS list

	public $cfgName;			// Original conf. description array name. Used with data extended objects

	protected $buffer;			// object buffer - save any data

	protected $configArray;		//massiv opisaniia DI

	public $checkString;

	public $values;
	public $value;

	/**
	 * Constructor
	 *
	 * @param string $name
	 * @param boolean $initFields
	 * @param string $customDataName
	 */
	function __construct($name, $initFields=true, $customDataName=null)
	{
		$this->initFields = $initFields;
		$prefix = substr($name, 0, 4);

		if ($prefix == 'dyn_') $this->InitDynamicDataItem($name, $customDataName);
		else $this->InitDataItem($name, $customDataName);
	}

	/**
	 * Create data object
	 *
	 * @param string $name
	 * @return Data
	 */
	public static function Create($name, $initFields=true)
	{
		$prefix = substr($name, 0, 4);
		if ($prefix == 'Obj_')
		{
			$objName = substr($name, 4);
			$objParams = explode('_di_', $objName);

			if (isset($objParams[1])) $obj = new $objParams[0]($objParams[1], $initFields);
			else $obj = new $objName($initFields);
		}
		else $obj = new Data($name, $initFields);

		return $obj;
	}

	private function InitDataItem($name, $customDataName)
	{
		$arr = isset($GLOBALS['DATA_'.strtoupper($name)]) ? $GLOBALS['DATA_'.strtoupper($name)] : null;
		$this->InitDataItemOnArray($name, $arr, $customDataName);
	}

	private function InitDynamicDataItem($name, $customDataName)
	{
		GLOBAL $Db;

		$arr = $Db->Get('name='.Database::Escape($name), 'data', 'sys_di');
		$arr = @unserialize($arr);
		$this->InitDataItemOnArray($name, $arr, $customDataName);
	}

	private function InitDataItemOnArray($name, $arr, $customDataName)
	{
		GLOBAL $App;

		$data = $arr;
		$this->configArray = $data;

		// error if data $name not defined
		if (!Utils::IsArray($data)) $App->RaiseError("DataItem <b>$name</b> is unknown");

		// data debug
		$this->Debug('set', $name);

		// oroginal cfg name
		$this->cfgName = $name;

		// DataItem name
		if ($customDataName == null) $customDataName = $name;
		$this->name = $customDataName;

		// Data Item Label
		$this->label = (isset($data['label']) && strlen($data['label'])) ? $data['label'] : Format::Label($this->name);

		// DB table name
		$this->table = isset($data['table']) ? $data['table'] : null;

		// dfault Order
		$this->order = (isset($data['order']) && $data['order'] != null) ? $data['order'] : 'id';

		// list fields
		$this->listFields = (isset($data['list_fields']) && strlen($data['list_fields'])) ? $data['list_fields'] : 'name';

		// Initialize Fields
		$this->SetFields($data['fields']);

		$this->ReSet(INIT_NAME);
	}

	public function SetFields($arr)
	{
		GLOBAL $App;
		if ($this->initFields===false) return false;

		foreach ($arr as $key=>$row)
		{
			if (is_array($row))
			{
				$row['name'] = $key;
				$type = $row['type'];
			}
			else
			{
				$type = $row;
				$row = array(
					'name' => $key,
					'type' => $row
				);
			}
			$App->Load($type, 'type');
			$className = 'T_'.$type;
			if ($type == 'image')
			{
			   $className = (defined('INIT_NAME') && INIT_NAME!='cp') ? 'T_image_simple' : 'T_image';
			}
			
			$this->fields[$key] = new $className($row, $this);
		}

		$this->InitCheckParams();
		return true;
	}

	public function ReSet($name)
	{
		$name = 'redefine_'.$name;
		if (isset($this->configArray[$name]) && is_array($this->configArray[$name])) $this->SetFields($this->configArray[$name]);
	}

	public function InitCheckParams()
	{
	    $check = array();
		foreach ($this->fields as $obj)
		{
			if ($obj->jsCheckString != '') $check[] =  $obj->jsCheckString;
		}

		if (Utils::IsArray($check)) $check = '[' . implode(',',$check) . ']';
		else $check = '[]';

		$this->checkString = $check;
	}


	/**
	* @return void
	* @param unknown $action
	* @param unknown $params
	* @param unknown $rez
	* @desc Put debug information
	*/
	private function Debug($action, $params=null, $rez=null)
	{
		GLOBAL $App, $Debug;
		if (!$App->debug) return;
		$Debug->AddDebug('DATA', $action, $params, $rez);
	}

	/**
	* @return array 		Array of selected rows.
	* @param string $cond 	Condition for select
	* @param string $order 	Selection order
	* @param string $fields 	Array of fields to select
	* @desc Make SELECT with given condition and order for current data
	*/
	public function Select($cond=null, $fields=null, $order=null)
	{
		GLOBAL $Db;

		// set order
		if ($order == null) $order = $this->order;
		// set DB table
		$Db->SetTable($this->table);
		// get DB select
		$this->values = $Db->Select($cond, $order, $fields);
	}

	public function SelectValues($cond=null, $fields=null, $order=null)
	{
		GLOBAL $Db;

		// set order
		if ($order == null) $order = $this->order;
		// set DB table
		$Db->SetTable($this->table);
		// get DB select

		return $Db->Select($cond, $order, $fields);
	}

	/**
	* @return array 		Row of data
	* @param string $cond 	Condition for select
	* @param string $order 	Selection order
	* @desc Get one row for selected data.
	*/
	public function Get($cond, $fields=null)
	{
		GLOBAL $Db;
		$Db->SetTable($this->table);
		$this->value = $Db->Get($cond, $fields);
	}

	/**
	* @return array 		Row of data
	* @param string $cond 	Condition for select
	* @param string $order 	Selection order
	* @desc Get one row for selected data.
	*/
	public function GetValue($cond, $fields=null)
	{
		GLOBAL $Db;

		$Db->SetTable($this->table);

		return $Db->Get($cond, $fields);
	}

	public function TgerBeforeInsert(&$insertValues)
	{
		if ($this->order == 'oder' && (!isset($insertValues['oder']) || $insertValues['oder'] < 1))
		{
			GLOBAL $Db;
			$insertValues['oder'] = 1 + $Db->Get(null, 'MAX(oder)', $this->table);
		}
		return true;
	}

	public function TgerAfterInsert($newId, $values)
	{
		return true;
	}

	public function TgerBeforeUpdate($cond, &$updateValues)
	{
		return true;
	}

	public function TgerAfterUpdate($cond, $updateValues)
	{
		return true;
	}

	public function TgerBeforeDelete($cond, &$values)
	{
		return true;
	}

	public function TgerAfterDelete($cond, $values)
	{
		return true;
	}

	/**
	* @return int / boolean
	* @param array $values Array of new trigger values (field_name=>value)
	* @desc Insert into database
	*/
	function Insert($values)
	{
		GLOBAL $Db;

		$insertData = array(); // array with data to SQL insert

		foreach ($this->fields as $name=>$obj)
		{
			// set Def Value if no value
			if ((!isset($values[$name]) || $values[$name]==null) && $obj->defValue != null) $values[$name] = $obj->defValue;

			// process insert values
			$rez = $obj->Insert($values);
			if ($rez !== false) $insertData[$name] = $rez;
		}

		// execute before insert trigger
		if ($this->TgerBeforeInsert($insertData) === false)
		{
			return false;
		}

		$id = $Db->Insert($insertData, $this->table);

		// execute after insert trigger
		$this->TgerAfterInsert($id, $insertData);

		return $id;
	}

	/**
	* @return unknown
	* @param string $cond
	* @param array $values Array of new trigger values (field_name=>value)
	* @param unknown $flags
	* @desc Enter description here...
	*/
	function Update($cond, $values)
	{
		GLOBAL $Db;
		$Db->SetTable($this->table);

		$updateData = array(); // array with data to SQL update

		foreach ($this->fields as $name=>$obj)
		{
			// skip ID field
			if ($name == 'id' || !isset($values[$name])) continue;

			$rez = $obj->Update($values);
			if ($rez!==false) $updateData[$name] = $rez;
		}

		// execute before update trigger
		if ($this->TgerBeforeUpdate($cond, $updateData)===false)
		{
			return false;
		}

		$isUpdated = $Db->Update($cond, $updateData);

		//execute after update tger
		$this->TgerAfterUpdate($cond, $updateData);

		return $isUpdated;
	}

	function Replace($values)
	{
	    GLOBAL $Db;
		$Db->SetTable($this->table);
		
		$replaceData = array(); // array with data to SQL update
		
	    foreach ($this->fields as $name=>$obj)
		{
			if (!isset($values[$name])) continue;

			$rez = $obj->Insert($values);
			if ($rez !== false) $replaceData[$name] = $rez;
		}
		
		//TODO add triggers

		$isUpdated = $Db->Replace($replaceData);

		return $isUpdated;
	}


	/**
	* @return boolean
	* @param string $cond
	* @desc delete data
	*/
	function Delete($cond)
	{
		GLOBAL $Db;

		$values = $Db->Select($cond, null, null, $this->table);

		// execute before delete trigger
		if ($this->TgerBeforeDelete($cond, $values) === false)
		{
			return false;
		}

		$isDeleted = $Db->Delete($cond, $this->table);

		foreach ($this->fields as $obj)
		{
			$obj->delete($values);
		}

		// execute After Delete Trigger
		$this->TgerAfterDelete($cond, $values);

		return $isDeleted;
	}


	/**
	* Returns list of fields with specified types
	*
	* @param string $types. Type names by comma i.e. input,text,etc
	* @return string found fileds by comma i.e. name,desc,age
	*/
	function GetFieldsByType($types)
	{
		$rez = array();
		$types = explode(',', $types);
	    foreach ($this->fields as $obj)
		{
		    foreach ($types as $type)
		    {
    		    if (strtolower(get_class($obj)) == strtolower('t_'.$type))
    			{
    				$rez[] = $obj->name;
    			}
		    }
		}
		return implode(',', $rez);
	}
	
	/**
	 * Clear values for all fields
	 *
	 */
	function ClearFieldsValues()
	{
	    if (Utils::IsArray($this->fields)) foreach ($this->fields as $obj) $obj->value = null;
	}
}

?>