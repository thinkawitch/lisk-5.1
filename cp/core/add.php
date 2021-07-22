<?php
chdir('../');
require_once('init/init.php');

class CpAddPage extends CPPage
{
    /**
     * @var CMSAdd
     */
	private $cmsAdd;

	function __construct()
	{
		parent::__construct();
		
		$type = $_GET['type'];
		$redefine = isset($_GET['redefine']) ? $_GET['redefine']: 'add';
		
		$this->cmsAdd = new CMSAdd($type, $redefine);
		
		$this->SetPostAction('submit', 'Insert');
	}

	function Page()
	{
		$this->SetTitle('Add '.$this->cmsAdd->dataItem->label, 'cms/add/uho.gif');
		
		$this->pageContent.= $this->cmsAdd->Render();
		$this->ParseBack();
	}

	function Insert()
	{
		$this->cmsAdd->Insert();
		
		$pa = isset($_POST['post_action']) ? $_POST['post_action'] : null;
		if ($pa == 2) Navigation::Jump(Navigation::Referer());
		else Navigation::JumpBack($this->back);
	}

}

$Page = new CpAddPage();
$Page->Render();

?>