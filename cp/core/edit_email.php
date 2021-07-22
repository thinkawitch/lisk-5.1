<?php
chdir('../');
require_once('init/init.php');

class CpEditEmailPage extends CPPage
{
	/**
	 * @var CMSEditEmail
	 */
	private $cmsEditEmail;
	
	private $id;

	function __construct()
	{
		parent::__construct();
		$this->App->Load('cpmodules', 'lang');
		
		$this->id = isset($_GET['id']) ? $_GET['id'] : null;
		$this->cmsEditEmail = new CMSEditEmail($this->id);
		
		$this->SetPostAction('submit', 'Update');
	}

	function Page()
	{
		$emailName = "'".Format::Label($this->id)."'";
		$this->SetTitle('Edit email template '.$emailName, 'cms/edit_email/uho.gif');

		$this->pageContent .= $this->cmsEditEmail->Render();
		$this->ParseBack();
	}

	function Update()
	{
		$this->cmsEditEmail->Update();
		Navigation::JumpBack($this->back);
	}
}

$Page = new CpEditEmailPage();
$Page->Render();

?>