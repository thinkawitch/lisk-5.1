<?php

/**
* @param mixed $obj
* @param string $blockName
* @param Template $tpl
* @param string mode
* @return string
*/

function ParserDynamicViewHandler($obj, $blockName, Template $tpl, $mode)
{
	GLOBAL $Parser;
	$Parser->isDynamic = true;
	
	$objType = (is_object($obj)) ? 'DataItem' : 'Rows';
	$values = ($objType == 'DataItem') ? $obj->value : $obj;

	$row_name = $blockName.'_row';
	
	// get add variables
	$values = Utils::MergeArrays($values, $Parser->GetAddVariables());

	// parse view row
	$tpl->SetCurrentBlock($row_name);
	
	// get values[0] if ROWS and not ROW
	if (isset($values[0]) && Utils::IsArray($values[0])) $values = $values[0];
	
	foreach ($values as $key => $value)
	{
		if (isset($obj->fields[$key]) && is_object($obj->fields[$key]))
		{
			// skip hidden fields
			if ($obj instanceof T_hidden) continue;
				
			$obj->fields[$key]->value = $value;
			$value = $obj->fields[$key];
			$caption = $obj->fields[$key]->label;
		}
		else
		{
			$caption = Format::Label($key);
		}

		$tpl->SetVariable(array(
			'CAPTION' => $caption,
			'FIELD'   => $value
		));
		$tpl->ParseCurrentBlock();
	}

	// parse view
	$tpl->SetCurrentBlock($blockName);
	$tpl->SetVariable($Parser->GetCaptionVariables());
	$tpl->ParseCurrentBlock();

	// remove fields info
	$Parser->ClearAddVariables();
	
	$Parser->isDynamic = false;
	
	if ($objType=='DataItem') $obj->ClearFieldsValues();
	
	if ($mode == 'make') return $tpl->Get();
	else return '';
	
}

?>