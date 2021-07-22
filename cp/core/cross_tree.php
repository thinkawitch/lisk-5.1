<?php
chdir('../');
require_once('init/init.php');

class CpCrossTreePage extends CPPage
{
    /**
     * @var CMSCrossTree
     */
	private $crossTree;

	function __construct()
	{
		parent::__construct();
		$this->App->Load('cross_tree', 'cms');
		
		$type = isset($_GET['type']) ? $_GET['type'] : null;
		$cond = isset($_GET['cond']) ? $_GET['cond'] : null;
		
		$this->crossTree = new CMSCrossTree($type, $cond);
	}

	function Page()
	{
		$this->SetBack();
        if ($this->setBack > 0) $this->ParseBack();
        
		$this->crossTree->MakeLinkButtons();
		
		$this->SetTitle($this->crossTree->label, 'cms/cross_tree/uho.gif');
		
		$this->pageContent .= $this->crossTree->Render();
	}
}

$Page = new CpCrossTreePage();
$Page->Render();

?>