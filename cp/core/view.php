<?php
chdir('../');
require_once('init/init.php');

class CpViewPage extends CPPage
{
    /**
     * @var CMSView
     */
	private $cmsView;

	function __construct()
	{
		parent::__construct();
		$this->cmsView = new CMSView($_GET['type']);
	}
	
	function Page()
	{
	    $id = isset($_GET['id']) ? $_GET['id'] : null;
	    $allowEdit = (!isset($_GET['e']) || $_GET['e'] != 1);
	    $allowDelete = (!isset($_GET['d']) || $_GET['d'] != 1);
	    
		$this->cmsView->cond = 'id='.Database::Escape($id);

		$this->pageContent .= $this->cmsView->Render();
		
		$this->SetTitle('View '.$this->cmsView->dataItem->label, '');
		
		// ADD Links jolly
		$urlSuffix = $this->cmsView->GetRequiredUrlVars();
		
		if ($allowEdit)
		{
			$editUrl = "edit.php?type={$this->cmsView->dataItem->name}&back={$this->back}&id={$id}$urlSuffix";
			$this->AddLink('Edit Record ', $editUrl, 'img/ico/links/edit.gif');
		}

		if ($allowDelete)
		{
			$delUrl = "del.php?type={$this->cmsView->dataItem->name}&back={$this->back}&id={$id}$urlSuffix\" onclick=\"return ShowConfirm('Delete this record?', this)";
			$this->AddLink('Delete Record ', $delUrl, 'img/ico/links/delete.gif');
		}

		$this->ParseBack();
	}
}

$Page = new CpViewPage();
$Page->Render();

?>