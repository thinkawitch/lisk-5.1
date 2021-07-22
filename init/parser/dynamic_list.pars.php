<?php

/**
* @param mixed $obj
* @param string $blockName
* @param Template $tpl
* @param string $mode
* @return string
*/
function ParserDynamicListHandler($obj, $blockName, Template $tpl, $mode)
{
	GLOBAL $Parser;
	$Parser->isDynamic = true;

	$listName = $blockName;
	$rowName = $blockName.'_row';
	$elementName = $blockName.'_element';
	$captionRowName = $blockName.'_caption_row';
	$captionElementName = $blockName.'_caption_element';
	$decorationVariable = 'decoration';

	$add = $Parser->GetAddVariables();

	$objType = (is_object($obj)) ? 'DataItem' : 'Rows';
	$values = ($objType == 'DataItem') ? $obj->values: $obj;

	// make captions line
	if (Utils::IsArray($Parser->dynamicListColumns))
	{
		foreach ($Parser->dynamicListColumns as $key=>$value)
		{
			if (Utils::IsArray($value))
			{
				foreach ($value as $key2=>$value2)
				{
					$cap_arr[$key2] = $value2;
				}
				$cap_arr['ELEMENT_NAME'] = $key;
			}
			else
			{
				$cap_arr = array(
					'ELEMENT'		=> $value,
					'ELEMENT_NAME'	=> $key
				);
			}
			$tpl->ParseVariable($cap_arr, $captionElementName);
		}

		$tpl->SetCurrentBlock($captionRowName);
		$tpl->ParseCurrentBlock();
	}
	
	switch ($objType)
	{
		case 'DataItem':
			$i = 1;
			foreach ($values as $row)
			{
				foreach ($row as $key=>$value)
				{
					// make decoration
					if($Parser->listDecoration1 != '' || $Parser->listDecoration2 != '')
					{
						if ($i%2 == 1)
						{
							$tpl->SetVariable($decorationVariable, $Parser->listDecoration1);
						}
						else
						{
							$tpl->SetVariable($decorationVariable, $Parser->listDecoration2);
						}
					}
					
					// preobrazovanie
					if (isset($obj->fields[$key]) && is_object($obj->fields[$key]))
					{
						// DataItem field
						if (!is_a($obj->fields[$key], 'T_hidden') && $key != 'id')
						{
							$obj->fields[$key]->value = $value;
							$value = $obj->fields[$key];
							$insert_array = array(
								'ELEMENT'	=> $value,
								//'ID'		=> $row['id']
							);
							$tpl->ParseVariable($insert_array, $elementName);
						}
					}
					else
					{
						// Simple value
						if ($key != 'id')
						{
							$tpl->ParseVariable(array('ELEMENT'	=> $value), $elementName);
						}
					}
				}
				$row = Utils::MergeArrays($row, $add);
				// make decoration
				if ($Parser->listDecoration1 != '' || $Parser->listDecoration2 != '')
				{
					if ($i%2 == 1)
					{
						$row[$decorationVariable] = $Parser->listDecoration1;
					}
					else
					{
						$row[$decorationVariable] = $Parser->listDecoration2;
					}
				}
				$tpl->ParseVariable($row, $rowName);
				$i++;
			}
			break;
			
		case 'Rows':
			$i=1;
			foreach ($values as $row)
			{
				foreach ($row as $key=>$value)
				{
					// make decoration
					if ($Parser->listDecoration1 != '' || $Parser->listDecoration2 != '')
					{
						if ($i%2 == 1)
						{
							$tpl->SetVariable($decorationVariable, $Parser->listDecoration1);
						}
						else
						{
							$tpl->SetVariable($decorationVariable, $Parser->listDecoration2);
						}
					}
					$tpl->ParseVariable(array('ELEMENT'	=> $value), $elementName);
				}
				$row = Utils::MergeArrays($row, $add);
				$tpl->ParseVariable($row, $rowName);
			    $i++;
			}
			break;
	}


	$tpl->SetCurrentBlock($listName);
	$tpl->SetVariable($Parser->captionVariables);

	$tpl->ParseCurrentBlock();

	$Parser->ClearAddVariables();
	$Parser->ClearListDecoration();
	
	$Parser->isDynamic = false;
	
	if ($objType=='DataItem') $obj->ClearFieldsValues();
	
	if ($mode == 'make')
	{
		return $tpl->Get();
	}
}
?>