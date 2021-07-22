<?php
require_once('init/init.php');

class CpDiConstructorPage extends CPPage
{
    /**
     * data item name
     *
     * @var string
     */
	private $diName;
	
	/**
	 * data item constructor
	 *
	 * @var DIConstructor
	 */
	private $constructor;

	function __construct()
	{
		parent::__construct();
		
		$this->App->Load('di_constructor', 'cms');

		$this->titlePicture = 'cms/di_constructor/uho.gif';

		$this->SetPostAction('change_order', 'ChangeOrder');
		$this->SetPostAction('save_listfields', 'SaveListFields');

		$this->SetGetAction('add', 'AddField');
		$this->SetGetPostAction('add', 'submit', 'AddFieldSubmit');

		$this->SetGetAction('edit', 'EditField');
		$this->SetGetPostAction('edit', 'submit', 'EditFieldSubmit');

		$this->SetGetAction('delete', 'DeleteField');

		$this->InitDI();
	}

	function Page()
	{
		GLOBAL $Auth;
		$this->back = $_SESSION['SYS_NAV_cms_di_constructor_back'];
		$this->setBack = $this->back + 1;
		
		if ($Auth->user['level'] == LISK_GROUP_DEVELOPERS)
		{
			$this->pageContent .= $this->constructor->RenderOverview();
		}
		else
		{
			Navigation::Jump('developers_only.php');
		}
		$this->SetBack();
		$this->ParseBack();
		$this->title = 'Object customization ';
	}

	function InitDI()
	{
		if (isset($_GET['name']))
		{
			$_SESSION['SYS_cms_di_constructor']			 = $_GET['name'];
			$_SESSION['SYS_NAV_cms_di_constructor_back'] = $_GET['back'];
		}
		$this->diName = $_SESSION['SYS_cms_di_constructor'];
		if (!strlen($this->diName))
		{
			GLOBAL $App;
			$App->RaiseError('DI Constructor. No DI specified;');
		}
		else
		{
			$this->constructor = new DIConstructor();
			$this->constructor->Init($this->diName);
		}
	}

	function AddField()
	{
		$this->ParseBack();

		switch ($_GET['field_type'])
		{
			case 'hidden_integer':
				$this->pageContent .= $this->constructor->RenderAddHiddenIntegerField();
				break;
				
			case LiskType::TYPE_INPUT:
				$this->pageContent .= $this->constructor->RenderAddInputField();
				break;
				
			case LiskType::TYPE_TEXT:
				$this->pageContent .= $this->constructor->RenderAddTextField();
				break;
				
			case LiskType::TYPE_HTML:
				$this->pageContent .= $this->constructor->RenderAddHtmlField();
				break;
				
			case LiskType::TYPE_DATE:
				$this->pageContent .= $this->constructor->RenderAddDateField();
				break;
				
			case LiskType::TYPE_DATETIME:
				$this->pageContent .= $this->constructor->RenderAddDateTimeField();
				break;
				
			case LiskType::TYPE_FILE:
				$this->pageContent .= $this->constructor->RenderAddFileField();
				break;
				
			case LiskType::TYPE_IMAGE:
				$this->pageContent .= $this->constructor->RenderAddImageField();
				break;
				
			case LiskType::TYPE_LIST:
				$this->pageContent .= $this->constructor->RenderAddListField();
				break;
				
			case LiskType::TYPE_PROP_LIST:
				$this->pageContent .= $this->constructor->RenderAddPropListField();
				break;
				
			default:
				$this->pageContent .= $this->constructor->RenderAddField();
				break;
		}

		$this->title = 'Add new field ';
	}

	function EditField()
	{
		$this->ParseBack();
		$this->pageContent .= $this->constructor->RenderEditField($_GET['field']);
		$this->title = 'Edit field ';
	}

	function EditFieldSubmit()
	{
		$this->constructor->EditFieldSubmit($_GET['field'], $_POST);
		Navigation::Jump('?');
	}

	function AddFieldSubmit()
	{
		$this->constructor->AddFieldSubmit($_POST);
		Navigation::Jump('?');
	}

	function DeleteField()
	{
		$this->constructor->DeleteField($_GET['field']);
		Navigation::Jump('?');
	}

	function ChangeOrder()
	{
		$this->constructor->SetOrderField($_POST['field_name'], $_POST['order_type']);
		Navigation::Jump('?');
	}

	function SaveListFields()
	{
		$this->constructor->SetListFields($_POST['rezlist']);
		Navigation::Jump('?');
	}
}

$Page = new CpDiConstructorPage();
$Page->Render();

?>