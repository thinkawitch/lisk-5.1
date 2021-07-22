<?php

/**
* @param mixed $obj
* @param string $blockName
* @param Template $tpl
* @param string mode
* @return string
*/
function ParserListHandler($obj, $blockName, Template $tpl, $mode)
{
	GLOBAL $Parser;

	// setup template defenitions
	$list_name = $blockName;
	$row_name = $blockName.'_row';
	$empty_name = $blockName.'_empty';
	$separator_name = $blockName.'_separator';
	$decorationVariable = 'decoration';

	$add = $Parser->GetAddVariables();

	$objType = (is_object($obj)) ? 'DataItem' : 'Rows';
	$values = ($objType == 'DataItem') ? $obj->values : $obj;

	if (Utils::IsArray($values))
	{
		$list_size = sizeof($values);
		$i = 1;
		foreach ($values as $row)
		{
			// make separator
			if ($i<$list_size) $tpl->TouchBlock($separator_name);

			// set _row block
			$tpl->SetCurrentBlock($row_name);
			
			// add $Parser->add_variables
			$row = Utils::MergeArrays($row, $add);

			foreach ($row as $key=>$value)
			{
				// make decoration
				if ($Parser->listDecoration1!='' || $Parser->listDecoration2!='')
				{
					if ($i%2 == 1) $tpl->SetVariable($decorationVariable, $Parser->listDecoration1);
					else $tpl->SetVariable($decorationVariable, $Parser->listDecoration2);
				}
				
				switch ($objType)
				{
					case 'Rows':
						$tpl->SetVariable($key,$value);
						break;
						
					case 'DataItem':
						// preobrazovanie
						if (isset($obj->fields[$key]) && is_object($obj->fields[$key]))
						{
							$obj->fields[$key]->value=$value;
							$value = $obj->fields[$key];
						}
						$tpl->SetVariable($key, $value);
						break;
				}
			}
			$tpl->ParseCurrentBlock();
			$i++;
		}
	}
	else
	{
		$tpl->TouchBlock($empty_name);
	}

	$tpl->SetCurrentBlock($list_name);
	$tpl->SetVariable($Parser->GetCaptionVariables());
	$tpl->ParseCurrentBlock();
	
	// remove caption & add variables
	$Parser->ClearAddVariables();
	$Parser->ClearListDecoration();
	
	if ($objType == 'DataItem') $obj->ClearFieldsValues();

	if ($mode == 'make') return $tpl->Get();
	else return '';
}
?>