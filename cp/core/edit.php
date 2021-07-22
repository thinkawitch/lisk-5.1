<?php
chdir('../');
require_once('init/init.php');

class CpEditPage extends CPPage
{
    /**
     * @var CMSEdit
     */
	private $cmsEdit;

	function __construct()
	{
		parent::__construct();
		
		$type = isset($_GET['type']) ? $_GET['type'] : null;
		$redefine = isset($_GET['redefine']) ? $_GET['redefine']: 'edit';
		
		$cond = isset($_GET['cond']) ? $_GET['cond'] : null;
		if ($cond==null) $cond = 'id='.intval($_GET['id']);
		
		$this->cmsEdit = new CMSEdit($type);
		$this->cmsEdit->dataItem->ReSet($redefine);
		$this->cmsEdit->cond = $cond;

		$this->SetPostAction('submit', 'Update');
	}

	function Page()
	{
		$this->SetTitle('Edit '.$this->cmsEdit->dataItem->label, 'cms/edit/uho.gif');
		
		$sb = isset($_GET['sb']) ? $_GET['sb'] : null;
		if ($sb) $this->SetBack();
		
		$this->pageContent .= $this->cmsEdit->Render();
		$this->ParseBack();
	}

	function Update()
	{
		$this->cmsEdit->Update();
		Navigation::JumpBack($this->back);
	}
}

$Page = new CpEditPage();
$Page->Render();

?>