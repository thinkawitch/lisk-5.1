<?php
chdir('../');
require_once('init/init.php');

class CpCrossListPage extends CPPage
{
    /**
     * @var CMSCrossList
     */
	private $crossList;
	
	function __construct()
	{
		parent::__construct();
		
		$type = $_GET['type'];
		$cond = isset($_GET['cond']) ? $_GET['cond'] : null;
		
		$this->crossList = new CMSCrossList($type, $cond);
	}

	function Page()
	{
		$this->SetTitle($this->CrossList->label);
        $this->SetBack();
        
		$this->CrossList->MakeLinkButtons();
		
		$this->pageContent .= $this->crossList->Render();
	}
}

$Page = new CpCrossListPage();
$Page->Render();

?>