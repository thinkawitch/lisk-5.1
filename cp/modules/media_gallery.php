<?php
chdir('../');
require_once('init/init.php');

class CpMediaGalleryPage extends CPModulePage
{
	/**
	 * @var MediaGallery
	 */
	private $MG;

	/**
	 * @var CMSMediaGallery
	 */
	private $Tree;

	private $CmsAddNode, $CmsEditNode, $CmsOrderNode;
	private $CmsAddPoint, $CmsEditPoint, $CmsOrderPoint;

	function __construct()
	{
		parent::__construct(true);

		$this->App->Load('media_gallery', 'mod');
		$this->App->LoadModule('installed/media_gallery/media_gallery.cms.php');

		$this->MG = new MediaGallery($this->iid);

		$this->Tree = new CMSMediaGallery($this->MG);

		$this->CmsAddNode   = new CMSAdd($this->Tree->node);
		$this->CmsEditNode  = new CMSEdit($this->Tree->node);
		$this->CmsOrderNode = new CMSOrder($this->Tree->node);

		$this->CmsAddPoint   = new CMSAdd($this->Tree->point);
		$this->CmsEditPoint  = new CMSEdit($this->Tree->point);
		$this->CmsOrderPoint = new CMSOrder($this->Tree->point);

		if ($this->Tree->categoryType == MEDIA_GALLERY_CATEGORY_TYPE_IMAGE)
		{
			$this->CmsAddPoint->dataItem->ReSet('image_gallery');
			$this->CmsEditPoint->dataItem->ReSet('image_gallery');
		}
		else
		{
			$this->CmsAddPoint->dataItem->ReSet('media_gallery');
			$this->CmsEditPoint->dataItem->ReSet('media_gallery');
		}

		$this->AddBookmark('Media Gallery', '?action=list', 'img/modules/media_gallery/tab_media_gallery.gif');
		$this->AddBookmark('Settings', '?action=settings', 'img/modules/media_gallery/settings.gif');

		$this->titlePicture = 'modules/media_gallery/h_media_gallery.gif';

		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');

		$this->AddPostHandler('list__action', 'DeleteSelected', 'delete_selected');

		$this->SetGetAction('list', 'MediaGallery');

		$this->SetPostAction('upload', 'UploadArchive');
		$this->SetGetAction('upload', 'ShowUpload');

		$this->SetGetPostAction('order_node', 'submit', 'OrderNodePost');
		$this->SetGetAction('order_node', 'OrderNodeForm');
		$this->SetGetPostAction('order_point', 'submit', 'OrderPointPost');
		$this->SetGetAction('order_point', 'OrderPointForm');

		$this->SetGetPostAction('add_node', 'submit', 'AddNodePost');
		$this->SetGetAction('add_node', 'AddNodeForm');
		$this->SetGetPostAction('edit_node', 'submit', 'EditNodePost');
		$this->SetGetAction('edit_node', 'EditNodeForm');
		$this->SetGetAction('delete_node', 'DeleteNode');

		$this->SetGetPostAction('add_point', 'submit', 'AddPointPost');
		$this->SetGetAction('add_point', 'AddPointForm');
		$this->SetGetPostAction('edit_point', 'submit', 'EditPointPost');
		$this->SetGetAction('edit_point', 'EditPointForm');
		$this->SetGetAction('delete_point', 'DeletePoint');

		$this->SetGetAction('view', 'View');
		$this->SetGetAction('preview', 'Preview');

		$this->SetGetPostAction('paging', 'submit', 'PagingPost');
		$this->SetGetAction('paging', 'PagingForm');

		$this->SetGetAction('download', 'Download');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');
	}

	function Page()
	{
		$this->MediaGallery();
	}

	function MediaGallery()
	{
		Navigation::SetBack($this->back); //backs!!!

		$this->currentBookmark = 'Media Gallery';
		$this->SetTitle('Media Gallery', $this->titlePicture);

		// Links
		$this->Tree->MakeLinkButtons($this);
		$this->Tree->AdditionalNavigation($this);

		if ($this->setBack > 0) $this->ParseBack();

		$this->pageContent .= $this->Tree->Render($this->setBack);
	}

	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('Media Gallery Settings');
		$this->currentBookmark = 'Settings';

		$this->settingsFields = array (
			'preview_in_popup'	=> array(
				'label'	=> 'Preview In Popup',
				'type'	=> 'flag',
				'hint'	=> 'Show Media Preview Page In Popup'
			),
			'items_per_page' => array(
				'type'  => 'input',
				'hint'  => 'Number of entries displayed on one page. Set to zero to display all entries',
				'label' => 'Entries Per Page'
			),
			'pages_per_page' => array(
				'type' => 'input',
				'hint' => 'Number of pages displayed on the paging line'
			),
			'columns_per_table' => array(
				'type' => 'input',
				'hint' => 'Number of columns to display items'
			),
		);
		$this->settingsFieldsValues=array(
			'preview_in_popup' => $this->MG->confPreviewInPopup,
			'items_per_page' => $this->MG->confPagingItemsPerPage,
			'pages_per_page' => $this->MG->confPagingPagesPerPage,
			'columns_per_table' => $this->MG->confColumnsPerTable,
		);
		$this->customizableDI = array($this->MG->confDICategoriesName, $this->MG->confDIItemsName);

		$this->pageContent .= $this->RenderSettingsPage($this->MG);
	}

	function SaveSettings()
	{
		$settings = $_POST;

		$this->MG->confPreviewInPopup = isset($settings['preview_in_popup_checked']) ? true : false;
		$this->MG->confPagingItemsPerPage = $settings['items_per_page'];
		$this->MG->confPagingPagesPerPage = $settings['pages_per_page'];
		$this->MG->confColumnsPerTable = $settings['columns_per_table'];

		$this->MG->SaveSettings();

		Navigation::Jump('?action=settings');
	}

	function DeleteSelected()
	{
		$this->Tree->DeleteSelected();
	}

	function AddNodeForm()
	{
		$this->currentBookmark = 'Media Gallery';
		$this->SetTitle('Media Gallery: Add Category', $this->titlePicture);

		$this->pageContent .= $this->CmsAddNode->Render();

		if ($this->setBack > 0) $this->ParseBack();
	}

	function AddNodePost()
	{
		$this->CmsAddNode->Insert();
		if (@$_POST['post_action'] == 2) Navigation::Jump(Navigation::Referer());
		else Navigation::JumpBack($this->back);
	}

	function EditNodeForm()
	{
		$this->currentBookmark = 'Media Gallery';
		$this->SetTitle('Media Gallery: Edit Category', $this->titlePicture);

		$this->CmsEditNode->cond = "id='{$_GET['id']}'";
		$this->pageContent .= $this->CmsEditNode->Render();

		if ($this->setBack > 0) $this->ParseBack();
	}

	function EditNodePost()
	{
		$this->CmsEditNode->cond = "id='{$_GET['id']}'";
		$this->CmsEditNode->Update();
		Navigation::JumpBack($this->back);
	}

	function DeleteNode()
	{
		$this->Tree->node->Delete("id={$_GET['id']}");
		Navigation::JumpBack($this->back);
	}

	function OrderNodeForm()
	{
		$this->currentBookmark = 'Media Gallery';
		$this->SetTitle('Media Gallery: Order Categories', $this->titlePicture);

		$this->pageContent .= $this->CmsOrderNode->Render();

		$this->ParseBack();
	}

	function OrderNodePost()
	{
		$this->CmsOrderNode->Save();
		Navigation::JumpBack($this->back);
	}

	function AddPointForm()
	{
		$this->currentBookmark = 'Media Gallery';
		$this->SetTitle('Media Gallery: Add Item', $this->titlePicture);

		$this->pageContent .= $this->CmsAddPoint->Render();

		if ($this->setBack > 0) $this->ParseBack();
	}

	function AddPointPost()
	{
		$this->CmsAddPoint->Insert();
		if (@$_POST['post_action'] == 2) Navigation::Jump(Navigation::Referer());
		else Navigation::JumpBack($this->back);
	}

	function OrderPointForm()
	{
		$this->currentBookmark = 'Media Gallery';
		$this->SetTitle('Media Gallery: Order Items', $this->titlePicture);

		$this->pageContent .= $this->CmsOrderPoint->Render();

		$this->ParseBack();
	}

	function OrderPointPost()
	{
		$this->CmsOrderPoint->Save();
		Navigation::JumpBack($this->back);
	}

	function EditPointForm()
	{
		$this->currentBookmark = 'Media Gallery';
		$this->SetTitle('Media Gallery: Edit Item', $this->titlePicture);

		$this->CmsEditPoint->cond = "id='{$_GET['id']}'";
		$this->pageContent .= $this->CmsEditPoint->Render();

		if ($this->setBack > 0) $this->ParseBack();
	}

	function EditPointPost()
	{
		$this->CmsEditPoint->cond = "id='{$_GET['id']}'";
		$this->CmsEditPoint->Update();
		Navigation::JumpBack($this->back);
	}

	function DeletePoint()
	{
		$this->Tree->point->Delete("id={$_GET['id']}");
		Navigation::JumpBack($this->back);
	}

	function Download()
	{
		$id = @$_REQUEST['id'];
		$this->MG->Download($id);
	}

	function ShowUpload()
	{
		$this->currentBookmark = 'Media Gallery';
		$this->SetTitle('Upload files in archive', $this->titlePicture);

		if ($this->setBack > 0) $this->ParseBack();

		$this->pageContent .= $this->Tree->RenderForm();
	}

	function UploadArchive()
	{
		GLOBAL $App;
        $id = intval($_POST['id']);
        
        $nodeValue = $this->Tree->node->GetValue('id='.$id);
        $res = 0;
        
		if (Utils::IsArray($nodeValue))
		{
		    $res = $this->Tree->UploadArchive($nodeValue);
		}

		if ($res != 1)
		{
			switch ($res)
			{
				case 2:
					$this->SetError('File not uploaded');
					break;
					
				case 3:
					$this->SetError('Can\'t create temporary directory');
					break;
					
				case 4:
					$this->SetError('Wrong file format');
					break;
					
				case 5:
					$this->SetError('Can\'t copy file');
					break;
					
				case 6:
					$this->SetError('Can\'t extract files');
					break;
					
				default:
					$App->RaiseError('media_gallery.php::UploadArchive error');
			}
		}
		Navigation::JumpBack($this->back);
	}

	function GetSnippet()
	{
		$code = $this->MG->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}

}

$Page = new CpMediaGalleryPage();
$Page->Render();
?>