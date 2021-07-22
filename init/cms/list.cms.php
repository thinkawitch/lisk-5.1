<?php
/**
 * CMS List
 * @package lisk
 *
 */

class CMSListColumn
{
    public $name;
    public $isSortable = true;
    
    function __construct($name)
    {
        $this->name = $name;
    }
}

class CMSList extends CMSCore
{
    /**
     * @var Data
     */
	public $dataItem;

	//var $tpl;
	public $columns;		// *View columns
	public $cond;			// *List select cond
	public $order;			// *ORDER BY for current list
	public $tplName;		// *template name to use

	public $hiddenVariables;	// used for link generation to add.php

	public $linkFields;	// Array of fields that are a link
	public $linkFieldsByCond;	// Array of fields that are a link only if condition passed

	public $addButtons = array();	// Array of add buttons (near view,delte,edit)
	public $removeButtons = array(); //hash name=>cond

	public $buttonAdd 	= true;
	public $buttonOrder = true;
	public $buttonView 	= true;
	public $buttonEdit 	= true;
	public $buttonDelete = true;
	public $buttonCheckbox = true;

	public $buttonDeleteAll = true;
	public $buttonExport    = true;

	public $inlineOrder = false;

	public $buttonSearch = true;
	public $searchFields = null;
	public $searchMatch = null;
	
	public $alphabeticNavigation = false;
	public $alphabeticField = null;

	public $listDecoration1 = 'ListTD1';
	public $listDecoration2 = 'ListTD2';

	public $back;

	/**
	 * if true - delete_selected must be handled by custom function
	 *
	 * @var boolean
	 */
	public $customDeleteSelected = false;
    
	public $handlerPostProcess = null;  //handler called after data are selected
	public $handlerParseRow = null;     //handler called on render each row

	function __construct($dataItem)
	{
		parent::__construct();
		
		GLOBAL $App;
		$App->Load('cpmodules', 'lang');
		if (!($dataItem instanceof Data)) $dataItem = Data::Create($dataItem);
		$this->dataItem = $dataItem;

		$this->SetColumns();
		$this->SetSearchFields();
		$this->SetTemplate();
	}
	
	function Init()
	{
	    //alphabetic navigation
	    if ($this->alphabeticNavigation && !strlen($this->alphabeticField))
		{
			$this->alphabeticField = $this->columns[0]->name;
		}
		
		// sort out ordering
		$this->order = (isset($_GET['order']) && $_GET['order']!='') ? $_GET['order'] : $this->dataItem->order;
		if (isset($_GET['order_type']) && $_GET['order_type'] == 2)
		{
			$this->order .= ' DESC';
		}
		
		//if to enable inline order
		if (strtolower($this->dataItem->order) == 'oder') $this->inlineOrder = true;
	}

	function DeleteSelected()
	{
		foreach (@$_POST['ids'] as $id)
		{
            $this->dataItem->Delete('id='.Database::Escape($id));
		}
		Navigation::Jump(Navigation::Referer());
	}

	function SetFieldLink($name, $link)
	{
		$this->linkFields[]=array(
			'field'	=> $name,
			'link'	=> $link,
		);
	}

	/**
	 * Set linked field by conditions
	 *
	 * @param string $name Field name
	 * @param string $link Link URL
	 * @param string $cond Condition i.e. [page_type]=1
	 */
	function SetFieldLinkByCond($name, $link, $cond)
	{
		$this->linkFieldsByCond[] = array(
			'field'	=> $name,
			'link'	=> $link,
			'cond'  => $cond,
		);
	}

	private function GetAvailableLetters()
	{
		GLOBAL $Db;
		return $Db->Query('SELECT DISTINCT (UPPER(left(`'.$this->alphabeticField.'`, 1))) AS `letter`FROM `'.$this->dataItem->table.'` WHERE `'.$this->alphabeticField.'` <> \'\' ORDER BY `'.$this->alphabeticField.'`');
	}

	
	function AddButton($name, $link, $hint=null, $icon=null)
	{
		$this->addButtons[] = array(
			'name'	=> $name,
			'link'	=> $link,
			'hint'	=> $hint,
			'icon'  => $icon,
		);
	}

	function RemoveButton($name,$cond)
	{
		$this->removeButtons[$name] = $cond;
	}

	function FormatFieldLinks($fieldName, $fieldValue, $row)
	{
		if (!Utils::IsArray($row)) return '';

		$fieldLink = $fieldValue;

		//array to store cells changed by linkFieldsByCond
		$originalValues = array();

		//links with condition first
		if (Utils::IsArray($this->linkFieldsByCond))
		{
			foreach ($this->linkFieldsByCond as $params)
			{
				if ($fieldName == $params['field'])
				{
					if (strlen($params['cond']))
					{
						$conditionPassed = true;
						$cond = Format::String($params['cond'], $row);
						$evalStr = '$conditionPassed = ('.$cond.');';
						eval($evalStr);
						if ($conditionPassed)
						{
							$formatArr = array_merge($row, array(
								'back'	=> $this->back
							));
							$link = Format::String($params['link'], $formatArr);
							$originalValues[$fieldName] = $row;
							$fieldLink = "<a href=\"$link\">$fieldValue</a>";
						}
					}
				}
			}
		}

		//common links second, parse only not linked by condition
		if (Utils::IsArray($this->linkFields))
		{
			foreach ($this->linkFields as $params)
			{
				if ($fieldName==$params['field'] && !isset($originalValues[$fieldName]))
				{
					$formatArr = array_merge($row,array(
						'back'	=> $this->back
					));
					$link = Format::String($params['link'], $formatArr);
					$fieldLink = "<a href=\"$link\">$fieldValue</a>";
				}
			}
		}

		return $fieldLink;
	}

	function MakeLinkButtons()
	{
		GLOBAL $Page;

		// Add Link
		if ($this->buttonAdd)
		{
			$addUrl = "add.php?type={$this->dataItem->name}&back={$Page->setBack}&{$this->hiddenVariables}";
			$addUrl .= $this->GetRequiredUrlVars();
			$Page->AddLink($Page->Message('main', 'add').' '.$this->dataItem->label,$addUrl, 'img/ico/links/add.gif', 'Add new record to the list.');
		}

		// Link to show alphabetic
		if ($this->alphabeticNavigation)
		{
			$addUrl = "javascript:menuSlide('alphabetic_form');";
			$Page->AddLink($Page->Message('main', 'showAlphabeticForm'), $addUrl, 'img/ico/links/abc.gif', 'Show alphabetic navigation for '.$this->dataItem->label);
		}
		
		// Link to show search
		if ($this->buttonSearch)
		{
			$addUrl = "javascript:menuSlide('list_form_search');";
			$Page->AddLink($Page->Message('main', 'showListSearchForm'), $addUrl, 'img/ico/links/search.gif', 'Show search form for '.$this->dataItem->label);
		}
                 
        //Export link
		if ($this->buttonExport)
		{
			$linkHref = "export.php?type={$this->dataItem->name}&back={$Page->setBack}";
			if (strlen($this->cond)) $linkHref.="&cond={$this->cond}";
			$linkHref .= $this->GetRequiredUrlVars();
			$Page->AddLink('Export '.$this->dataItem->label, $linkHref, 'img/ico/links/import_export.gif', 'Export data '.$this->dataItem->label);
		}

		// Order Links
		if ($this->buttonOrder && $this->dataItem->order=='oder')
		{
			$orderUrl = "order.php?type={$this->dataItem->name}&back={$Page->setBack}&cond={$this->cond}";
			$orderUrl .= $this->GetRequiredUrlVars();
			$Page->AddLink($Page->Message('main','order'), $orderUrl, 'img/ico/links/order.gif', 'Change records order.');
		}

	}

	function SetTemplate($tpl=null)
	{
		$this->tplName = (strlen($tpl)) ? $tpl : 'cms/list';
	}

	function SetCond($cond)
	{
		$this->cond = (strlen($cond)) ? $cond : null;
		if ($this->cond != null)
		{
			$array1 = explode(' AND ', $this->cond);
			if (Utils::IsArray($array1))
			{
				//many fields
				$array = array();
				foreach ($array1 as $pair)
				{
					$array[] = 'HIDDEN_'.$pair;
				}
				$this->hiddenVariables = implode('&', $array);

			}
			else
			{
				//one field
    			$array = explode('&', $this->cond);
    			foreach ($array as $key=>$item)
    			{
    				$array[$key] = 'HIDDEN_'.$item;
    			}
    			$this->hiddenVariables = implode('&', $array);
    		}
		}
		else
		{
			$this->hiddenVariables = null;
		}
	}

	function AddCondLetter()
	{
		//Alphabetic cond
		$add = '';
		if (strlen($this->cond)) $add = ' AND ';
		$this->cond .= $add.$this->alphabeticField.' LIKE '.Database::Escape($_GET['letter'].'%');
	}
	
    function AddCondSearch($keyword, $match, $searchfield)
	{
	    GLOBAL $Db;
		$add = '';
		if (strlen($this->cond)) $add = ' AND ';
		$keyword = trim($keyword);
		
		if ($match=='match') $qqVal = '='.Database::Escape($keyword);
		else $qqVal = 'LIKE '.Database::Escape('%'.$keyword.'%');
		
		$tp = $this->IsLinkedTableInUse() ? 't1.' : '';
		
		if ($searchfield == '_all_')
		{
		    $tFields = $Db->GetTableFields($this->dataItem->table);
            $condSearch = '(';
			foreach ($tFields as $field)
			{
			    $condSearch .= "{$tp}`{$field}` $qqVal OR ";
			}
    		$condSearch = substr($condSearch, 0, -3).')';
    		$this->cond .= $add.' '.$condSearch;
		}
		else
		{
		    $this->cond .= $add.$tp.$searchfield.' '.$qqVal;
		}
	}
	
	function SetColumns($columns=null)
	{
		if (!strlen($columns))
		{
			$columns = ($this->dataItem->listFields != null) ? $this->dataItem->listFields : 'name';
		}
		
		$columns = explode(',', $columns);
		$this->columns = array();
		foreach ($columns as $column)
		{
		    $this->columns[] = new CMSListColumn($column);
		}
	}
	
    function SetSearchFields($searchFields=null)
	{
	    $this->searchFields = array();
	    
	    if (strlen($searchFields))
	    {
	        $fields = explode(',', $searchFields);
	        if (Utils::IsArray($fields)) $this->searchFields = $fields;
	    }
	    else
	    {
	        foreach ($this->columns as $column) $this->searchFields[] = $column->name;
	    }
	}
	

	function Render($blockName='cms_list', $customRows=null)
	{
		GLOBAL $Db,$Parser,$Paging,$Page;
        
		$listAction = @$_POST['list_'.$this->dataItem->name.'_action'];
		if (!$this->customDeleteSelected && $listAction=='delete_selected')
		{
			$Paging->SwitchOff();
			$this->DeleteSelected();
		}

		// alphabetic
		if (strlen(@$_GET['alpha_field']))
		{
			$this->alphabeticField = @$_GET['alpha_field'];
		}
		if ($this->alphabeticNavigation && strlen($this->alphabeticField) && strlen(@$_GET['letter']))
		{
            $this->AddCondLetter(@$_GET['letter']);
		}
		
		// search form
		if (strlen(@$_GET['qq']) && strlen(@$_GET['searchfield']))
		{
			$this->searchMatch = @$_GET['match'];
			$this->AddCondSearch(@$_GET['qq'], @$_GET['match'], @$_GET['searchfield']);
		}
		
		$blockNameRow = $blockName.'_row';
		$blockNameEmpty = $blockName.'_empty';
		$blockNameAlphabetic = $blockName.'_alphabetic';
		$blockNameSearchForm = $blockName.'_searchform';

		$this->back = $Page->setBack;

		$customColumns = array(); //contains custom rendered data
		$columnsCaptions = $this->RenderColumnsCaptions($customColumns);

		if ($customRows!==null) $rows = $customRows;
		else
		{
		    @list($orderField, $orderType) = explode(' ', $this->order);
		    
		    $obj = isset($this->dataItem->fields[$orderField]) ? $this->dataItem->fields[$orderField] : null;
		    
		    if ($obj!=null && $obj->type==LiskType::TYPE_LIST && $obj->objectType=='data')
		    {
		        
		        $d2 = Data::Create($obj->objectName, false);
		        $t1 = $this->dataItem->table;
		        $t2 = $d2->table;
		        $crossField = $obj->crossField;
		        
    		    $skipSqlCommands = array('CONCAT(');
    		    $doSkip = false;
    	        foreach($skipSqlCommands as $sqlCom)
    	        {
    	            if (strstr($crossField, $sqlCom) && !strstr($crossField, 't2.')) $doSkip = true;
    	        }
		        
		        if ($doSkip)
		        {
		            //crossField contains sql, we should do default sorting
		            $rows = $Db->Select($this->cond, $this->order, null, $this->dataItem->table);
		        }
		        else
		        {
    		        //init paging
    		        $Paging->SwitchOn('cp');
    		        $countCond = str_replace('t1.', '', $this->cond);
    		        $Paging->itemsTotal = $Db->Get($countCond, 'COUNT(*)', $t1);
    		       
    		        $Paging->Calculate();
    		        $offset = $Paging->offset;
    		        $limit = $Paging->GetItemsPerPage();
    		        
                    if (!strstr($crossField, 't2.')) $crossField = 't2.'.$crossField;
    		        
    		        //create select
    		        $cond = strlen($this->cond) ? 'WHERE '.$this->cond : '';
    		        $sql = "SELECT t1.*, $crossField AS _list_value_
    		             FROM $t1 AS t1
    		        	 LEFT JOIN $t2 AS t2 ON t1.$orderField=t2.id
    		        	 $cond
    		        	 GROUP BY t1.id
    		        	 ORDER BY _list_value_ $orderType
    		        	 LIMIT $offset, $limit
    		        ";
    		        
                    $rows = $Db->Query($sql);
		        }
		    }
		    else $rows = $Db->Select($this->cond, $this->order, null, $this->dataItem->table);
		}

        
	    // execute post processing, if any
		if ($this->handlerPostProcess && Utils::IsArray($rows))
		{
		    foreach ($rows as $k=>$row)
		    {
		        $rows[$k] = call_user_func($this->handlerPostProcess, $row);
		    }
		}
		
		//evaluate custom function for additional columns
		if (Utils::IsArray($customColumns) && Utils::IsArray($rows))
		{
			foreach($rows as $k=>$row)
			{
				foreach($customColumns as $func)
				{
					$rows[$k][$func] = $func($row);
				}
			}
		}

		$this->dataItem->values = $rows;

		$pagingHtml = '';

		if ($Paging->IsOn())
		{
			$pagingHtml = $Paging->Render();
			$Paging->SwitchOff();
		}

        $listParams = '';
		if ($this->buttonView)
		{
			if ($this->buttonEdit==false)
			{
				$listParams .= '&e=1';
			}
			if ($this->buttonDelete==false)
			{
				$listParams .= '&d=1';
			}
			$this->AddButton($Page->Message('main', 'view'), 'view.php?type=[type]&id=[id]&back=[back]'.$listParams, $Page->Message('cpmodules','view_hint'), '<img src="img/cms/list/view.gif" width="8" height="14" border="0" align="absmiddle">');
		}
		
		if ($this->buttonEdit)
		{
			$this->AddButton($Page->Message('main', 'edit'), 'edit.php?type=[type]&id=[id]&back=[back]', $Page->Message('cpmodules','edit_hint'), '<img src="img/cms/list/edit.gif" width="8" height="14" border="0" align="absmiddle">');
		}

		if ($this->buttonDelete)
		{
		    $addParams = $this->GetRequiredUrlVars();
		    $this->AddButton($Page->Message('main', 'delete'), "#delete\" class=\"delete\" rel=\"del.php?type=[type]&id=[id]&back=[back]{$addParams}\" onclick=\"return false", $Page->Message('cpmodules','delete_hint'), '<img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle">');
		}
		
		if ($this->buttonCheckbox) $this->AddButton('<input type="checkbox" name="ids[]" value="[id]" />', null);

		$Parser->LoadTemplate($this->tplName);
		$Tpl = $Parser->tpl;

		//AlphabeticNavigation
		if ($this->alphabeticNavigation && strlen($this->alphabeticField))
		{
			$letters = $this->GetAvailableLetters();
			$Tpl->ParseVariable($this->RenderAlphabeticMarker($letters), $blockNameAlphabetic);
		}
		
		//Search Form
		if ($this->buttonSearch)
		{
			$Tpl->ParseVariable(array(
    			'search_link' => Navigation::AddGetVariable(array('qq'=>'')),
    			'all_fields' => $this->RenderFieldsFormOpt(@$_GET['searchfield']),
    			'options_match' => $this->RenderMatchFormOpt(),
    			'keyword'=> @$_GET['qq'],
    			'list_name_search'=> $this->dataItem->name,
    			'display'	=> strlen(@$_GET['qq']) ? 'inline' : 'none'), $blockNameSearchForm
		    );
		}
				
		$i = 1;
		if (Utils::IsArray($this->dataItem->values))
		{
            foreach ($this->dataItem->values as $row)
            {
            	$decoration = ($i%2 == 1)? $this->listDecoration1 : $this->listDecoration2;
            	$rowView = array(
            	    'id' => $row['id'],
            	    'back' => $this->back,
            	    'row_id'	=> 'idRow_'.$row['id'],
            		'fields'	=> $this->RenderFieldsMarker($row, $decoration),
            		'buttons'	=> $this->RenderButtonsMarker($row, $decoration),
            		'decoration'=> $decoration
            	);
            	if ($this->handlerParseRow) $rowView = call_user_func($this->handlerParseRow, $row, $rowView);
            	$Tpl->ParseVariable($rowView, $blockNameRow);
            	$i++;
            }
		}
		else
		{
			$Tpl->TouchBlock($blockNameEmpty);
		}

		if ($this->buttonDeleteAll)
		{
			$Tpl->SetVariable(array(
				'list_name' => $this->dataItem->name
			));
		}

		$Tpl->SetCurrentBlock($blockName);
		$Tpl->SetVariable(array(
			'captions'		=> $this->RenderCaptionsMarker($columnsCaptions),
			'paging'		=> $pagingHtml,
		    'dataitem_name' => $this->dataItem->name,
		    'paging_pcp' => isset($_GET['pcp']) ? intval($_GET['pcp']) : 0,
		));
		$Tpl->ParseCurrentBlock();

		return $Tpl->Get();
	}
	
	private function RenderColumnsCaptions(&$customColumns)
	{
	    $columnsCaptions = array();
	    
	    foreach ($this->columns as $column)
		{
		    $field = $column->name;
			$sortStuff = '';

			if ($field{0} == '[' && substr($field, -1, 1) == ']')
			{
				$columnsCaptions[$field] = array(
					'element'	=> $this->dataItem->fields[$field]->label,
					'sort_stuff' => $sortStuff
				);
				$customColumns[] = substr($field, 1, -1);
				continue;
			}
			
			if ($column->isSortable)
			{
    			if (isset($_GET['order']) && $_GET['order'] != $field)
    			{
    				$sortStuff.='id="hand" ';
    				$sortUrl = Navigation::AddGetVariable(array(
    					'order'			=> $field,
    					'order_type'	=> 1
    				));
    				$sortStuff.="liskSortUrl=\"{$sortUrl}\"
    					liskSortField=\"sort_$field\"
    					liskSortImage1=\"img/cms/list/sort_1.gif'\"
    					liskSortImage2=\"img/cms/list/sort_0.gif'\">";
    				
    				$sortStuff.="<img src=\"img/cms/list/sort_0.gif\" width=8 height=8 name=\"sort_$field\"";
    			}
                else
    			{
    			    $orderOver = (isset($_GET['order_type']) && $_GET['order_type']==1) ? 2 : 1;
                    $orderType = (isset($_GET['order_type'])) ? $_GET['order_type'] : 0 ;
    				
    				$sortStuff .= 'id="hand" ';
    				$sortUrl = Navigation::AddGetVariable(array(
    					'order'			=> $field,
    					'order_type'	=> $orderOver
    				));
    				$sortStuff .= "liskSortUrl=\"{$sortUrl}\"
    					liskSortField=\"sort_$field\"
    					liskSortImage1=\"img/cms/list/sort_$orderOver.gif\"
    					liskSortImage2=\"img/cms/list/sort_$orderType.gif\">";
    				
    				$sortStuff .= "<img src=\"img/cms/list/sort_$orderType.gif\" width=8 height=8 name=\"sort_$field\"";
    
    				$columnsCaptions[$field] = array(
    					'element'	=> $this->dataItem->fields[$field]->label,
    					'sort_stuff'=> $sortStuff
    				);
    			}
			}
			
			$columnsCaptions[$field]=array(
				'element' => $this->dataItem->fields[$field]->label,
				'sort_stuff'=> $sortStuff
			);
		}
		
		return $columnsCaptions;
	}
	
    public function IsLinkedTableInUse()
	{
	    $arr = explode(' ', $this->order);
	    $orderField = isset($arr[0]) ? $arr[0] : null;
	    
	    $obj = $this->dataItem->fields[$orderField];
		    
	    if ($obj->type==LiskType::TYPE_LIST && $obj->objectType=='data')
	    {
	        $crossField = $obj->crossField;
	        
		    $skipSqlCommands = array('CONCAT(');
		    $doSkip = false;
	        foreach($skipSqlCommands as $sqlCom)
	        {
	            if (strstr($crossField, $sqlCom) && !strstr($crossField, 't2.')) $doSkip = true;
	        }
	        
	        return !$doSkip;
	    }
	    
	    return false;
	}

	public function RenderAlphabeticMarker($letters)
	{
		$rez = '';
		
		if (Utils::IsArray($letters))
		{
			foreach ($letters as $letter)
			{
				$link = Navigation::AddGetVariable(array(
					'letter'	=> $letter['letter']
				));
				if ($letter['letter']==@$_GET['letter']) $style = 'class="alpha_nav_sel"';
				else $style = '';
				$rez .= '<a href="'.$link.'" '.$style.'>'.$letter['letter'].'</a> | ';
			}
		}
		return array(
			'alpha_letters' => $rez,
			'all' => Navigation::AddGetVariable(array('letter'=>'')),
			'alpha_link' => Navigation::AddGetVariable(array('letter'=>'', 'alpha_field'=>'')),
			'all_fields' => $this->RenderFieldsFormOpt($this->alphabeticField),
			'display'	=> (isset($_GET['letter']) || isset($_POST['alpha_field'])) ? 'inline' : 'none');
	}
	
	public function RenderFieldsFormOpt($selected=null)
	{
	    $fields = '<option value="_all_">- all -</option>';
		$types = array(LiskType::TYPE_INPUT, LiskType::TYPE_TEXT, LiskType::TYPE_HTML);
		foreach ($this->searchFields as $col)
		{
		    $field = @$this->dataItem->fields[$col];
		    if ($field instanceof LiskType && in_array($field->type, $types))
		    {
		        $caption = $this->dataItem->fields[$col]->label;
                $fields .= '<option value="'.$col.'" '.(($col==$selected)?'selected':'').'>'.$caption.'</option>';
		    }
		}
		return $fields;
	}
	
	public function RenderMatchFormOpt()
	{
	    $vals = array(
	        'like' => 'Like',
	        'match' => 'Match',
	    );
	    $opts = '';
	    foreach ($vals as $key=>$val)
	    {
	        $opts .= '<option value="'.$key.'" '.(($key==$this->searchMatch)?'selected':'').'>'.$val.'</option>';
	    }
	    return $opts;
	}
	
	public function RenderFieldsMarker($row)
	{
        $rez = '';
        
        if ($this->inlineOrder)
        {
            $rez .= '<td width="20"><div class="order-handle-box"><img src="img/ico/links/ohb.gif" alt="" /></div></td>';
        }
        
		// it's a new version - it fixes the bug with the columns order
		foreach ($this->columns as $column)
		{
		    $key = $column->name;
		    
			//this is support for custom field render by func name ie [renderMyField]
			$key2 = str_replace(array('[',']'),'',$key);
			if (isset($row[$key2]))
			{
				$key = $key2;
			}

			if (isset($row[$key]))
			{
				// Type Render
				$value = $row[$key];
				if (isset($this->dataItem->fields[$key]))
				{
					$obj = $this->dataItem->fields[$key];
					$obj->value = $value;
					$param1 = null;
					if ($obj instanceof  T_image) $param1 = 'system';
					$value = $obj->Render($param1);
				}

				$fieldValue = $this->FormatFieldLinks($key, $value, $row);

				$rez .= '<td height="22" id="pad">'.$fieldValue.'</td>';
			}
		}

		return $rez;
	}

	private function RenderRowButton($row, $params)
	{
		$formatArr = array_merge($row, array(
			'back'	=> $this->back,
			'type'	=> $this->dataItem->name
		));

		$icon = '';
		if (strlen($params['icon']))
		{
			$icon = $params['icon'].' ';
		}
		if ($params['link']!=null)
		{
			$link = Format::String($params['link'], $formatArr);
			//Add load to url if required
			if (strtolower(substr($link,0,10))!='javascript') $link .= $this->GetRequiredUrlVars();

			if ($params['hint']!=null)
			{
				$hint = "liskHint=\"{$params['hint']}\"";
			}
			else
			{
				$hint = '';
			}

			$button	= "<a href=\"$link\" $hint>$icon{$params['name']}</a>";
		}
		else
		{
			$button	= $icon.Format::String($params['name'],$formatArr);
		}

		return $button;
	}

	public function RenderButtonsMarker($row)
	{
        $rez = '';
		if (Utils::IsArray($this->addButtons))
		{
			foreach ($this->addButtons as $params)
			{
				$remove = false;
				if (isset($this->removeButtons[$params['name']]))
				{
					$cond = Format::String($this->removeButtons[$params['name']], $row);
					$evalStr = "\$remove = ($cond);";
					eval($evalStr);
				}
				if ($remove)
				 {
					// create empty cell
					$rez .= '<td width="1%" id="pad">&nbsp;</td>';
				}
				else
				{
					$button = $this->RenderRowButton($row, $params);
					$rez.="<td align=\"right\" width=\"1%\" nowrap=\"nowrap\" id=\"pad\">$button</td>";
				}
			}
		}
		else
		{
			// create empty cell
			$rez .= '<td width="1%" id="pad">&nbsp;</td>';
		}

		return $rez;
	}

	public function RenderCaptionsMarker($columns)
	{
	    $rez = '';
	    if ($this->inlineOrder)
	    {
	        $rez .= '<td class="ListTDTop" width="20"><img src="img/ico/links/order.gif" alt="Order" /></td>';
	    }
		foreach ($columns as $row)
		{
			$rez .= "<td class=\"ListTDTop\" nowrap=\"nowrap\" {$row['sort_stuff']}> {$row['element']}</td>";
		}
		return $rez;
	}
}

?>