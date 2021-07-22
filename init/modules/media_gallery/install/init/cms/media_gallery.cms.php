<?php

function mgImageThumbnailSmall($row, $di)
{
	$image = $di->fields['file_image'];
	$image->AddFormParam('hspace', '2');
	$image->AddFormParam('vspace', '4');
	$image->AddFormParam('style', 'border-color:#000000; border-width:1px; border-style:solid;');
	$image->value = $row['file_image'];
	$image->InitStateFromValue();
	return  $image->Render('system');
}

function mgUpdateItemsCount($tableNodes, $tablePoints)
{
	GLOBAL $Db;
	$sql = 'SELECT n.id, COUNT(p.id) AS qty'
		." FROM $tableNodes AS n "
		." LEFT JOIN $tablePoints AS p ON p.parents LIKE CONCAT('%<',n.id,'>%')"
		.' GROUP BY n.id';
	$values = $Db->Query($sql);
	if (Utils::IsArray($values))
	{
		foreach ($values as $value)
		{
			$Db->Update("id = {$value['id']}", array ('items_count' => $value['qty']), $tableNodes);
		}
	}
}

class CMSMediaGallery
{
	public $treeName;		// System Tree name (in CFG file)
	public $params,		// Array of tree description (in CFG file)
		$label,			// Tree Label
		$maxLevel;		// Max Level. 1-list, 2-folder, 3-folder-subfolder, etc.

	public $node,			// node data item
		$point;			// point data item

	public $cl;			// current level (node ID)
	public $clMode;		// 0 - nothing 1 - nodes 2 - points
	public $nestingLevel;	// current Nesting Level (see max level)

	public $nodeViewField,	// name of the node's display field
		$pointViewField;// name of the point's display field

	public $buttonNodeAdd      = true;
	public $buttonNodeOrder    = true;
	public $buttonNodeView 	= false;
	public $buttonNodeEdit 	= true;
	public $buttonNodeDelete	= true;
	public $buttonNodeCheckbox	= true;
	public $buttonNodeDeleteAll= true;

	public $buttonPointAdd     = true;
	public $buttonPointOrder   = true;
	public $buttonPointView 	= false;
	public $buttonPointEdit 	= true;
	public $buttonPointDelete	= true;
	public $buttonPointCheckbox= true;
	public $buttonPointDeleteAll= true;

	public $_pointAddButtons	= array();
	public $_pointRemoveButtons = array();
	public $_nodeAddButtons	= array();

	public $back;

	public $FileSystem;
	public $dir;
	public $_imgExt = array();

	public $nodeId;

	private $filenamePattern = '[^_0-9a-zA-Z\.]+';

	public $categoryType;

	function __construct(MediaGallery $ModuleMediaGallery)
	{

		$this->_imgExt = $ModuleMediaGallery->GetAllValidExtentions();

		$this->FileSystem = new FileSystem();

		$this->__InitializeTree($ModuleMediaGallery->confDITreeName);

		$this->AddNodeButton('Upload from archive', "?cl=[id]&action=upload&id=[id]&back=[back]", '', '<img src="img/modules/media_gallery/ico_upload_in_archive.gif" border="0" hspace="2" align="absmiddle">');

		$this->AddPointButton('Download', "?cl={$this->cl}&action=download&id=[id]", 'Download', '<img src="img/modules/media_gallery/i_download.gif" width="16" height="12" border="0" align="absmiddle">');
		$this->AddPointButton('Preview', 'javascript: popupWindow(\'module_media_gallery_preview.php?iid='.$ModuleMediaGallery->iid.'&cl='.$this->cl.'&action=preview&id=[id]&back=[back]\',450,420);"', '', '<img src="img/cms/list/view.gif" width="8" height="14" border="0" align="absmiddle">');

		if ($this->categoryType==MEDIA_GALLERY_CATEGORY_TYPE_MEDIA)
		{
			$this->RemovePointButton('Download', "'[file_media]'==''");
			$this->RemovePointButton('Preview', "'[file_media]'==''");
		}
		else
		{
			$this->RemovePointButton('Download', "'[file_image]'==''");
			$this->RemovePointButton('Preview', "'[file_image]'==''");
		}
	}

	function __InitializeTree($treeName)
	{
		GLOBAL $App,$Db;
		if (!strlen($treeName)) $this->treeName = $_SESSION['cms_tree_name'];
		else
		{
			$_SESSION['cms_tree_name'] = $treeName;
			$this->treeName = $treeName;
		}

		$this->params = $App->ReadTree($treeName);

		if (!Utils::IsArray($this->params)) $App->RaiseError("Tree {$this->treeName} is undefined");

		$this->node		= new MediaGalleryCategoryDI($this->params['node'],true);
		$this->point	= new MediaGalleryItemDI($this->params['point'], true);
		$this->label	= $this->params['name'];
		$this->maxLevel	= $this->params['max_level'];
		$this->cl		= (!isset($_GET['cl']) || $_GET['cl'] < 1)?1:$_GET['cl'];

		$nodeListFields			= explode(',',$this->node->listFields);
		$this->nodeViewField	= $nodeListFields[0];
		$pointListFields		= explode(',',$this->point->listFields);
		$this->pointViewField	= $pointListFields[0];

		// define current level mode
		$isNode = $Db->Get("parent_id = $this->cl", 'id', $this->node->table);
		$isPoint = $Db->Get("parent_id = $this->cl", 'id', $this->point->table);
		$isNode = (!empty($isNode));
		$isPoint = (!empty($isPoint));

		if ($isNode) {
			$this->clMode = 1;
		} else if ($isPoint) {
			$this->clMode = 2;
		} else {
			$this->clMode = 0;
		}

		// arrange current nesting level
		$currentNode = $Db->Get("id = ".$this->cl, '', $this->node->table);
		$nodeParents = $currentNode['parents'];
		$this->nestingLevel = substr_count($nodeParents, '&raquo;');

		$this->categoryType = $currentNode['category_type'];
        
		if ($this->categoryType==MEDIA_GALLERY_CATEGORY_TYPE_IMAGE) {
			$this->point->Reset('image_gallery');
		} else {
			$this->point->Reset('media_gallery');
		}

	}

	function AddNodeButton($name,$link,$hint='',$icon='') {
		$this->_nodeAddButtons[]=array(
			'name'	=> $name,
			'link'	=> $link,
			'hint'	=> $hint,
			'icon'	=> $icon,
		);
	}

	function AddPointButton($name,$link,$hint='',$icon='') {
		$this->_pointAddButtons[]=array(
			'name'	=> $name,
			'link'	=> $link,
			'hint'	=> $hint,
			'icon'	=> $icon,
		);
	}

	function RemovePointButton($name, $cond) {
		$this->_pointRemoveButtons[$name] = $cond;
	}

	function DeleteSelected() {

		switch ($this->clMode) {
			case 1:
				$DataItem = &$this->node;
				break;
			case 2:
				$DataItem = &$this->point;
				break;
		}

		foreach ($_POST as $key=>$value) {
			if (substr($key,0,2)=='r_') {
				$id=substr($key,2);
				$cond="id=$id";
				$DataItem->Delete($cond);
			}
		}
		Navigation::Jump(Navigation::Referer());
	}

	function MakeLinkButtons(CPPage $Page)
	{
		if ($this->categoryType==MEDIA_GALLERY_CATEGORY_TYPE_MEDIA)
		{
			$captionAdd = 'Add Media Item';
			$captionUpload = 'Upload Media Files From Archive';
			$captionOrder = 'Order Media';

		}
		elseif ($this->categoryType==MEDIA_GALLERY_CATEGORY_TYPE_IMAGE)
		{
			$captionAdd = 'Add Image';
			$captionUpload = 'Upload Images From Archive';
			$captionOrder = 'Order Images';
		}

		if ($this->buttonNodeAdd && $this->clMode!=2 && $this->nestingLevel<$this->maxLevel)
		{
			$addUrl = "?action=add_node&type={$this->treeName}&back={$Page->setBack}&HIDDEN_parent_id={$this->cl}";

			$Page->AddLink('Add Category', $addUrl, 'img/ico/links/add.gif', 'Add new category.');
		}

		if ($this->buttonPointAdd && $this->clMode!=1)
		{
			$addUrl="?cl={$this->cl}&action=add_point&type={$this->treeName}&back={$Page->setBack}&HIDDEN_parent_id={$this->cl}";
			$Page->AddLink($captionAdd, $addUrl,'img/ico/links/add.gif','Add new record to the list.');
		}

		// Order Links
		if ($this->buttonNodeOrder && $this->clMode==1 && $this->node->order=='oder')
		{
			$orderUrl="?action=order_node&type={$this->treeName}&back={$Page->setBack}&cond=parent_id={$this->cl}";
			$Page->AddLink(' Order '.$this->node->label,$orderUrl,'img/ico/links/order.gif','Change categories order.');
		}

		if ($this->buttonPointOrder && $this->clMode==2 && $this->point->order=='oder')
		{
			$orderUrl="?action=order_point&type={$this->treeName}&back={$Page->setBack}&cond=parent_id={$this->cl}";
			$Page->AddLink($captionOrder, $orderUrl,'img/ico/links/order.gif','Change records order.');
		}

		if ($this->clMode==2)
		{
			$addUrl="?cl={$this->cl}&action=upload&type={$this->treeName}&id={$this->cl}";
			$Page->AddLink($captionUpload, $addUrl, 'img/modules/media_gallery/ico_upload_in_archive.gif', 'Upload from archive.');
		}
	}

	function AdditionalNavigation(&$Page) {
		GLOBAL $Parser;

		$rez = $Parser->MakeView(array(
			'jump_field'	=> $this->MakeJumpField(),
			'navigation'	=> $this->RenderNavigation()
		),'cms/media_gallery','additional_navigation');
		$Page->customLine=$rez;
	}

	function RenderNavigation() {
		GLOBAL $Parser;
		$Parser->setAddVariables(array(
			'back'	=> $this->back
		));
		return $Parser->MakeNavigation(Utils::TreeToNavigation($this->cl,$this->treeName),'cms/media_gallery','nav');
	}

	function ListRender($back,$list,$blockName='cms_list')
	{
		GLOBAL $Db,$Parser,$Paging;

		$list->back = $back;

		$blockNameRow = $blockName.'_row';
		$blockNameEmpty = $blockName.'_empty';
		$blockNameDeleteAll = $blockName.'_delete_all';

		$customColumns = array(); //contains custom rendered data

		// create select fields list
		$sql_select_fields='id';
		foreach ($list->columns as $column)
		{
			if (substr($column->name, 0, 1)=='[' && substr($column->name, -1, 1)==']')
			{
				$customColumns[] = substr($column->name, 1, strlen($column->name)-2);
			}
			else
			{
				$sql_select_fields .= ', '.$column->name;
			}
		}

		// get columns caption
		foreach ($list->columns as $column)
		{
			$sortStuff = '';
			$field = $column->name;
			
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
    
    				$columnsCaptions[$field]=array(
    					'element'	=> $list->dataItem->fields[$field]->label,
    					'sort_stuff'=> $sortStuff
    				);
    			}
			}
		}


		// make DB select

//		$temp = explode(',',$sql_select_fields);
//		foreach ($temp as $k=>$v) {
//			$temp[$k] = 'a.'.$v;
//		}
//		$sql_select_fields = implode(",",$temp);
//		$query = "SELECT $sql_select_fields,count(b.id) AS items_count FROM ".$list->dataItem->table." a LEFT JOIN ".$this->point->table." b ON a.id=b.parent_id WHERE ".$list->cond." GROUP BY a.id ORDER BY a.".$list->order;
//
//		$rows = $Db->Query($query);

		$rows = $Db->Select($list->cond, $list->order, '', $list->dataItem->table);

		if ($this->clMode==1) {
			for ($i=0; $i<count($rows); $i++) {
				$rows[$i]['items_count'] = "Contains ".$rows[$i]['items_count']." items";
			}
		}

		//evaluate custom function for additional columns
		if(Utils::IsArray($customColumns) && Utils::IsArray($rows)) {
			foreach($rows as $k=>$row) {
				foreach($customColumns as $func) {
					$rows[$k][$func] = $func($row, $list->dataItem);
				}
			}
		}

		$list->dataItem->values = $rows;

		$Paging->Calculate();

		if ($Paging->IsOn())
		{
			$pagingMarker = $Paging->Render();
			$Paging->SwitchOff();
		}

		if ($list->buttonView) {
			$list->AddButton('<img src="img/cms/list/view.gif" width="8" height="14" border="0" align="absmiddle"> View','?action=view&type='.$this->treeName.'&id=[id]&back=[back]&cl='.$this->cl, 'View current record.');
		}
		if ($list->buttonEdit) {
			$action = 'edit_node';
			if ($this->clMode==2) {
				$action = 'edit_point';
			}
			$list->AddButton('<img src="img/cms/list/edit.gif" width="8" height="14" border="0" align="absmiddle"> Edit','?action='.$action.'&type='.$this->treeName.'&id=[id]&back=[back]&cl='.$this->cl,'Edit current record.');
		}
		if ($list->buttonDelete) {
			$action = 'delete_node';
			if ($this->clMode==2) {
				$action = 'delete_point';
			}
			$list->AddButton('<img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle"> Delete',"?action={$action}&type={$this->treeName}&id=[id]&back=[back]&cl={$this->cl}", 'Delete current record.');

		}
		if ($list->buttonCheckbox) {
			$list->AddButton('<input name="r_[id]" type="checkbox" id="r_[id]" value="1">', null);
		}

		$Parser->loadTemplate($list->tplName);
		$Tpl = $Parser->tpl;

		$i=1;
		if (Utils::IsArray($list->dataItem->values)) {
			foreach ($list->dataItem->values as $row) {
				$decoration = ($i%2 == 1)?$list->listDecoration1:$list->listDecoration2;
				$Tpl->parseVariable(array(
					'fields'	=> $list->RenderFieldsMarker($row,$decoration),
					'buttons'	=> $list->RenderButtonsMarker($row,$decoration),
					'decoration'=> $decoration
				), $blockNameRow);
				$i++;

			}
		} else {
			$Tpl->touchBlock($blockNameEmpty);
		}

		if ($list->buttonDeleteAll) {
			$Tpl->touchBlock($blockNameDeleteAll);
		}

		$Tpl->setCurrentBlock($blockName);
		$Tpl->setVariable(array(
			'captions'		=> $list->RenderCaptionsMarker($columnsCaptions),
			'paging'		=> $pagingMarker,
		    'dataitem_name' => $list->dataItem->name,
		    'paging_pcp' => isset($_GET['pcp']) ? intval($_GET['pcp']) : 0,
		));
		$Tpl->parseCurrentBlock();

		return $Tpl->get();
	}

	function Render($back)
	{
		GLOBAL $Parser,$Paging;
		//echo '1';
		$this->back = $back;

		switch ($this->clMode)
		{
			case 0:
				return $Parser->GetHtml('cms/media_gallery','empty');
				break;
				
			case 1:

				$list = new CMSList($this->node);
				$list->Init();
				// copy buttons view status
				$list->buttonCheckbox	= $this->buttonNodeCheckbox;
				$list->buttonDeleteAll	= $this->buttonNodeDeleteAll;
				$list->buttonDelete 	= $this->buttonNodeDelete;
				$list->buttonEdit		= $this->buttonNodeEdit;
				$list->buttonView		= $this->buttonNodeView;
				$list->buttonAdd		= $this->buttonNodeAdd;
//				$list->SetCond("a.parent_id={$this->cl}");
				$list->SetCond("parent_id={$this->cl}");
				$list->SetFieldLink('name','?cl=[id]&back=[back]');

				// node add buttons
				if (Utils::IsArray($this->_nodeAddButtons)) {
					foreach ($this->_nodeAddButtons as $row) {
						$list->AddButton($row['name'],$row['link'],$row['hint'],$row['icon']);
					}
				}

				// initialize paging
				$Paging->SwitchOn('cp');
				return $this->ListRender($back,$list);
				break;
				
			case 2:
				if ($this->categoryType==MEDIA_GALLERY_CATEGORY_TYPE_IMAGE)
				{
					$this->point->ReSet('list_image_gallery');
					$this->point->listFields .= ',[mgImageThumbnailSmall]';
				}
				$list = new CMSList($this->point);
				$list->Init();
				$list->SetCond("parent_id={$this->cl}");
				//

				// copy buttons view status
				$list->buttonCheckbox	= $this->buttonPointCheckbox;
				$list->buttonDeleteAll	= $this->buttonPointDeleteAll;
				$list->buttonDelete 	= $this->buttonPointDelete;
				$list->buttonEdit		= $this->buttonPointEdit;
				$list->buttonView		= $this->buttonPointView;
				// point add buttons
				if (Utils::IsArray($this->_pointAddButtons))
				{
					foreach ($this->_pointAddButtons as $row)
					{
						$list->AddButton($row['name'],$row['link'],$row['hint'],$row['icon']);
					}
				}
				// point remove buttons
				if (Utils::IsArray($this->_pointRemoveButtons))
				{
					foreach ($this->_pointRemoveButtons as $name=>$cond)
					{
						$list->RemoveButton($name, $cond);
					}
				}

				// initialize paging
				$Paging->SwitchOn('cp');
//				return $list->Render($back);
				return $this->ListRender($back,$list);
				break;
		}
	}


	function __NodeSort($parent, $rows, $cl)
	{
		STATIC $rez;
		if (Utils::IsArray($rows))
		{
			foreach($rows as $row)
			{
				if ($row['parent_id']==$parent)
				{
					$rez[$row['id']] = str_repeat("&nbsp;",substr_count($row['parents'],">")*2).$row[$this->nodeViewField];
					$this->__NodeSort($row['id'], $rows, $cl, $this->nodeViewField);
				}
			}
		}
		return $rez;
	}

	function MakeJumpField($params='') {
		GLOBAL $Db,$App;

		$rows = $Db->Select(null, '', '', $this->node->table);
		$arr = $this->__NodeSort(0, $rows, $this->cl);

		$App->Load('list','type');
		$list	= new T_list(array(
			'object'	=> 'arr',
			'form'		=> "onChange=\"document.location='?cl='+this.value\" style=\"font-size: 10px;\" liskHint=\"Quick jump to the selected category.\""
		));
		$list->values=$arr;
		$list->value=$this->cl;
		return $list->RenderFormTplView();
	}

	function UploadArchive(array $nodeValue)
	{
		GLOBAL $App;

		$this->nodeId = $nodeValue['id'];
		$this->dir = $App->sysRoot.$App->filePath.'temp/'.time().'/';

		if (!is_uploaded_file($_FILES['userfile']['tmp_name'])) return 2;

		if (!$this->FileSystem->CreateDir($this->dir)) return 3;

		/*if (strtolower($this->GetExt($_FILES['userfile']['name'])) <> 'zip') {
			$this->DelDir($this->dir);
			return 4;
		}*/

		$file = $this->dir.$_FILES['userfile']['name'];
		$extractPath = $this->dir;
		if (!$this->FileSystem->CopyFile($_FILES['userfile']['tmp_name'], $file))
		{
			$this->DelDir($this->dir);
			return 5;
		}

		$res = $this->Extract($file, $extractPath);

		if (!$res)
		{
			$this->DelDir($this->dir);
			return 6;
		}

		foreach ($res as $v)
		{
			if (is_file($v['filename']))
			{
				$this->SaveFile($v['filename'], $nodeValue['category_type']);
			}
		}

		$this->DelDir($this->dir);
		return 1;
	}

	function DelDir($dir)
	{
		$this->FileSystem->DeleteDir($dir);
	}

	function SaveFile($file, $categoryType)
	{
		GLOBAL $Db;
		if (!in_array($this->GetExt(basename($file)), $this->_imgExt)) return false;

		$filename = basename($file);
		$filename = ereg_replace($this->filenamePattern, '_', $filename);
		$name = $filename;
        
		$data = array(
			'file_media'=> '',
			'file_image'=> '',
			'name'		=> $name,
			'parent_id'	=> $this->nodeId
		);

		$lastId = $this->point->Insert($data);
		
		$di = Data::Create($this->point->name);
		
		if ($categoryType==MEDIA_GALLERY_CATEGORY_TYPE_IMAGE)
		{
		    $di->Reset('image_gallery');
			$Image = $di->fields['file_image'];
			$added = $Image->InsertFromFile($file);
			
			if ($added) $filename = $Image->GetValueToSave();
			else $filename = '';
			
			if (strlen($filename)) $Db->Update("id=$lastId", array('file_image'=>$filename), $di->table);
		}
		else
		{
		    $di->Reset('media_gallery');
		    
			$File = $di->fields['file_media'];
			$filename = $this->FileSystem->MakeFilenameUnique($File->path, $filename);
			$this->FileSystem->CopyFile($file, $File->path.$filename);
			
			$Db->Update("id=$lastId", array('file_media'=>$filename), $di->table);
		}

		return true;
	}

	function Extract($file, $extractPath)
	{
		GLOBAL $App;
		$App->Load('zip', 'utils');
		Utils::FreezeMBEncoding();
		$Zip = new Archive_Zip($file);
		$val = $Zip->extract(array('add_path'=>$extractPath));
		Utils::UnfreezeMBEncoding();
		return $val;
	}

	function GetExt($fileName)
	{
		$tempArr = explode('.', basename($fileName));
		if (count($tempArr) < 2) return false;
		return $tempArr[count($tempArr)-1];
	}

	function RenderForm()
	{
		GLOBAL $Parser;
		return $Parser->MakeView(array('temp'=>' ','id'=>$_GET['id'], 'max_size'=>ini_get('upload_max_filesize')),'cms/media_gallery','upload');
	}
}
?>