<?php
require_once('init/init.php');

class CpEmailPage extends CPPage
{
	function __construct()
	{
		parent::__construct();
	}

	function Page()
	{
		$List = new CMSList('email');
		$List->Init();
		
		$this->SetTitle('List Email templates', 'cms/list/uho.gif');
		$this->SetBack();
		
		// Links
		$List->MakeLinkButtons();
		// initialize paging
		$this->Paging->SwitchOn('cp');

		$List->buttonDeleteAll = false;
		$List->buttonCheckbox = false;
		$List->buttonView = false;
		$List->buttonEdit = false;
		
		$List->AddButton('Edit','edit_email.php?&id=[id]&back=[back]', $this->Message('cpmodules','edit_hint'), '<img src="img/cms/list/edit.gif" width="8" height="14" border="0" align="absmiddle">');

		if ($this->setBack>0) $this->ParseBack();
		
		$this->pageContent .= $List->Render();
	}
}

$Page = new CpEmailPage();
$Page->Render();

?>