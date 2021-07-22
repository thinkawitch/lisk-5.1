<?php
require_once('init/init.php');

class CpTemplatePage extends CPPage
{
	function __construct()
	{
		parent::__construct();
		
		$this->SetGetAction('templates', 'ListTemplates');
		$this->SetGetAction('edit_tpl', 'EditTpl');
		$this->SetGetPostAction('edit_tpl', 'submit', 'SaveTpl');
	}

	function Page()
	{
		$this->ListTemplates();
	}

	public function ListTemplates()
	{
	    $this->title = 'Page Temlates';
	    $this->SetBack();
	    
	    $this->App->Load('tpl', 'cms');
	    $tree = new CMSContentTreeFiles();
	    
	    if ($this->setBack>0) $this->ParseBack();
	    
	    $tree->MakeJsLinkButtons();
		$this->pageContent .= $tree->RenderJS();
		
	}
	
	public function EditTpl()
	{
	    $this->currentBookmark = 'Templates';
	    $this->SetGlobalTemplate('0');
	    
	    $this->App->Load('tpl', 'cms');
	    $tree = new CMSContentTreeFiles();
	    
	    echo $tree->RenderFile($_GET['file']);
	    exit;
	}
	
	public function SaveTpl()
	{
	    $file = isset($_POST['file']) ? $_POST['file'] : null;
	    $content = isset($_POST['filecontent']) ? trim($_POST['filecontent']) : null;
	    
	    $this->App->Load('tpl', 'cms');
	    $tree = new CMSContentTreeFiles();
	    $tree->SaveFileContent($file, $content);
	    
	    echo "<strong>template file updated</strong>";
	    exit();
	}
}

$Page = new CpTemplatePage();
$Page->Render();
?>