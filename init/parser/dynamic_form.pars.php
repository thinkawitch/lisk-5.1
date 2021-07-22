<?php

/**
 * Generate Dynamic Form
 *
 * @param Data $dataItem
 * @param string $blockName template block name
 * @param Template $tpl
 * @param string $mode [parse|make]
 * @return string
 */
function ParserDynamicFormHandler(Data $dataItem, $blockName='dynamic_form', Template $tpl, $mode)
{
	GLOBAL $Parser;
	$Parser->isDynamic = true;
	
	$hidden_row_name = $blockName.'_hidden';
	$row_name = $blockName.'_row';
	$separator_name = $blockName.'_separator';

	// set values
	$values = $dataItem->value;
	
	$i=1;
	$listSize = 0;
	foreach ($dataItem->fields as $key=>$obj)
	{
		if (!($obj instanceof T_hidden)) $listSize++;
	}
	
	// parse visible fields
	foreach ($dataItem->fields as $key=>$obj)
	{
		// make separator
		if ($i < $listSize && !($obj instanceof T_hidden))
		{
			$i++;
			$tpl->TouchBlock($separator_name);
		}
						
		// set value, only if not empty, otherwise object default is used
		if (isset($values[$key])) $obj->value = $values[$key];
		
		if (!($obj instanceof T_hidden))
		{
			$tpl->SetCurrentBlock($row_name);
			$tpl->SetVariable(array(
				'CAPTION'	=> $obj->label,
				'REQUIRED'  => ($obj->isRequired) ? $Parser->formRequiredMarker : '',
				'FIELD'		=> $obj,
				'HINT'		=> $obj->hint
			));
			$tpl->ParseCurrentBlock();
			
			if ($obj instanceof T_password)
			{
				$tpl->touchBlock($separator_name);
				
				$name = $obj->name;
				$label = $obj->label;
				
				$obj->name = $name.'_confirmation';
				$obj->label = $label.' Confirmation';
				
				$tpl->SetVariable(array(
					'CAPTION'	=> $obj->label,
					'REQUIRED'  => ($obj->isRequired) ? $Parser->formRequiredMarker : '',
					'FIELD'		=> $obj
				));
				$tpl->ParseCurrentBlock();
			}
		}
	}
	
	// parse hidden fields
	$tpl->SetCurrentBlock($hidden_row_name);
	foreach ($dataItem->fields as $key=>$obj)
	{
		if ($obj instanceof T_hidden)
		{
			if (isset($values[$key])) $obj->value = $values[$key];
			
			$tpl->SetVariable(array(
				'HIDDEN' => $obj
			));
			$tpl->ParseCurrentBlock();
		}
	}

	// parse form
	$tpl->SetCurrentBlock($blockName);
	$tpl->SetVariable(array(
		'JS_CHECK'   => $dataItem->checkString,
    	'CHECK_FORM' => 'onSubmit="return CheckForm(this,'.$dataItem->checkString.')"'
	));
	$tpl->SetVariable($Parser->captionVariables);
	$tpl->ParseCurrentBlock();

	$Parser->isDynamic = false;
	
	$dataItem->ClearFieldsValues();
	
	if ($mode=='make') return $tpl->Get();
	else return '';
}
?>