<?php
/**
 * Lisk Type Tree
 * @package lisk
 *
 */

class T_tree extends LiskType
{
	public $table;
    public $order;
    public $currentObjectType = 1;		// u tree vsegda tolko nodes !!!

	public $crossField;
	public $addValues;

	public $viewRoot = false;

    public $delimiter = ' &raquo; ';
    public $chooseble = ' style="background-color:#E0FFA9" ';

    public $categoryCond = null;
    public $categoryFields = null;
    
    public $diObject;

	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		
		$this->type = LiskType::TYPE_TREE;
		$this->tplFile = 'type/tree';
		
		$this->crossField = isset($info['cross_field']) ? $info['cross_field'] : 'name';
		$this->addValues  = isset($info['add_values']) ? $info['add_values'] : array();
		$this->categoryCond = isset($info['category_cond']) ? $info['category_cond'] : '1==1';
		
		if (strlen($this->categoryCond))
		{
			$matches = array();
			preg_match_all('/(?<=\[)[^\[]+(?=\])/i', $this->categoryCond, $matches);
			$foundFields = $matches[0];
			if (Utils::IsArray($foundFields))
			{
				foreach ($foundFields as $field)
				{
					$this->categoryFields .= 'n.'.$field.',';
				}
			}
		}
        
		$this->diObject = Data::Create($info['object'], false);
		$this->table = $this->diObject->table;
		$this->order = $this->diObject->order;
	}

	function RenderFormView()
	{
		switch ($this->formRender)
		{
			case 'tpl':
				return $this->RenderFormTplView();
				break;

			default:
				return "<input  name=\"{$this->name}\" value=\"{$this->value}\" ".$this->RenderFormParams()." />";
				break;
		}
	}

	function RenderFormTplView()
	{
		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

		$arr = $this->MakeCategoryArray(0);

		if (Utils::IsArray($arr))
		{
			$arr = array_merge_recursive($this->addValues, $arr);
		}

		$tpl->SetCurrentBlock('form_list_row');
		foreach ($arr as $key=>$item)
		{
			if (is_array($item))
			{
				$key = $item['id'];
				$name = $item['name'];
			}

			$chooseble = '';

			if (strlen($this->categoryCond))
			{
				$conditionPassed = true;
				$evalStr = Format::String($this->categoryCond, $item);
				eval( "\$conditionPassed = ( $evalStr);");

				if ($conditionPassed) $chooseble = $this->chooseble;
				else $key = 0;
			}

			$tpl->SetVariable(array(
			    'CHOOSEBLE' => $chooseble,
				'CAPTION'	=> $name,
				'VALUE'		=> $key,
				'SELECTED'	=> (strlen($this->value) && $key == $this->value) ? ' selected' : null
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
        $parents = $this->diObject->GetValue("id={$this->value}", 'parents')."<{$this->value}>";
        $parentsCond = Utils::TreeToIn($parents);
		$rows = $this->diObject->SelectValues("id IN $parentsCond", "id,{$this->crossField}");

        //sort result by known parents
		$new_rows = array();
		$hash  = Utils::ListToHash($rows, 'id', $this->crossField);
		$parents_ids = Utils::TreeToArray($parents);
        foreach ($parents_ids as $v)
        {
        	if (isset($hash[$v]))
        	{
        		$new_rows[$v] = $hash[$v];
        	}
        }

        if (!$this->viewRoot)
        {
			reset($new_rows);
			unset($new_rows[key($new_rows)]); //unset first
        }

        //back from hash to array
        $rows = array_values($new_rows);

        $rez = (Utils::IsArray($rows)) ? implode($this->delimiter, $rows) : '';
        
        return $rez;
	}
	
	function Insert(&$values)
	{
		GLOBAL  $App;

		$fieldValue = @$values[$this->name];
		
	    if ($fieldValue==0)
	    {
			$App->RaiseError('T_tree->Insert() '.$this->name.'='.$fieldValue);
		}
		
		// get array we need ???
		$arr = $this->MakeCategoryArray(0);

		//update parents
		$parents = '';
		$start_from = $fieldValue;
		while ($start_from!=0)
		{
            $parents .= "<{$arr[$start_from]['id']}>";
            $start_from = $arr[$start_from][$this->name];
		}
		//convert parents into array, and reverse it to be in right order
		$new_parents = array_reverse(Utils::TreeToArray($parents));

		//convert parents array into string
		$parents = '';
		if (Utils::IsArray($new_parents))
		{
            foreach($new_parents as $p) $parents .= "<$p>";
		}
		
		//set new parents into &$values !
		$values['parents'] = $parents;

		return $fieldValue;
	}

	function Update(&$values)
	{
		GLOBAL $Db, $App;

		//get old record, to determine what was previous parent_id
		$previous = $Db->Get('id='.$values['id'], null, $this->dataItem->table);

		if (Utils::IsArray($previous))
		{
			$arr = $this->MakeCategoryArray(0);
    		$new_value = $previous[$this->name];

            //don't allow to set parent_id to itself
            if ($values['id'] != $values[$this->name])
            {
                $new_value = $values[$this->name];
            }

    		//update parents
    		$parents = $this->MakeParentsStr($new_value, $arr);

    		//set new parents into &$values !
    		$values['parents'] = $parents;

    		$sql = "UPDATE {$this->diObject->table} SET {$this->name}=$new_value, parents='$parents' WHERE id={$values['id']}";
    		$Db->Query($sql);

    		//array with updated record
    		$arr2 = $this->MakeCategoryArray(0);

    		if ($this->currentObjectType == 1)
    		{   //node, always
	    		//update children parents
	    		$children = $this->GetNodeChildren($previous['id'], $arr2);
	    		if (Utils::IsArray($children))
	    		{
	    			foreach($children as $v)
	    			{
	    				$parents_children = $this->MakeParentsStr($v[$this->name], $arr2);
	    				//update nodes
	    				$sql = "UPDATE {$this->table} SET parents='$parents_children' WHERE parent_id={$v[$this->name]}";
	    				$Db->Query($sql);
	    			}
	    		}
    		}

    		return $new_value;
		}

		$App->RaiseError('T_tree->Update() previous row error!');
	}

	function Delete(&$values)
	{
		if (Utils::IsArray($values))
		{
			foreach($values as $row_to_delete)
			{
				if(Utils::IsArray($row_to_delete))
				{
					$di = Data::Create($this->dataItem->name);
					$di->Delete("parents LIKE '%<{$row_to_delete['id']}>%'");
				}
			}
			return true;
		}
		else return false;
	}

	private function MakeParentsStr($start_from, $treeArr)
	{
	    $parents = '';
		while ($start_from!=0)
		{
            $parents .= "<{$treeArr[$start_from]['id']}>";
            $start_from = $treeArr[$start_from][$this->name];
		}
		//convert parents into array, and reverse it to be in right order
		$new_parents = array_reverse(Utils::TreeToArray($parents));
		//convert parents array into string
		$parents = '';
		if (Utils::IsArray($new_parents))
		{
            foreach($new_parents as $p)  $parents .= "<$p>";
		}
		return $parents;
	}

	private function GetNodeChildren($id, &$treeArr)
	{
		static $rez = array();
		foreach($treeArr as $k=>$v)
		{
			if ($treeArr[$k][$this->name] == $id)
			{
				$rez[$k] = $v;
				$this->GetNodeChildren($v['id'], $treeArr);
			}
		}
		return $rez;
	}

	private function NodeSort($parent, $rows)
	{
		STATIC $rez = array();
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

	private function MakeCategoryArray($parentId=0)
	{
		GLOBAL $Db;

			$sql = "
SELECT
	n.id,n.parent_id,n.parents,{$this->categoryFields} n.{$this->crossField}
FROM {$this->table} as n
WHERE n.id>0
GROUP BY n.id
ORDER BY n.{$this->order}";

		$rows = $Db->Query($sql);
		$arr = $this->NodeSort($parentId, $rows);
		
		return $arr;
	}

}
?>