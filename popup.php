<?php
require_once('init/init.php');

class PopupPage extends Page
{
	function __construct()
	{
		parent::__construct();
	}

	function Page()
	{
		$this->SetGlobalTemplate(0);
		$this->LoadTemplate();
		
		$key = isset($this->parameters[0]) ? $this->parameters[0] : null;
		
		$di = Data::Create('content');
		$di->Get('`key`='.Database::Escape($key), 'content');

		$this->SetVariable('content', $di->value);
	}
}

$Page = new PopupPage();
$Page->Render();

?>