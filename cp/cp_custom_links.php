<?php
require_once('init/init.php');

class CpCustomLinksPage extends CPPage
{
	private $diNameLinks = 'cp_custom_link';
	private $diNameUser = 'user_cp';
	
	private $titleText;

	function __construct()
	{
		parent::__construct();
		
		$this->SetGetAction('list', 'ShowCustomLinks');
		
		$this->SetGetPostAction('add', 'submit', 'AddLinkPost');
		$this->SetGetAction('add', 'AddLinkForm');
		$this->SetGetPostAction('edit', 'submit', 'EditLinkPost');
		$this->SetGetAction('edit', 'EditLinkForm');
		$this->SetGetAction('delete', 'DeleteLink');
		
		$this->SetGetPostAction('order', 'submit', 'OrderLinkPost');
		$this->SetGetAction('order', 'OrderLinkForm');
		
		$this->titlePicture = 'cms/list/uho.gif';
		$this->titleText = 'Control Panel Custom Links';
	}

	function Page()
	{
		$this->ShowCustomLinks();
	}
	
	function ShowCustomLinks()
	{
		GLOBAL $Page, $Auth;
		$this->currentBookmark = LANG_CP_CUSTOM_LINKS;
		$this->SetTitle($this->titleText);
		$this->SetBack();
		
		$dataItem = Data::Create($this->diNameLinks);
		$List = new CMSList($dataItem);
        $List->Init();
		$List->buttonAdd = false;
		$List->buttonView = false;
		$List->buttonEdit = false;
		$List->buttonDelete = false;
		$List->buttonDeleteAll = false;
		$List->buttonCheckbox = false;
		$List->buttonExport = false;
		
		$List->AddButton($Page->Message('main','edit'), '?action=edit&id=[id]&back=[back]', 'Edit', '<img src="img/cms/list/edit.gif" width="8" height="14" border="0" align="absmiddle">');
		
		$List->AddButton($Page->Message('main','delete'), "#delete\" class=\"delete\" rel=\"?action=delete&id=[id]&back=[back]\" onclick=\"return false", $Page->Message('cpmodules','delete_hint'), '<img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle">');
		
		$this->AddLink($Page->Message('main','add').' '.$dataItem->label,'?action=add&back='.$this->setBack,'img/ico/links/add.gif','Add new record to the list.');
		
		$this->AddLink($Page->Message('main','order').' '.$dataItem->label,'?action=order&back='.$this->setBack,'img/ico/links/order.gif','Change records order.');
		
		$List->MakeLinkButtons();

		//get user links
		$links = $this->GetUserLinks($Auth->user['id']);
		
		$this->Paging->SwitchOff();
		$this->pageContent .= $List->Render('cms_list', $links);
		
	}
	
	function AddLinkForm()
	{
		$this->currentBookmark = LANG_CP_CUSTOM_LINKS;
		$this->SetTitle($this->titleText.': Add', $this->titlePicture);
		
		$CMSAdd = new CMSAdd($this->diNameLinks);

		$this->pageContent .= $CMSAdd->Render();

		if ($this->setBack>0) $this->ParseBack();
	}

	function AddLinkPost()
	{
		GLOBAL $Auth;
		
		//get user links
		$links = $this->GetUserLinks($Auth->user['id']);
		$links[] = array(
			'id' => uniqid('1'),
			'name' => $_POST['name'],
			'link' => $_POST['link'],
		);
		
		$this->SaveUserLinks($Auth->user['id'], $links);
		
		if (@$_POST['post_action']==2) Navigation::Jump(Navigation::Referer());
		else Navigation::JumpBack($this->back);
	}
	
	function EditLinkForm()
	{
		GLOBAL $Parser,$App,$Auth;
		$this->currentBookmark = LANG_CP_CUSTOM_LINKS;
		$this->SetTitle($this->titleText.': Edit', $this->titlePicture);
		
		$App->Load('cpmodules','lang');
		$dataItem = Data::Create($this->diNameLinks);
		
		$dataItem->value = $this->GetUserLink($Auth->user['id'],$_GET['id']);

		$this->pageContent .= $Parser->MakeDynamicForm($dataItem,'cms/edit');

		if ($this->setBack>0) $this->ParseBack();
	}
	
	function EditLinkPost()
	{
		GLOBAL $Auth;
		
		$newValue = array(
			'id' => $_GET['id'],
			'name' => $_POST['name'],
			'link' => $_POST['link'],
		);
		
		$links = $this->UpdateUserLink($Auth->user['id'], $_GET['id'], $newValue);
		
		$this->SaveUserLinks($Auth->user['id'], $links);
		
		Navigation::JumpBack($this->back);
	}
	
	function DeleteLink()
	{
		GLOBAL $Auth;
		
		$links = $this->RemoveUserLink($Auth->user['id'], $_GET['id']);
		
		$this->SaveUserLinks($Auth->user['id'], $links);
		
		Navigation::JumpBack($this->back);
	}
	
	
	function OrderLinkForm()
	{
		GLOBAL $Auth, $Parser;
		$this->currentBookmark = LANG_CP_CUSTOM_LINKS;
		$this->SetTitle($this->titleText.': Order', $this->titlePicture);
		
		$links = $this->GetUserLinks($Auth->user['id']);
		
		$size = Utils::IsArray($links) ? count($links) : 0;
		
		if (Utils::IsArray($links))
		{
			foreach ($links as $key=>$row) {
				$viewValue = $row['name'];
				$viewValue = (strlen($viewValue) > 80) ? substr($viewValue, 0, 80).'...' : $viewValue;
				$links[$key]['name']=$viewValue;
			}
		}

		$Parser->SetCaptionVariables(array ('size' => $size));
		
		$this->pageContent .= $Parser->MakeList($links, 'cms/order');

		$this->ParseBack();
	}

	function OrderLinkPost()
	{
		GLOBAL $Auth;
		
		$links = $this->GetUserLinks($Auth->user['id']);
		$newLinks = array();
		
		$ids = explode(',',$_POST['id_set']);
		
		if (Utils::IsArray($links))
		{
			foreach ($ids as $id)
			{
				foreach ($links as $one)
				{
					if ($one['id']==$id)
					{
						$newLinks[] = $one;
					}
				}
			}
			
			$this->SaveUserLinks($Auth->user['id'], $newLinks);
		}
		
		Navigation::JumpBack($this->back);
	}
	
	
	/**
	 * Get user links from db
	 *
	 * @param integer $userId
	 * @return array
	 */
	function GetUserLinks($userId)
	{
		$dataItem = Data::Create($this->diNameUser);
		$dataItem->ReSet('custom_links');
		$links = @unserialize($dataItem->GetValue('id='.$userId, 'custom_links'));
		if (Utils::IsArray($links)) return $links;
		else return array();
	}
	
	/**
	 * Save user links into db
	 *
	 * @param integer $userId
	 * @param array $links
	 */
	function SaveUserLinks($userId, $links)
	{
		$dataItem = Data::Create($this->diNameUser);
		$dataItem->ReSet('custom_links');
		$dataItem->Update('id='.$userId, array('custom_links'=>serialize($links)));
	}
	
	/**
	 * Get one link from links array
	 *
	 * @param integer $userId
	 * @param string $linkId
	 * @return array
	 */
	function GetUserLink($userId,$linkId)
	{
		$links = $this->GetUserLinks($userId);
		$link = array();
		
		if (Utils::IsArray($links))
		{
			foreach ($links as $one)
			{
				if ($one['id']==$linkId)
				{
					$link = $one;
					break;
				}
			}
		}
		
		return $link;
	}
	
	/**
	 * Update specified linkId with new value
	 *
	 * @param integer $userId
	 * @param string $linkId
	 * @param array $newValue
	 * @return array
	 */
	function UpdateUserLink($userId, $linkId, $newValue)
	{
		$links = $this->GetUserLinks($userId);
		if (Utils::IsArray($links))
		{
			foreach ($links as $k=>$one)
			{
				if ($one['id']==$linkId)
				{
					$links[$k] = $newValue;
					break;
				}
			}
		}
		return $links;
	}
	
	/**
	 * Remove specified link from links array
	 *
	 * @param integer $userId
	 * @param string $linkId
	 * @return array
	 */
	function RemoveUserLink($userId, $linkId)
	{
		$links = $this->GetUserLinks($userId);
		if (Utils::IsArray($links))
		{
			foreach ($links as $k=>$one)
			{
				if ($one['id']==$linkId)
				{
					unset($links[$k]);
					break;
				}
			}
		}
		return $links;
	}
}

$Page = new CpCustomLinksPage();
$Page->Render();

?>