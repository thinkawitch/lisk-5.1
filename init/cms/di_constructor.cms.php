<?php
/**
 * DataItem Constructor
 * @package lisk
 *
 */

$GLOBALS['DATA_DI_CONSTRUCTOR_ADD'] = array(
	'fields'	=> array(
		'field_type'	=> array(
			'type'			=> LiskType::TYPE_LIST,
			'object'		=> 'def_di_constructor_pub_fields',
			'form'			=> 'id="field_type" OnChange="SwitchFieldType();"'
		),
		'name'			=> array(
			'type'			=> LiskType::TYPE_INPUT,
			'check'			=> 'empty'
		),
		'label'			=> LiskType::TYPE_INPUT,
		'path'			=> array(
			'type'			=> LiskType::TYPE_INPUT,
			'form'			=> 'id="path"'
		),
		'thumbnails'	=> array(
			'type'			=> LiskType::TYPE_LIST,
			'object'		=> 'def_di_constructor_thumbnails',
			'form'			=> 'id="thumbnails" OnChange="SwitchThumbnails();"'
		),
		'required'		=> array(
			'type'			=> LiskType::TYPE_FLAG
		),
		'def_value'		=> LiskType::TYPE_INPUT
	)
);

$GLOBALS['LIST_DI_CONSTRUCTOR_THUMBNAILS'] = array(
	0		=> 'No',
	1		=> 'Yes',
);

$GLOBALS['LIST_DI_CONSTRUCTOR_PUB_FIELDS'] = array(
	LiskType::TYPE_INPUT    => 'Input',
	LiskType::TYPE_TEXT		=> 'Text',
	LiskType::TYPE_HTML		=> 'HTML',
	LiskType::TYPE_DATE		=> 'Date',
	LiskType::TYPE_DATETIME	=> 'Date and time',
	LiskType::TYPE_FILE		=> 'File',
	LiskType::TYPE_IMAGE	=> 'Image',
	LiskType::TYPE_LIST		=> 'List',
	LiskType::TYPE_PROP_LIST=> 'Prop List'
);

class DIConstructor
{

	/**
	 * data item
	 *
	 * @var Data
	 */
	public $di;
	public $name;
	public $configArray;

	public $systemFields = array();
	public $publicFields = array();
	public $listFields;
	public $order;
	public $orderType = array(
		'ASC'  => 'Ascending',
		'DESC' => 'Descending',
	);

	function __construct()
	{
		//
	}

	function Init($name)
	{
		GLOBAL $Db;

		$this->name = $name;

		$data = $Db->Get('name='.Database::Escape($name), 'data', 'sys_di');
		$this->configArray = unserialize($data);

		$this->di = Data::Create($name);

		foreach ($this->di->fields as $name=>$obj)
		{
			if (key_exists($obj->type, $GLOBALS['LIST_DI_CONSTRUCTOR_PUB_FIELDS']) && !$obj->isSystem)
			{
				$this->publicFields[$name] = $obj;
			}
			else
			{
				$this->systemFields[$name] = $obj;
			}
		}

		$this->listFields = $this->di->listFields;

		$this->order = $this->di->order;
	}

	function RenderAddField()
	{
		GLOBAL $Parser,$App;
		$di = Data::Create('di_constructor_add');
		$Parser->SetAddVariables(array(
			'sys_path'	=> $App->sysRoot.$App->filePath
		));
		return $Parser->MakeForm($di, 'cms/di_constructor/add', 'form');
	}

	function RenderAddHiddenIntegerField()
	{
		GLOBAL $Parser;
		$di = Data::Create('di_constructor_add');
		return $Parser->MakeForm($di, 'cms/di_constructor/add/hidden_integer', 'form');
	}

	function RenderAddInputField()
	{
		GLOBAL $Parser;
		$di = Data::Create('di_constructor_add');
		return $Parser->MakeForm($di, 'cms/di_constructor/add/input', 'form');
	}

	function RenderAddTextField()
	{
		GLOBAL $Parser;
		$di = Data::Create('di_constructor_add');
		return $Parser->MakeForm($di, 'cms/di_constructor/add/text', 'form');
	}

	function RenderAddHtmlField()
	{
		GLOBAL $Parser;
		$di = Data::Create('di_constructor_add');
		return $Parser->MakeForm($di, 'cms/di_constructor/add/html', 'form');
	}

	function RenderAddDateField()
	{
		GLOBAL $Parser;
		$di = Data::Create('di_constructor_add');
		return $Parser->MakeForm($di, 'cms/di_constructor/add/date', 'form');
	}

	function RenderAddDateTimeField()
	{
		GLOBAL $Parser;
		$di = Data::Create('di_constructor_add');
		return $Parser->MakeForm($di, 'cms/di_constructor/add/datetime', 'form');
	}

	function RenderAddFileField()
	{
		GLOBAL $Parser,$App;
		$di = Data::Create('di_constructor_add');
		$Parser->SetAddVariables(array(
			'sys_path'	=> $App->sysRoot.$App->filePath
		));
		return $Parser->MakeForm($di, 'cms/di_constructor/add/file', 'form');
	}

	function RenderAddImageField()
	{
		GLOBAL $Parser,$App;
		$di = Data::Create('di_constructor_add');
		$Parser->SetAddVariables(array(
			'sys_path'	=> $App->sysRoot.$App->filePath
		));
		return $Parser->MakeForm($di, 'cms/di_constructor/add/image', 'form');
	}

	function RenderAddListField()
	{
		GLOBAL $Parser;
		$di = Data::Create('di_constructor_add');
		return $Parser->MakeForm($di, 'cms/di_constructor/add/list', 'form');
	}

	function RenderAddPropListField()
	{
		GLOBAL $Parser;
		$di = Data::Create('di_constructor_add');
		return $Parser->MakeForm($di, 'cms/di_constructor/add/prop_list', 'form');
	}

	function RenderEditField($field)
	{
		GLOBAL $Parser, $App;
		$di = Data::Create('di_constructor_add');
		$fieldType = $this->di->fields[$field]->type;
		$di->value = array(
			'field_type'	=> $fieldType,
			'name'			=> $this->di->fields[$field]->name,
			'label'			=> $this->di->fields[$field]->label,
			'required'		=> ($this->di->fields[$field]->check != '') ? 1 : 0
		);

		switch ($fieldType)
		{
			case LiskType::TYPE_LIST:
				switch ($this->di->fields[$field]->objectType)
				{
					case 'arr':
						break;

					case 'def':
						$App->RaiseError('DIConstructor::RenderEditField() / def');
						break;

					case 'data':
						$App->RaiseError('DIConstructor::RenderEditField() / data');
						break;
				}
                $rezListArr = array();
				foreach ($this->di->fields[$field]->values as $key=>$val)
				{
					$rezListArr[] = $key.'[*]'.$val;
				}

				$rezList = implode('[|]', $rezListArr);
				$Parser->SetAddVariables(array(
					'rez_list'	=> $rezList
				));
				$tplName = 'edit_list';
				break;
				
			case LiskType::TYPE_IMAGE:
			    $img = $this->di->fields[$field];
				$di->value['path'] = $img->path;
				$thumbnails = $img->object['thumbnails'];
				$tplName = 'edit_image';
				//$renderedThumbs = 'no thumbnails';
				//if (Utils::IsArray($thumbnails))
				//{
				    $renderedThumbs = $Parser->MakeView(
				        array(
        					'big_w'		=> @$thumbnails[0]['width'],
        					'medium_w'	=> @$thumbnails[1]['width'],
        					'small_w'	=> @$thumbnails[2]['width'],
        					'big_h'		=> @$thumbnails[0]['height'],
        					'medium_h'	=> @$thumbnails[1]['height'],
        					'small_h'	=> @$thumbnails[2]['height'],
    				    ),
				        'cms/di_constructor/'.$tplName, 'thumbnails'
				    );
				//}
				
				$Parser->SetAddVariables(array('thumbnails'=>$renderedThumbs));
				break;
				
			case LiskType::TYPE_FILE:
				$di->value['path'] = $this->di->fields[$field]->path;
				$tplName = 'edit_file';
				break;

			default:
				$tplName = 'edit';
				break;
		}

		return $Parser->MakeForm($di, 'cms/di_constructor/'.$tplName, 'form');

	}

	function AddFieldSubmit($values)
	{
		GLOBAL $App,$Db;
		$type = $values['field_type'];
		$name = str_replace(' ', '_', trim($values['name']));
		$label= $values['label'];

		switch ($type)
		{
			case LiskType::TYPE_INPUT:
				$sql = "ALTER TABLE `[table]` ADD `[name]` VARCHAR( 255 ) NOT NULL";
				$this->configArray['fields'][$name] = array(
					'type'	=> $type,
					'label'	=> $label
				);
				break;
				
			case LiskType::TYPE_HTML:
			case LiskType::TYPE_TEXT:
			case LiskType::TYPE_PROP_LIST:
				$sql = "ALTER TABLE `[table]` ADD `[name]` TEXT NOT NULL";
				$this->configArray['fields'][$name] = array(
					'type'	=> $type,
					'label'	=> $label
				);
				break;
				
			case LiskType::TYPE_DATE:
				$sql = "ALTER TABLE `[table]` ADD `[name]` DATE NOT NULL";
				$this->configArray['fields'][$name] = array(
					'type'	=> $type,
					'label'	=> $label
				);
				break;
				
			case LiskType::TYPE_DATETIME:
				$sql = "ALTER TABLE `[table]` ADD `[name]` DATETIME NOT NULL";
				$this->configArray['fields'][$name] = array(
					'type'	=> $type,
					'label'	=> $label
				);
				break;
				
			case LiskType::TYPE_FILE:
				$path = Format::ToUrl($values['path']);
				if (file_exists($App->sysRoot.$App->filePath.$path))
				{
					$App->SetError("The folder ".$path." already exists. Please choose another one.");
					return false;
				}
				GLOBAL $FileSystem;
				$FileSystem->CreateDir($App->sysRoot.$App->filePath.$path);
				$sql = "ALTER TABLE `[table]` ADD `[name]` VARCHAR( 255 ) NOT NULL";
				$this->configArray['fields'][$name] = array(
					'type'	=> $type,
					'label'	=> $label,
					'path'	=> $path
				);
				break;
				
			case LiskType::TYPE_IMAGE:
				$path=Format::ToUrl($values['path']);

				if (file_exists($App->sysRoot.$App->filePath.$path))
				{
					$App->SetError("The folder ".$path." already exists. Please choose another one.");
					return false;
				}
				GLOBAL $FileSystem;
				$FileSystem->CreateDir($App->sysRoot.$App->filePath.$path);
				$sql = "ALTER TABLE `[table]` ADD `[name]` TEXT NOT NULL";

				//create thumbnail array
				$thumbnailsArr = array();
				$keys = array(0=>'big',1=>'medium',2=>'small');
				foreach ($keys as $keyId=>$key)
				{
					if ($values[$key.'_w']>0 && $values[$key.'_h']>0)
					{
						$thumbnailsArr[$keyId] = array(
							'name'		=> $key,
							'height'	=> $values[$key.'_h'],
							'width'		=> $values[$key.'_w'],
						);
					}
				}

				$this->configArray['fields'][$name] = array(
					'type'	=> $type,
					'label'	=> $label,
					'object'	=> array(
						'path'			=> $path,
						'no_image'		=> false,
						'thumbnails'	=> $thumbnailsArr
					)
				);
				break;
				
			case LiskType::TYPE_LIST:
				$sql = "ALTER TABLE `[table]` ADD `[name]` INT UNSIGNED NOT NULL";

				//create values array
				$arr = explode('[|]', $values['rezlist']);
				$values = array();
				foreach ($arr as $value)
				{
					list($eKey,$eValue) = explode('[*]', $value);
					$values[$eKey] = $eValue;
				}

				$this->configArray['fields'][$name] = array(
					'type'		=> $type,
					'label'		=> $label,
					'object'	=> $values

				);
				break;
				
			case 'hidden_integer':
				$sql = "ALTER TABLE `[table]` ADD `[name]` INT UNSIGNED NOT NULL";
				$this->configArray['fields'][$name] = array(
					'type'	=> 'hidden',
					'label'	=> $label
				);
				if (strlen($values['def_value']))
				{
					$this->configArray['fields'][$name]['def_value'] = $values['def_value'];
				}
				break;

			default:
				$App->RaiseError("DI Constructor::Add Field. Unknown field type $type");
				break;
		}
		$sql = Format::String($sql,array(
			'table'	=> $this->di->table,
			'name'	=> $name
		));
		$Db->Query($sql);
		$App->SaveDI($this->name, $this->configArray);
		
		return true;
	}

	function EditFieldSubmit($field, $values)
	{
		GLOBAL $App;
		$type 		= $this->di->fields[$field]->type;
		$label		= $values['label'];
		$required	= @$values['required_checked'];

		switch ($type)
		{
			case LiskType::TYPE_LIST:
				//create values array
				$arr = explode('[|]', $values['rezlist']);
				$newValues = array();
				foreach ($arr as $value)
				{
					list($eKey,$eValue) = explode('[*]',$value);
					$newValues[$eKey] = $eValue;
				}
				$this->configArray['fields'][$field]['object'] = $newValues;
				break;

			case LiskType::TYPE_IMAGE:

				//`if` used to prevent empty arrays
				if (@$values['big_h'] && @$values['big_w'])
				{
					$this->configArray['fields'][$field]['object']['thumbnails'][0]['name'] = 'big';
					$this->configArray['fields'][$field]['object']['thumbnails'][0]['height'] = $values['big_h'];
					$this->configArray['fields'][$field]['object']['thumbnails'][0]['width'] = $values['big_w'];
				}
				else
				{
					unset($this->configArray['fields'][$field]['object']['thumbnails'][0]);
				}

				if (@$values['medium_h'] && @$values['medium_w'])
				{
					$this->configArray['fields'][$field]['object']['thumbnails'][1]['name'] = 'medium';
					$this->configArray['fields'][$field]['object']['thumbnails'][1]['height'] = $values['medium_h'];
					$this->configArray['fields'][$field]['object']['thumbnails'][1]['width'] = $values['medium_w'];
				}
				else
				{
					unset($this->configArray['fields'][$field]['object']['thumbnails'][1]);
				}

				if (@$values['small_h'] && @$values['small_w'])
				{
					$this->configArray['fields'][$field]['object']['thumbnails'][2]['name'] = 'small';
					$this->configArray['fields'][$field]['object']['thumbnails'][2]['height'] = $values['small_h'];
					$this->configArray['fields'][$field]['object']['thumbnails'][2]['width'] = $values['small_w'];
				}
				else
				{
					unset($this->configArray['fields'][$field]['object']['thumbnails'][2]);
				}

				break;
		}

		// redefine desc arr from field=type to field = array
		if (!Utils::IsArray($this->configArray['fields'][$field]))
		{
			$this->configArray['fields'][$field] = array(
				'type'	=> $type
			);
		}

		$this->configArray['fields'][$field]['label'] = $label;
		if ($required == 1)
		{
			$this->configArray['fields'][$field]['check']='empty';
		}
		else
		{
			unset($this->configArray['fields'][$field]['check']);
		}

		$App->SaveDI($this->name, $this->configArray);
	}

	function DeleteField($name)
	{
		GLOBAL $App,$Db,$FileSystem;
		$sql="ALTER TABLE `[table]` DROP `[name]`";
		$sql = Format::String($sql,array(
			'table'	=> $this->di->table,
			'name'	=> $name
		));
		$Db->Query($sql);
    
		switch ($this->di->fields[$name]->type)
		{
			case LiskType::TYPE_FILE:
			case LiskType::TYPE_IMAGE:
				$FileSystem->DeleteDir($this->di->fields[$name]->path);
				break;
			default;
		}
		
		//remove field
		unset($this->configArray['fields'][$name]);
		
		
		//remove from listFields
		$listFields = explode(',', $this->listFields);
		foreach ($listFields as $key=>$field)
		{
			if ($name==$field) unset($listFields[$key]);
		}
		$this->configArray['list_fields'] = implode(',', $listFields);
		
		//remove from order
		$arr = explode(' ', $this->order);
		$orderField = $arr[0];
		if ($orderField==$name) $this->configArray['order'] = '';
		
		$App->SaveDI($this->name, $this->configArray);
	}

	function SetOrderField($field, $orderType='ASC')
	{
		GLOBAL $App;

		$order = $field.' '.$orderType;
		if ($field=='oder') $order = 'oder';

		$this->configArray['order'] = $order;
		$App->SaveDI($this->name, $this->configArray);
	}

	function SetListFields($listFields)
	{
		GLOBAL $App;
		$this->configArray['list_fields'] = $listFields;
		$App->SaveDI($this->name, $this->configArray);
	}

	function RenderDeveloperOnly()
	{
		GLOBAL $Parser;
		return $Parser->MakeView(array(
			'info_block' => $this->__RenderInfoBlock(),
		), 'cms/di_constructor/developer_only', 'view');
	}

	function RenderOverview() {
		GLOBAL $Parser;

		return $Parser->MakeView(array(
			'info_block'			=> $this->RenderInfoBlock(),
			'system_fields_block'	=> $this->RenderSystemFiledsBlock(),
			'public_fields_block'	=> $this->RenderPublicFiledsBlock(),
			'edit_order'			=> $this->RenderEditOrderForm(),
			'edit_list_fields'		=> $this->RenderEditListFieldsForm()
		),'cms/di_constructor/overview');
	}
	
	private function OrderStrToField()
	{
		$orderArr = explode(' ', $this->di->order);
		return $orderArr[0];
	}
	
	private function RenderEditOrderForm()
	{
		GLOBAL $Parser;
		// add id
		$rows[] = array(
			'name'		=> 'id',
			'label'		=> 'Record Id'
		);
		//add oder (manual)
		if (isset($this->di->fields['oder']))
		{
			$rows[] = array(
				'name'	=> 'oder',
				'label'	=> 'Manual'
			);
		}
		foreach ($this->publicFields as $name=>$obj)
		{
			$rows[] = array(
				'name'	=> $name,
				'label'	=> $obj->label
			);
		}
		
		$orderArr = explode(' ', $this->di->order);
		
		$orderType = 'ASC';
		$orderFieldName = $orderArr[0];
		
		foreach ($orderArr as $key=>$one)
		{
			foreach (array_keys($this->orderType) as $oType)
			{
				if (strtolower($one)==strtolower($oType))
				{
					$orderType = $oType;
					$orderFieldName = $orderArr[$key-1];
				}
			}
		}
		$captionOrder = array(
			'asc_selected'  => $orderType == 'ASC' ? 'checked' : '',
			'desc_selected' => $orderType == 'DESC' ? 'checked' : '',
		);
		$Parser->SetCaptionVariables($captionOrder);
		
		foreach($rows as $key=>$row)
		{
			if ($row['name'] == $orderFieldName) $rows[$key]['selected'] = 'checked="checked"';
		}
		return $Parser->MakeList($rows, 'cms/di_constructor/edit_order_form');
	}

	private function RenderEditListFieldsForm()
	{
		GLOBAL $Parser;

		$listFields = explode(',', $this->listFields);
		
		$available = '';
		$listBox = '';
		foreach ($this->publicFields as $name=>$obj)
		{
			if (!in_array($name, $listFields))
			{
				$available .= '<option value="'.$name.'">'.$obj->label.'</option>';
			}
			else
			{
				$listBox .= '<option value="'.$name.'">'.$obj->label.'</option>';
			}
		}

		return $Parser->MakeView(array(
			'available'	=> $available,
			'list_box'	=> $listBox
		), 'cms/di_constructor/edit_listfields_form');
	}

	private function RenderInfoBlock()
	{
		GLOBAL $Parser;
		$listfields = explode(',', $this->listFields);
		$rez = array();
		if (Utils::IsArray($listfields))
		{
    		foreach ($listfields as $field)
    		{
    			if (isset($this->di->fields[$field])) $rez[] = $this->di->fields[$field]->label;
    		}
		}
		$rezlist = implode(',', $rez);
		
		// order string
		$arr = explode(' ', $this->order);
		$orderField = $arr[0];
		$orderType = 'ASC';
		if (count($arr)==2) $orderType = $arr[1];
		
		if ($this->OrderStrToField($this->order) == 'oder') $orderStr = 'Manual';
		else $orderStr = $this->di->fields[$orderField]->label.' '.$orderType;
		
		
		return $Parser->MakeView(array(
			'sys_name'		=> $this->name,
			'table'			=> $this->di->table,
			'list_fields'	=> $rezlist,
			'order'			=> $orderStr,
			'label'			=> $this->di->label
		), 'cms/di_constructor/info');
	}

	function RenderSystemFiledsBlock()
	{
		GLOBAL $Parser,$Page;
		foreach ($this->systemFields as $name=>$obj)
		{
			$rows[] = array(
				'name'		=> $name,
				'type'		=> $obj->type,
				'details'	=> ''
			);
		}
		$Parser->SetCaptionVariables(array(
			'back'	=> $Page->setBack
		));
		return $Parser->MakeList($rows, 'cms/di_constructor/system_fields_list');
	}

	function RenderPublicFiledsBlock()
	{
		GLOBAL $Parser,$Page;
		$rows = array();
		foreach ($this->publicFields as $name=>$obj)
		{
			$rows[] = array(
				'name'		=> $name,
				'type'		=> $obj->type,
				'details'	=> '',
				'label'		=> $obj->label,
				'back'		=> $Page->setBack
			);
		}
		$Parser->SetCaptionVariables(array(
			'back'	=> $Page->setBack
		));
		return $Parser->MakeList($rows, 'cms/di_constructor/public_fields_list');
	}

}

?>