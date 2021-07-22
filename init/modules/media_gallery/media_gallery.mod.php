<?php

$GLOBALS['MEDIA_GALLERY_MODULE_INFO'] = array(
	'name'			=> 'Media Gallery',
	'sys_name'		=> LiskModule::MODULE_MEDIA_GALLERY,
	'version'		=> '5.0',
	'description'	=> 'Media Gallery Module',
	'object_name'	=> 'MediaGallery',
	'multiinstance'	=> true,
	'ss_integrated'	=> true
);

define('MEDIA_GALLERY_CATEGORY_TYPE_MEDIA', 0);
define('MEDIA_GALLERY_CATEGORY_TYPE_IMAGE', 1);

$GLOBALS['LIST_MEDIA_GALLERY_CATEGORY'] = array(
	MEDIA_GALLERY_CATEGORY_TYPE_MEDIA => 'Media Gallery',
	MEDIA_GALLERY_CATEGORY_TYPE_IMAGE => 'Image Gallery',
);


/**
 * Media Gallery Module Main Class
 *
 */
class MediaGallery extends LiskModule
{

	/**
	 * Media gallery base url
	 * used in tree
	 *
	 * @var string
	 */
	public $confBaseUrl;

	public $confDICategoriesName;
	public $confDIItemsName;
	public $confDITreeName;

	public $tplPath = 'modules/media_gallery_';


	/**
	 * Extensions used to determine which player to use for render/preview
	 */
	public $confExtImage;
	public $confExtFlash;
	public $confExtMedia;

	//open media preview in popup window
	public $confPreviewInPopup;

	//paging
	public $confPagingItemsPerPage;
	public $confPagingPagesPerPage;

	//reder options
	public $confColumnsPerTable;

	public $DICategory;
	public $DIItem;
	public $isImageGallery = false;

	//used for popup preview
	public $confImagePreviewWidth  = 440;
	public $confImagePreviewHeight = 320;

	/**
	 * Constructor
	 *
	 * @return MediaGallery
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_MEDIA_GALLERY;
		if ($instanceId!=null)
		{
			$this->Init($instanceId);
			$this->DICategory = Data::Create($this->confDICategoriesName);
			$this->DIItem = Data::Create($this->confDIItemsName);
		}
	}

	/**
	 * Initialize MediaGallery
	 *
	 * @param integer $instanceId
	 */
	public function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->tplPath .= $instanceId.'/';

		$this->version = $GLOBALS['MEDIA_GALLERY_MODULE_INFO']['version'];

		$this->confBaseUrl          = $this->config['base_url'];
		$this->confDICategoriesName = $this->config['categories_di'];
		$this->confDIItemsName	    = $this->config['items_di'];
		$this->confDITreeName		= $this->config['tree_di'];
		$this->confExtImage         = $this->config['ext_image'];
		$this->confExtFlash         = $this->config['ext_flash'];
		$this->confExtMedia         = $this->config['ext_media'];
		$this->confPreviewInPopup     = $this->config['preview_in_popup'];
		$this->confPagingItemsPerPage = $this->config['items_per_page'];
		$this->confPagingPagesPerPage = $this->config['pages_per_page'];
		$this->confColumnsPerTable = $this->config['columns_per_table'];

		$classVars = array_keys(get_class_vars(get_class($this)));
		foreach ($classVars as $name)
		{
			if (substr($name, 0, 4) == 'conf' && $name != 'config')
			{
				$this->Debug($name, $this->$name);
			}
		}
	}

	/**
	 * Save MediaGallery settings
	 *
	 */
	public function SaveSettings()
	{
		GLOBAL $Db;

		$this->config['base_url'] = $this->confBaseUrl;
		$this->config['categories_di'] = $this->confDICategoriesName;
		$this->config['items_di'] = $this->confDIItemsName;
		$this->config['tree_di'] = $this->confDITreeName;
		$this->config['ext_image'] = $this->confExtImage;
		$this->config['ext_flash'] = $this->confExtFlash;
		$this->config['ext_media'] = $this->confExtMedia;
		$this->config['preview_in_popup'] = $this->confPreviewInPopup ? true : false;
		$this->config['items_per_page'] = $this->confPagingItemsPerPage;
		$this->config['pages_per_page'] = $this->confPagingPagesPerPage;
		$this->config['columns_per_table'] = $this->confColumnsPerTable;

		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	/**
	 * Install MediaGallery
	 *
	 * @param integer $instanceId
	 * @param array $params
	 */
	public function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/media_gallery/media_gallery.install.mod.php', 1);
		installMediaGalleryModule($instanceId, $params['path'], $params['page_name']);
	}

	/**
	 * Uninstall MediaGallery
	 *
	 */
	public function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/media_gallery/media_gallery.install.mod.php', 1);
		uninstallMediaGalleryModule($this->iid, $this->IsLastInstance());
		parent::Uninstall();
	}

	/**
	 * Get file type to know render method
	 *
	 * @param integer $file
	 * @return integer
	 */
	function GetPlayer($file)
	{
		//media
		foreach($this->confExtMedia as $ext)
		{
			if (strtolower(substr($file, -strlen($ext)-1)) == '.'.$ext)
			{
				return 1;
			}
		}
		//flash
		foreach($this->confExtFlash as $ext)
		{
			if (strtolower(substr($file,-strlen($ext)-1)) == '.'.$ext)
			{
				return 3;
			}
		}
		//image
		foreach($this->confExtImage as $ext)
		{
			if (strtolower(substr($file,-strlen($ext)-1))=='.'.$ext)
			{
				if ($this->isImageGallery)
				{
					return 5;
				}
				else
				{
					return 4; //image was moved from image gallery
				}
			}
		}

		//image gallery
		if (!strpos($file, '.'))
		{
			//consider it to be an image

			if ($this->isImageGallery) return 5;
			else return 4; //image was moved from image gallery
		}

		return false;
	}

	/**
	 * Get all allowed filenames extensions
	 *
	 * @return array
	 */
	function GetAllValidExtentions()
	{
		$arr = array();

		foreach ($this->confExtImage as $ext)
		{
			$arr[] = $ext;
		}

		foreach ($this->confExtFlash as $ext)
		{
			$arr[] = $ext;
		}

		foreach ($this->confExtMedia as $ext)
		{
			$arr[] = $ext;
		}

		return $arr;
	}

	/**
	 * Download item / send to browser with force download header
	 *
	 * @param integer $id
	 */
	function Download($id)
	{
		$ci = Data::Create($this->confDICategoriesName);
		$di = Data::Create($this->confDIItemsName);

		$di->Get('id='.$id);
		if (Utils::IsArray($di->value))
		{
			$ci->Get('id='.$di->value['parent_id']);

			if ($ci->value['category_type'] == MEDIA_GALLERY_CATEGORY_TYPE_IMAGE)
			{
				$field = 'file_image';
				$obj = $di->fields[$field];
				$obj->value = $di->value[$field];
				$obj->InitStateFromValue();
				$path = $obj->GetFileParam(null, 'path');
			}
			else
			{
				$field = 'file_media';
				$path = $di->fields[$field]->path.$di->value[$field];
			}
		}

		if (file_exists($path) && !is_dir($path))
		{
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private", false);
			header("Content-Type: application/octet-stream");
			header('Content-Disposition: attachment; filename="'.basename($path).'";');
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".filesize($path));
			readfile($path);
		}
		else
		{
		    header('Status: 404 Not Found', true, 404);
			header('HTTP/1.0 404 Not Found', true, 404);
		}
		exit();
	}

	public function Render()
	{
		GLOBAL $Parser,$Paging,$Page,$App,$Scms;
		$App->Load('tree', 'utils');
		//show category or item
		$Tree = new Tree($this->confDITreeName);

		$navigation = $Tree->GetNavigationRows();
		unset($navigation[0]);
		$Scms->AddNavigation($navigation);

		$catType = $Tree->Node->GetValue('id='.$Tree->cl, 'category_type');
		if ($catType == MEDIA_GALLERY_CATEGORY_TYPE_IMAGE)
		{
			$Tree->Point->Reset('image_gallery');
			$this->isImageGallery = true;
		}
		else
		{
			$Tree->Point->Reset('media_gallery');
		}

		//check if this is a download request
		if (Utils::IsArray($Page->parameters))
		{
			foreach ($Page->parameters as $k=>$param)
			{
				if ($param == '_download') $this->Download($Page->parameters[$k+1]);
			}
		}

		$categories = '';
		$items = '';
		$item = '';

		switch ($Tree->curMode)
		{
			case TREE_NODE_LIST:
			    $Paging->SwitchOn('system');
			    $Paging->SetItemsPerPage($this->confPagingItemsPerPage);
		        $Paging->pagesPerPage = $this->confPagingPagesPerPage;
		
				$categories = $Tree->RenderCategoriesList($this->tplPath.'categories_list', 'list');
				break;

			case TREE_POINT_LIST:
                $Paging->SwitchOn('system');
                $Paging->SetItemsPerPage($this->confPagingItemsPerPage);
		        $Paging->pagesPerPage = $this->confPagingPagesPerPage;
		
				if ($this->confPreviewInPopup)
				{
					$Parser->SetAddVariables(array('_preview_popup'=>1));
				}
				if ($catType==MEDIA_GALLERY_CATEGORY_TYPE_IMAGE)
				{
					$items = $Tree->RenderItemsTable($this->confColumnsPerTable, $this->tplPath.'items_list', 'tbl_images');
				}
				else
				{
					$Parser->SetListDecoration('down1', 'down2');
					$items = $Tree->RenderItemsList($this->tplPath.'items_list', 'list');
				}
				break;

			case TREE_POINT:
				$Tree->Point->Get('url='.Database::Escape($GLOBALS['url']));

				$this->DIItem->value = $Tree->Point->value;
				$item = $this->RenderItem($this->tplPath.'media_gallery');

				if ($this->confPreviewInPopup)
				{
					$Page->SetGlobalTemplate($this->tplPath.'global_media_gallery_preview');
					$Page->LoadTemplate($this->tplPath.'page');
					$Page->SetGlobalVariable(array(
						'popup_title' => $Tree->Point->value['name']
					));
					$Parser->ParseView(
						array(
							'item' => $item,
						),
						'view_popup'
					);
					$Page->Output();
				}
				break;
		}

		$Paging->SwitchOff();

		return $Parser->MakeView(
			array(
				'categories' => $categories,
				'items'      => $items,
				'item'       => $item,
				'navigation' => $Scms->RenderNavigation(),
			),
			$this->tplPath.'page',
			'view'
		);
	}

	/**
	 * Render One Item
	 *
	 * @param string $tplName
	 * @return string
	 */
	public function RenderItem($tplName)
	{
		if ($this->isImageGallery)
		{
			$field = 'file_image';

			$value = $this->DIItem->value[$field];
			$obj = $this->DIItem->fields[$field];
			$obj->value = $value;
			$obj->InitStateFromValue();
            
			$file = $obj->GetFilename();;
			$path = $obj->GetFileParam(null, 'path');
			$type = $this->GetPlayer($file);

			$this->DIItem->value['original_width']  = $obj->GetFileParam(null, 'width');
			$this->DIItem->value['original_height'] = $obj->GetFileParam(null, 'height');

			$this->DIItem->value['medium_height'] = $this->confImagePreviewHeight;
			$this->DIItem->value['medium_width']  = $this->confImagePreviewWidth;
			
			
		}
		else
		{
			$field = 'file_media';

			$file = $this->DIItem->value[$field];
			$path = $this->DIItem->fields[$field]->path.$file;
			$type = $this->GetPlayer($file);

			$imageSize = getimagesize($path);
			$this->DIItem->value['original_width']  = $imageSize[0];
			$this->DIItem->value['original_height'] = $imageSize[1];

			$this->DIItem->value['medium_height'] = $this->confImagePreviewHeight;
			$this->DIItem->value['medium_width']  = $this->confImagePreviewWidth;
		}

		switch($type)
		{
			case 1:
				return $this->RenderViewMedia($tplName);
				break;

			case 2:
				return $this->RenderViewAudio($tplName);
				break;

			case 3:
				return $this->RenderViewFlash($tplName);
				break;

			case 4:
				return $this->RenderViewImage($tplName);
				break;

			case 5:
				return $this->RenderViewImageSlideShow($tplName);
				break;
		}
	}

	/**
	 * Render Video/Audio
	 *
	 * @param string $tplName
	 * @param string $blockName
	 * @return string
	 */
	function RenderViewMedia($tplName, $blockName='view_media')
	{
		GLOBAL $Parser;
		return $Parser->MakeView($this->DIItem, $tplName, $blockName);
	}

	/**
	 * Render Flash
	 *
	 * @param string $tplName
	 * @param string $blockName
	 * @return string
	 */
	function RenderViewFlash($tplName, $blockName='view_flash')
	{
		GLOBAL $Parser;
		return $Parser->MakeView($this->DIItem, $tplName, $blockName);
	}

	/**
	 * Render Image
	 *
	 * @param string $tplName
	 * @param string $blockName
	 * @return string
	 */
	function RenderViewImage($tplName, $blockName='view_image')
	{
		GLOBAL $Parser;
		return $Parser->MakeView($this->DIItem, $tplName, $blockName);
	}

	/**
	 * Render Whole Image Category & Slide Show
	 *
	 * @param string $tplName
	 * @param string $blockName
	 * @return string
	 */
	function RenderViewImageSlideShow($tplName, $blockName='view_image_slideshow')
	{
		GLOBAL $Parser;
		$Point = $this->DIItem;
		$Point->Select('parent_id='.$this->DIItem->value['parent_id'].' AND file_image!=\'\'');

		$currentIdx = 0;
		if (Utils::IsArray($Point->values))
		{
			foreach ($Point->values as $k=>$value)
			{
				if ($value['id'] == $this->DIItem->value['id'])
				{
					$currentIdx = $k;
				}
			}
			$picsList = $Parser->MakeList($Point, $tplName, 'pics_list');
			$picsHeight = $Parser->MakeList($Point, $tplName, 'pics_height');
			$picsWidth = $Parser->MakeList($Point, $tplName, 'pics_width');
			$picsName = $Parser->MakeList($Point, $tplName, 'pics_name');
			$picsSize = $Parser->MakeList($Point, $tplName, 'pics_size');
			$picsIds = $Parser->MakeList($Point, $tplName, 'pics_ids');
		}
		
		$p = new Paging();
		$p->SwitchOn('system');
		$p->SetItemsPerPage(1);
		$p->pagesPerPage = $this->confPagingPagesPerPage;
		$p->itemsTotal = count($Point->values);
		if (!isset($_GET['pcp'])) $_GET['pcp'] = $currentIdx;
		$p->Calculate();

		$Parser->SetCaptionVariables(
			array(
				'pics_list'   => $picsList,
				'pics_ids' => $picsIds,
				'pics_height' => $picsHeight,
				'pics_width'  => $picsWidth,
				'pics_size'  => $picsSize,
				'pics_name'   => $picsName,
				'paging' => $p->Render(),
				'ind' => $_GET['pcp']+1
			)
		);
		
		$p->SwitchOff();

		if (isset($_GET['pcp']))
		{
			$this->DIItem->value = $Point->values[$_GET['pcp']];
		}
		return $Parser->MakeView($this->DIItem, $tplName, $blockName);
	}

	function RenderCPPreview($id)
	{
		$item = $this->DIItem->GetValue('id='.$id);
		$category = $this->DICategory->GetValue('id='.$item['parent_id']);

		if ($category['category_type']==MEDIA_GALLERY_CATEGORY_TYPE_IMAGE)
		{
			$this->DIItem->Reset('image_gallery');
			$this->isImageGallery = true;
		}
		else
		{
			$this->DIItem->Reset('media_gallery');
		}

		$this->DIItem->value = $item;

		return $this->RenderItem('modules/media_gallery/media_gallery');
	}
    
    public function UpdateBaseUrl($baseUrl)
	{
	    GLOBAL $Db;
	    
	    if (!isset($this->config['base_url'])) return;
	    if ($this->config['base_url'] == $baseUrl) return;
	    
	    $oldUrl = $this->config['base_url'];
        
	    //save module settings
	    $this->config['base_url'] = $baseUrl;
		$this->SaveConfig();
		
		$len = strlen($oldUrl) + 1;
		
		//update categories urls
		$di = Data::Create($this->confDICategoriesName, false);
		$table = $di->table;
		$sql = "UPDATE $table
			SET url = CONCAT('$baseUrl', SUBSTRING(url, $len))
		";
		$Db->Query($sql);
		
		//update items urls
		$di = Data::Create($this->confDIItemsName, false);
		$table = $di->table;
		$sql = "UPDATE $table
			SET url = CONCAT('$baseUrl', SUBSTRING(url, $len))
		";
		$Db->Query($sql);
	}
}

/**
 * DataItem for media category
 *
 */
class MediaGalleryCategoryDI extends Data
{

	public $instanceId;
	public $itemsTable;

	public $bufferUpdate;

	function __construct($diName, $initFields=true)
	{
		parent::__construct($diName, $initFields, 'Obj_MediaGalleryCategoryDI_di_'.$diName);

		//define object's module instanceId
		$arr = explode('_',$diName);
		$this->instanceId = end($arr);

		$this->itemsTable = 'mod_media_gallery_items_'.$this->instanceId;
	}

	function TgerBeforeDelete($cond, &$values)
	{
		//
		if (Utils::IsArray($values))
		{
			//data, not MediaGalleryItemDI - not to update itemsCount for each deleted category
			$di = Data::Create('dyn_media_gallery_item_'.$this->instanceId);
			foreach ($values as $value)
			{
				//remove items
				if ($value['category_type']==MEDIA_GALLERY_CATEGORY_TYPE_IMAGE)
				{
					$di->Reset('image_gallery');
				}
				else
				{
					$di->Reset('media_gallery');
				}
				$di->Delete('parent_id='.$value['id']);
			}
		}
	}

	function TgerAfterDelete($cond, $values)
	{
		mgUpdateItemsCount($this->table, $this->itemsTable);
	}

	function TgerBeforeUpdate($cond, &$updateValues)
	{
		//remember previos state
		$this->bufferUpdate = $this->SelectValues($cond);
		return true;
	}

	function TgerAfterUpdate($cond, $updateValues)
	{
		GLOBAL $Db;

//		$di = new Data('dyn_media_gallery_item_'.$this->instanceId);
//		$file_1 = $di->fields['file_media'];

		if (Utils::IsArray($this->bufferUpdate))
		{
			foreach ($this->bufferUpdate as $row)
			{
				if ($updateValues['category_type']==MEDIA_GALLERY_CATEGORY_TYPE_MEDIA
					&& $row['category_type']==MEDIA_GALLERY_CATEGORY_TYPE_IMAGE)
				{
					//move items from file_image to file media
					$sql = "UPDATE {$this->itemsTable} SET file_media=file_image,file_image=''"
						." WHERE parent_id='{$row['id']}'";
					$Db->Query($sql);

				}
				elseif ($updateValues['category_type']==MEDIA_GALLERY_CATEGORY_TYPE_IMAGE
							&& $row['category_type']==MEDIA_GALLERY_CATEGORY_TYPE_MEDIA)
				{
					//move items from file_media to file image
					$sql = "UPDATE {$this->itemsTable} SET file_image=file_media,file_media=''"
						." WHERE parent_id='{$row['id']}'";
					$Db->Query($sql);
				}
			}
		}
	}

}

/**
 * DataItem for media item
 *
 */
class MediaGalleryItemDI extends Data
{

	public $instanceId;
	public $categoriesTable;

	function __construct($diName, $initFields=true) {
		parent::__construct($diName, $initFields, 'Obj_MediaGalleryItemDI_di_'.$diName);

		//define object's module instanceId
		$arr = explode('_', $diName);
		$this->instanceId = end($arr);

		$this->categoriesTable = 'mod_media_gallery_categories_'.$this->instanceId;
	}

	function TgerAfterInsert($newId, $values)
	{
		mgUpdateItemsCount($this->categoriesTable, $this->table);
		return true;
	}

	function TgerAfterDelete($cond, $values)
	{
		mgUpdateItemsCount($this->categoriesTable, $this->table);
	}
}
?>