<?php
chdir('../');
require_once('init/init.php');

class CpCrossTreeListPage extends CPPage
{
    /**
     * @var CMSCrossTreeList
     */
	private $crossTreeList;

	function __construct()
	{
		parent::__construct();
		$this->App->Load('cross_tree_list', 'cms');
		
		$type = isset($_GET['type']) ? $_GET['type'] : null;
		$parentId = isset($_GET['parent_id']) ? $_GET['parent_id'] : null;
		
		$this->crossTreeList = new CMSCrossTreeList($type, $parentId);
	}

	function Page()
	{
		$this->SetBack();

		$this->crossTreeList->MakeLinkButtons();

		$this->SetTitle($this->crossTreeList->label, 'cms/cross_tree/uho.gif');

		if ($this->setBack > 0) $this->ParseBack();

		$this->pageContent .= $this->crossTreeList->Render();
	}
}

$Page = new CpCrossTreeListPage();
$Page->Render();

?>