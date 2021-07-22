<?php
require_once('init/init.php');

class CpContentPage extends CPPage
{
    /**
     * data item
     *
     * @var Data
     */
	private $dataItem;

	/**
	 * cms tree
	 *
	 * @var CMSTree
	 */
	private $tree;

	/**
	 * cms list
	 *
	 * @var CMSList
	 */
	private $list;

	function __construct()
	{
		parent::__construct();

		$this->dataItem = Data::Create('content');
		$this->list = new CMSList($this->dataItem);
        $this->list->Init();
		$this->tree = new CMSTree('content');

		$this->SetPostAction('delete_selected', 'DeleteSelected');

		$this->SetGetAction('get_code', 'GetCode');
	}

	function Page()
	{
		GLOBAL $Parser;

		$this->SetBack();

		$this->tree->MakeLinkButtons();
		$this->tree->AdditionalNavigation();

		$this->SetTitle($this->tree->label, 'cms/tree/uho.gif');

		switch ($this->tree->clMode)
		{
			case 0:
				$this->pageContent .= $this->tree->Render();
				break;
				
			case 1:
				$this->pageContent .= $this->RenderListContentCategories();
				break;
				
			case 2:
				$this->pageContent .= $this->RenderListContentBlocks();
				break;
		}

		if ($this->setBack>0) $this->ParseBack();
	}

	function RenderListContentCategories()
	{
		$list = new CMSList($this->tree->node);
        $list->Init();
		$cond = "parent_id={$this->tree->cl}";
		$cond .= ($this->tree->cond!=null) ? '&'.$this->tree->cond : '';

		$list->SetCond($cond);

		$list->RemoveButton($this->Message('main','delete'), '[id]==2');
		$list->RemoveButton('<input type="checkbox" name="ids[]" value="[id]" />', '[id]==2');

		$list->SetFieldLink('name', '?cl=[id]&back=[back]');

		return $list->Render();
	}

	function RenderListContentBlocks()
	{
		$this->list->SetCond('parent_id='.$this->tree->cl);
		$this->list->AddButton('Get Code', "javascript: popupWindow('?action=get_code&name=[key]',400,300,false);", 'Get code', '<img src="img/cms/modules/i_get_code.gif" border="0" align="absmiddle" width="16" height="13" />');
		$this->list->buttonEdit = false;
		$this->list->AddButton($this->Message('main','edit'), 'content.php?id=[id]&back=[back]', $this->Message('cpmodules','edit_hint'), '<img src="img/cms/list/edit.gif" width="8" height="14" border="0" align="absmiddle">');

		$this->list->buttonCheckbox = false;
		$this->list->buttonDeleteAll = false;
		return $this->list->Render();
	}

	function GetCode()
	{
		GLOBAL $Parser,$App;
		$this->SetGlobalTemplate(0);

		$App->debug = false;
		$html = $Parser->MakeView(array(
			'code'	=> '<lisk:content name="'.$_GET['name'].'" />'
		),'cms/modules/get_snippet_code','view');

		echo $html;
		exit();
	}

	function DeleteSelected()
	{
		$this->tree->DeleteSelected();
	}
}

$Page = new CpContentPage();
$Page->Render();

?>