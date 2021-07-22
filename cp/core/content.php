<?php
chdir('../');
require_once('init/init.php');

class CpEditContentPage extends CPPage
{
	/**
	 * @var Data
	 */
	private $content;
	
	private $id;
	private $cond;

	function __construct()
	{
		parent::__construct();

		$this->App->Load('cpmodules', 'lang');

		$this->content = Data::Create('content');
		
		$this->id = isset($_GET['id']) ? $_GET['id'] : null;
		$this->cond = 'id='.Database::Escape($this->id);

		$this->SetPostAction('submit', 'Update');
	}

	function Page()
	{
		$this->content->Get($this->cond);
		$this->SetTitle('Edit content: '.Format::Label($this->content->value['name']), 'cms/edit/uho.gif');
		
		$this->ParseBack();
		
		$sb = isset($_GET['sb']) ? $_GET['sb'] : null;
		if ($sb) $this->SetBack();
		
		$this->pageContent .= $this->Parser->MakeForm($this->content, 'cms/content', 'content');
		
		
	}

	function Update()
	{
		$this->content->Update($this->cond, $_POST);
		Navigation::JumpBack($this->back);
	}
}

$Page = new CpEditContentPage();
$Page->Render();

?>