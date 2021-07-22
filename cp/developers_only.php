<?php
require_once('init/init.php');

class CpDevelopersOnlyPage extends CPPage
{

	function __construct()
	{
		parent::__construct();
	}
	
	function Page()
	{
		$this->ShowDevelopersOnly();
	}
	
	function ShowDevelopersOnly()
	{
		GLOBAL $Parser;
		$this->SetTitle('Developers Only!');
		
		$this->pageContent .= $Parser->GetHTML('developers_only', 'view');
		
		$this->ParseBack();
	}
}

$Page = new CpDevelopersOnlyPage();
$Page->Render();
?>