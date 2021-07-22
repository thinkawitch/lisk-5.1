<?php
/**
 * CMS View
 * @package lisk
 *
 */

class CMSView extends CMSCore
{
	/**
	 * data item
	 *
	 * @var Data
	 */
	public $dataItem;
	/**
	 * @var string $cond
	 */
	public $cond;

	function __construct($dataItem)
	{
		parent::__construct();
		
		GLOBAL $App;
		$App->Load('cpmodules', 'lang');
		
		if (!($dataItem instanceof Data)) $dataItem = Data::Create($dataItem);
		$this->dataItem = $dataItem;
	}


	function Render()
	{
		GLOBAL $Parser, $App;
		
		$this->dataItem->Get($this->cond);
		
		//lets replace objects, to render image preview with border as in edit mode
		foreach ($this->dataItem->fields as $field=>$obj)
		{
			if ($obj instanceof T_image)
			{
				$info = array(
					'name' => $obj->name,
					'label' => $obj->label,
				);
				$App->Load('input', 'type');
				$newObj = new T_input($info, $this->dataItem);
				$this->dataItem->fields[$field] = $newObj;
				$obj->value = $this->dataItem->value[$field];

				$img = $obj->Render('system');
				if ($img)
				{
					$zoom = $obj->Render('ZOOM', 'ORIGINAL');
					$arr = array(
						'img' => $img,
						'zoom' => $zoom,
					);
					$this->dataItem->value[$field] = $Parser->MakeView($arr, 'system/type/image', 'view');
				}
				else
				{
					$this->dataItem->value[$field] = '';
				}
			}
		}
		
		return $Parser->MakeDynamicView($this->dataItem, 'cms/view');
	}
}

?>