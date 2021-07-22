<?php
/**
 * Lisk Type Category
 * @package lisk
 *
 */

class T_category extends LiskType
{
    const OBJ_TYPE_NODE = 1;
    const OBJ_TYPE_POINT = 2;
    
	public $treeName;
	public $nodeName;
    public $pointName;
	public $nodeTable;
	public $nodeOrder;
	public $pointTable;
	public $currentObjectType;

	public $crossField;
	public $addValues;

	public $viewRoot = false;

    public $delimiter = ' &raquo; ';
    public $chooseble = ' style="background-color:#E0FFA9" ';
    public $disabled  = ' disabled="disabled"';

	function __construct(Array $info, Data $dataItem)
	{
		parent::__construct($info, $dataItem);
		GLOBAL $App;

		$this->treeName		= $info['object'];
		$this->crossField	= isset($info['cross_field']) ? $info['cross_field'] : 'name';
		$this->addValues	= isset($info['add_values']) ? $info['add_values'] : array();

		// init tree related variables
		$tree = $App->ReadTree($this->treeName);

		$this->nodeName 	= $tree['node'];
		$this->pointName	= $tree['point'];

		// Init Tree DataItems
		$Node = Data::Create($this->nodeName, false);
		$Point = Data::Create($this->pointName, false);

		$this->nodeTable	= $Node->table;
		$this->nodeOrder	= $Node->order;
		$this->pointTable	= $Point->table;
		
		$this->currentObjectType = ($this->dataItem->cfgName == $Point->cfgName) ? self::OBJ_TYPE_POINT : self::OBJ_TYPE_NODE;

		$this->type			= LiskType::TYPE_CATEGORY;
		$this->tplFile		= 'type/category';
	}

	function RenderFormView()
	{
		switch ($this->formRender)
		{
			case 'tpl':
				return $this->RenderFormTplView();
				break;
			default:
				return "<input name=\"{$this->name}\" value=\"{$this->value}\" ".$this->RenderFormParams()." />";
				break;
		}
	}

	function RenderFormTplView()
	{
		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

		$arr = $this->MakeCategoryArray(0, 1);

		if (Utils::IsArray($arr)) $arr = array_merge_recursive($this->addValues, $arr);

		$tpl->SetCurrentBlock('form_list_row');
		foreach ($arr as $key=>$item)
		{
			if (is_array($item))
			{
				$key = $item['id'];
				$name = $item['name'];
				$points = @$item['points'];
				$subdirs = @$item['subdirs'];
			}

			$chooseble = '';
			$disabled = '';
			if ($this->currentObjectType == self::OBJ_TYPE_NODE)
			{
                // working with node structure
                /*&& $item['id']!=$value*/
			    if ($points==0)
			    {
			    	//don't allow to set parent to itself how??
                	$chooseble = $this->chooseble;
			    }
			    else
			    {
			    	$key=0;
			    	$disabled = $this->disabled;
			    }
			}
			else
			{
			    // working with items
			    if ($subdirs!=0)
			    {
			    	$key=0;
			    	$disabled=  $this->disabled;
			    }
			    else
			    {
					$chooseble = $this->chooseble;
				}
			}

			$tpl->SetVariable(array(
			    'CHOOSEBLE' => $chooseble,
			    'DISABLED'	=> $disabled,
				'CAPTION'	=> $name,
				'VALUE'		=> $key,
				'SELECTED'	=> (strlen($this->value) && $key==$this->value)?' selected':null
			));
			$tpl->ParseCurrentBlock();
		}

		$tpl->SetcurrentBlock('form');
		$tpl->SetVariable(array(
			'NAME'		=> $this->name,
			'PARAMS'	=> $this->RenderFormParams(),
		));
		$tpl->ParseCurrentBlock();

		return $tpl->Get();
	}

	function RenderView($param1=null, $param2=null)
	{
		$node = Data::Create($this->nodeName, false);

        $parents = $node->GetValue('id='.Database::Escape($this->value), 'parents') . "<{$this->value}>";
        $parentsCond = Utils::TreeToIn($parents);
		$rows = $node->SelectValues("id IN $parentsCond", "id,{$this->crossField}");

        //sort result by known parents
		$new_rows = array();
		$hash  = Utils::ListToHash($rows, 'id', $this->crossField);
		$parents_ids = Utils::TreeToArray($parents);
        foreach($parents_ids as $v)
        {
        	if (isset($hash[$v])) $new_rows[$v] = $hash[$v];
        }

        if (!$this->viewRoot)
        {
			reset($new_rows);
			unset($new_rows[key($new_rows)]); //unset first
        }

        //back from hash to array
        $rows = array_values($new_rows);

        $rez = (Utils::IsArray($rows)) ? implode($this->delimiter,$rows) : '';
        return $rez;
	}
    
    function Insert(&$values)
	{
		GLOBAL  $App,$Db;
		
		$fieldValue = isset($values[$this->name]) ? $values[$this->name] : 0;
	    if ($fieldValue == 0)
	    {
			$App->RaiseError('T_category->Insert() '.$this->name.'='.$fieldValue);
		}
		
	    $parentRecord = $Db->Get('id='.Database::Escape($values['parent_id']), 'id,parent_id,parents,url', $this->nodeTable);
        if (!$parentRecord)
        {
            $App->RaiseError('T_category->Insert()  parent record not found!');
        }

		//set new parents into &$values !
		$values['parents'] = $parents;
		$values['url'] = $parentRecord['url'] . Format::ToUrl($values['name']).'/';

		return $fieldValue;
	}
	
	function Update(&$values)
	{
		GLOBAL $Db,$App;

		// check if id is specified
		if (!isset($values['id'])) $App->RaiseError('Type Category Update Error. Update values do not contain id field.');

		//get old record, to determine what was previous parent_id
		$previous = $Db->Get('id='.Database::Escape($values['id']), 'id,parent_id,parents,name,url', $this->dataItem->table);
		if (!Utils::IsArray($previous)) $App->RaiseError('T_category->Update() previous row error! (The parent_id is not found)');

		$arr = $this->MakeCategoryArray(0, 0);
		$new_value = $previous['parent_id'];

		if ($this->currentObjectType == self::OBJ_TYPE_NODE)
		{ //node
			if($values['parent_id']>0 && $arr[$values['parent_id']]['points'] == 0 )
			{
				//don't allow to set parent to itself
				if ($values['id'] != $values['parent_id'])
				{
					$new_value = $values['parent_id'];
				}
			}
		}
    	else
    	{
    		//POINT update
    		//
			if($values['parent_id']>0 && $arr[$values['parent_id']]['subdirs'] == 0)
			{
				$new_value = $values['parent_id'];
			}
		}

    		//update parents
    		$parents = $this->MakeParentsStr($new_value, $arr);

    		//set new parents into &$values !
    		$values['parents'] = $parents;

    		//set new url into &$values !
		    $values['url'] = $this->UpdateUrls($previous, $values);

    		$sql = "UPDATE {$this->dataItem->table} SET parent_id=$new_value, parents='$parents' WHERE id=".Database::Escape($values['id']);
    		$Db->query($sql);

    		//array with updated record
    		$arr2 = $this->MakeCategoryArray(0, 0);

    		if ($this->currentObjectType == self::OBJ_TYPE_NODE)
    		{ //node
	    		//update children parents
	    		$children = $this->GetNodeChildren($previous['id'], $arr2);
	    		if (Utils::IsArray($children))
	    		{
	    			foreach($children as $v)
	    			{
	    				$parents_children = $this->MakeParentsStr($v['parent_id'], $arr2);
	    				//update nodes
	    				$sql = "UPDATE {$this->nodeTable} SET parents='$parents_children' WHERE parent_id=".Database::Escape($v['parent_id']);
	    				$Db->Query($sql);
	    				/*//update points, no need in case we have points only in deepest nodes
	    				$sql = "UPDATE $point_table SET parents='$parents_children' WHERE parent_id={$v['parent_id']}";
	    				$db->query($sql);*/
	    			}
	    		}

	    		//update  points parents
	    		$sql = "UPDATE {$this->pointTable} SET parents='$parents<{$values['id']}>' WHERE parent_id=".Database::Escape($values['id']);
				$Db->Query($sql);
    		}
    		else
    		{ //point

    		}

		return $new_value;
	}

	

	function Delete(&$values)
	{
		if ($this->currentObjectType == self::OBJ_TYPE_NODE)
		{
			if (Utils::IsArray($values))
			{
				foreach($values as $row)
				{
				    if (!Utils::IsArray($row)) continue;
					//delete all data and files
				    $node = Data::Create($this->nodeName);
				    $node->Delete("parents LIKE '%<{$row['id']}>%'");
				    $point = Data::Create($this->pointName);
				    $point->Delete("parents LIKE '%<{$row['id']}>%'");
				}
				return true;
			}
			else return false;
		}
		else return true;
	}

	private function UpdateUrls($values, $newValues)
	{
		GLOBAL $Db;

		$parent = $Db->Get('id='.Database::Escape($newValues['parent_id']), 'id,url', $this->nodeTable);
		
		//if we change category and not update (have name) we use current (old) one
		if (!strlen($newValues['name']))
		{
			$newValues['name'] = $values['name'];
		}

		$url_subject = $values['url'];
		$url_replace = $parent['url'] . Format::ToUrl($newValues['name']) . '/';

		if ($this->currentObjectType == self::OBJ_TYPE_NODE)
		{
			$len = strlen($url_subject) + 1;
			$Db->query("UPDATE {$this->nodeTable} SET url = CONCAT('{$url_replace}', SUBSTRING(url, $len)) WHERE parents LIKE '%<{$values['id']}>%'");
			$Db->query("UPDATE {$this->pointTable} SET url = CONCAT('{$url_replace}', SUBSTRING(url, $len)) WHERE parents LIKE '%<{$values['id']}>%'");
		}

		return $url_replace;
	}

	private function MakeParentsStr($start_from, $treeArr)
	{
		$parents = '';
		while ($start_from!=0)
		{
            $parents .= "<{$treeArr[$start_from]['id']}>";
            $start_from = $treeArr[$start_from]['parent_id'];
		}
		//convert parents into array, and reverse it to be in right order
		$new_parents = array_reverse(Utils::TreeToArray($parents));
		//convert parents array into string
		$parents = '';
		if (Utils::IsArray($new_parents))
		{
            foreach($new_parents as $p) $parents .= "<$p>";
		}
		return $parents;
	}

	private function GetNodeChildren($id, &$treeArr)
	{
		static $rez = array();
		foreach($treeArr as $k=>$v)
		{
			if ($treeArr[$k]['parent_id']==$id)
			{
				$rez[$k] = $v;
				$this->GetNodeChildren($v['id'], $treeArr);
			}
		}
		return $rez;
	}

	private function NodeSort($parent, $rows)
	{
		STATIC $rez;
		foreach($rows as $row)
		{
			if ($row['parent_id']==$parent)
			{
			    $row['name'] = str_repeat("&nbsp;", substr_count($row['parents'],">")*2).$row[$this->crossField];
				$rez[$row['id']] = $row;
				$this->NodeSort($row['id'], $rows);
			}
		}
		return $rez;
	}

	private function MakeCategoryArray($parent_id=0, $mode=0)
	{
		GLOBAL $Db;

		if ($mode == 1)
		{
			switch ($this->currentObjectType)
			{
				case self::OBJ_TYPE_POINT:
					$sql = "
SELECT n.id,n.parent_id,n.parents,n.{$this->crossField}, COUNT(n2.id) AS subdirs
FROM {$this->nodeTable} as n
LEFT JOIN {$this->nodeTable} as n2 ON n2.parent_id=n.id
WHERE n.id>0 GROUP BY n.id ORDER BY n.{$this->nodeOrder}";
					break;
					
				case self::OBJ_TYPE_NODE:
				    $sql = "
SELECT n.id,n.parent_id,n.parents,n.{$this->crossField}, COUNT(p1.id) AS points
FROM {$this->nodeTable} as n
LEFT JOIN {$this->pointTable} as p1 ON p1.parent_id=n.id
WHERE n.id>0 GROUP BY n.id ORDER BY	n.{$this->nodeOrder}";
					break;
			}
		}
		else
		{
			$sql = "
SELECT n.id,n.parent_id,n.parents,n.{$this->crossField}, COUNT(p1.id) AS points, COUNT(n2.id) AS subdirs
FROM {$this->nodeTable} as n
LEFT JOIN {$this->pointTable} as p1 ON p1.parent_id=n.id
LEFT JOIN {$this->nodeTable} as n2 ON n2.parent_id=n.id
WHERE n.id>0 GROUP BY n.id ORDER BY n.{$this->nodeOrder}";
		}

		$rows = $Db->Query($sql);
		$arr = $this->NodeSort($parent_id, $rows);
		return $arr;
	}

}

?>