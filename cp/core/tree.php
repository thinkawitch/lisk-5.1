<?php
chdir('../');
require_once('init/init.php');

class CpTreePage extends CPPage
{
	/**
	 * @var CMSTree
	 */
	private $tree;

	/**
	 * @var array
	 */
	private $treeJSStructure = array();
	
	/**
	 * @var array
	 */
	private $treeJSNodes = array();

	function __construct()
	{
		parent::__construct();

		$this->AddBookmark('Default view', '?action=default', 'img/cms/tree/tab_default.gif');
		$this->AddBookmark('Tree view', '?action=js', 'img/cms/tree/tab_tree_view.gif');
		$this->AddBookmark('Search', '?action=search', 'img/cms/tree/tab_search.gif');
		$this->AddBookmark('Adv. options', '?action=advanced', 'img/cms/tree/tab_adv_options.gif');

		$type = isset($_GET['type']) ? $_GET['type'] : null;
		$this->tree = new CMSTree($type);

		$this->SetGetAction('js', 'JSTree');

		$this->SetGetAction('search', 'TreeSearch');
		$this->SetGetPostAction('search', 'submit', 'TreeSearchSubmit');

		$this->SetPostAction('delete_selected', 'DeleteSelected');

		$this->SetGetAction('advanced', 'Advanced');
	}

	function Page()
	{
		$this->SetBack();
		$this->currentBookmark = 'Default view';

		// Links
		$this->tree->MakeLinkButtons();

		$this->tree->AdditionalNavigation();

		$this->SetTitle($this->tree->label, 'cms/tree/uho.gif');

		$this->pageContent .= $this->tree->Render();
	}

	function Advanced()
	{
		$this->currentBookmark = 'Adv. options';
		$this->pageContent .= $this->tree->Advanced();
	}

	function JSTree()
	{
		$this->SetBack();
		$this->currentBookmark = 'Tree view';

		$this->SetTitle($this->tree->label, 'cms/tree/uho.gif');

		$this->tree->MakeJsLinkButtons();

		$this->pageContent .= $this->tree->RenderJS();
	}

	function TreeSearch()
	{
		$this->SetBack ();
		$this->currentBookmark = 'Search';
		
		$this->SetTitle ( $this->tree->label, 'cms/tree/uho.gif' );
		
		$this->pageContent .= $this->tree->Search();
	}

	function DeleteSelected()
	{
		$this->tree->DeleteSelected();
	}
}

$Page = new CpTreePage();
$Page->Render();

?>