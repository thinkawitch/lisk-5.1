<?php
chdir('../');
require_once('init/init.php');

class CpMediaGalleryPreviewPage extends CPModulePage
{
	/**
	 * @var MediaGallery
	 */
	private $MG;
	
	function __construct()
	{
		parent::__construct(true);

		$this->App->Load('media_gallery', 'mod');
		$this->MG = new MediaGallery($this->iid);

		$this->SetGlobalTemplate('modules/media_gallery/global_media_gallery_preview');

		$this->SetGetAction('download', 'Download');
	}

	function Page()
	{
		$this->SetTitle('Media Gallery: Preview');
		$this->pageContent .= $this->MG->RenderCPPreview($_GET['id']);
	}

	function Download()
	{
		$id = @$_REQUEST['id'];
		$this->MG->Download($id);
	}

}

$Page = new CpMediaGalleryPreviewPage();
$Page->Render();
?>