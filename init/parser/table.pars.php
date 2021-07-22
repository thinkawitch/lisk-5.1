<?php

/**
 * Table render handler
 *
 * @param mixed $obj
 * @param integer $cols
 * @param string $blockName
 * @param Template $tpl
 * @param string $mode
 * @return string
 */
function ParserTableHandler($obj, $cols, $blockName='table', Template $tpl, $mode)
{
	GLOBAL $Parser;

	$objType = (is_object($obj)) ? 'DataItem' : 'Rows';
	$arr = ($objType == 'DataItem') ? $obj->values : $obj;

	$table_name = $blockName;
	$column_separator_left_name = $blockName.'_column_separator_left';
	$column_separator_right_name = $blockName.'_column_separator_right';
	$column_separator_name = $blockName.'_column_separator';
	$column_name = $blockName.'_column';
	$row_separator_name = $blockName.'_row_separator';
	$row_name = $blockName.'_row';
	$empty_name = $blockName.'_empty';
	$empty_separator_name = $blockName.'_empty_separator';


	$size = sizeof($arr); // size of array
	$rows = round($size/$cols); // number of rows
	if ($rows*$cols < $size) $rows++;

	$i = 0; // current element
	$c = 0; // current column
	$r = 0; // current row

	$add = $Parser->GetAddVariables(); // get add variables

	if (Utils::IsArray($arr)) foreach ($arr as $row)
	{

		// new row begining
		if ($c == 0)
		{
			$tpl->touchBlock($column_separator_left_name);
			$tpl->setCurrentBlock($column_name);
			$tpl->parseCurrentBlock();
		}

		// make column separator
//		if ($c != 0) {
//			$tpl->touchBlock($column_separator_name);
//			$tpl->setCurrentBlock($column_name);
//			$tpl->parseCurrentBlock();
//		}

		// parse current column variables
		$tpl->setCurrentBlock($column_name);
		$row = Utils::MergeArrays($row, $add);
		
		if ($c != 0)
		{
			$tpl->touchBlock($column_separator_name);
		}

		foreach ($row as $key=>$value)
		{
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

		$c++; // column parsed - new column

		// row ending
		if ($c == $cols)
		{
			$tpl->TouchBlock($column_separator_right_name);
			$tpl->SetCurrentBlock($column_name);
			$tpl->ParseCurrentBlock();
		}

		// row separator processing
		if (++$i%$cols == 0)
		{
			if (sizeof($arr) - $i > 0)
			{
				$tpl->TouchBlock($row_separator_name);
			}
			$tpl->SetCurrentBlock($row_name);
			$tpl->ParseCurrentBlock();
			$c = 0;
			$r++;
		}
	}
	else
	{
	    //$tpl->touchBlock($empty_name);
	}

	// empty columns parsing
	if ($i%$cols != 0)
	{
		while ($i++%$cols != 0)
		{
			// empty column separator
			//if ($c != 0) $tpl->touchBlock($empty_separator_name);

			// empty column
			$tpl->TouchBlock($empty_name);

			$tpl->SetCurrentBlock($column_name);
			$tpl->ParseCurrentBlock();
			// new column
			$c++;
		}

		// table column separator right
		$tpl->TouchBlock($column_separator_right_name);

		// make row
		$tpl->SetCurrentBlock($row_name);
		$tpl->ParseCurrentBlock();
	}

	// parse main block table & add caption variables
	$tpl->SetCurrentBlock($table_name);
	$tpl->SetVariable($Parser->GetCaptionVariables());
	$tpl->ParseCurrentBlock();

	// remove caption & add variables
	$Parser->ClearAddVariables();
	
	if ($objType == 'DataItem') $obj->ClearFieldsValues();

	if ($mode == 'make') return $tpl->Get();
	else return '';
}

?>