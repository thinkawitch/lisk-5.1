<?php
chdir('../');
require_once('init/init.php');

class CpNodeTreePage extends CPPage
{
    /**
     * @var CMSNodeTree
     */
	private $tree;

	function __construct()
	{
		parent::__construct();
		$this->App->Load('node_tree', 'cms');
		
		$type = isset($_GET['type']) ? $_GET['type'] : null;
		
		$this->tree = new CMSNodeTree($type);
		$this->tree->back = $this->back;
	}

	function Page()
	{
		$this->SetBack();
		$this->SetTitle($this->tree->label, 'cms/tree/uho.gif');

		$this->tree->MakeLinkButtons();
		$this->tree->AdditionalNavigation();
		
		$this->pageContent .= $this->tree->Render();
	}
}

$Page = new CpNodeTreePage();
$Page->Render();

?>