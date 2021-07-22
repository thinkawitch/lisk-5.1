<?php
chdir('../');
require_once('init/init.php');

class CpListPage extends CPPage
{

	function __construct()
	{
		parent::__construct();
	}

	function Page()
	{
	    $list = new CMSList($_GET['type']);
	    
		$this->SetTitle('List '.$list->dataItem->label, 'cms/list/uho.gif');
		Navigation::SetBack($this->setBack);

		// export
		$list->buttonExport = (isset($_GET['export']) && $_GET['export'] == true);
		// search
		$list->buttonSearch = (isset($_GET['quick_search']) && $_GET['quick_search'] == 1);
		// alphabetic navigation
		$list->alphabeticNavigation = (isset($_GET['alpha_nav']) && $_GET['alpha_nav'] > 0);
		// tpl
		$list->SetTemplate(@$_GET['tpl']);
		// columns
		$list->SetColumns(@$_GET['columns']);
		// cond
		$list->SetCond(@$_GET['cond']);
		
		$list->Init();
		
		// links
		$list->MakeLinkButtons();
		// initialize paging
		$this->Paging->SwitchOn('cp');

		if ($this->setBack > 0) $this->ParseBack();
		
		$this->pageContent .= $list->Render();
	}
}

$Page = new CpListPage();
$Page->Render();

?>