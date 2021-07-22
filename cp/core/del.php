<?php
chdir('../');
require_once('init/init.php');

class CpDeletePage extends CPPage
{
    /**
     * @var CMSDel
     */
	private $cmsDel;

	function __construct()
	{
		parent::__construct();
		
		$type = isset($_GET['type']) ? $_GET['type'] : null;
		$redefine = isset($_GET['redefine']) ? $_GET['redefine'] : 'del';
		
		$this->cmsDel = new CMSDel($type, $redefine);
	}

	function Page()
	{
		$this->cmsDel->Delete();
		
		$back = isset($_GET['back']) ? $_GET['back'] : 0;
		
		Navigation::JumpBack($back);
	}
}

$Page = new CpDeletePage();
$Page->Render();

?>