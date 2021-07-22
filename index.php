<?php
require_once('init/init.php');

class IndexPage extends Page
{
	function __construct()
	{
		parent::__construct();
	}

	function Page()
	{
        $this->LoadTemplate();
   	}
}


$Page = new IndexPage();
$Page->Render();
