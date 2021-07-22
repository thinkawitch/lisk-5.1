<?php

/**
 * Add prefix for every key of an array
 *
 * @param array $arr
 * @param string $prefix
 * @return array
 */
function RtbrAddPrefix(Array $arr, $prefix)
{
	$new = array();
	if (Utils::IsArray($arr))
	{
		foreach ($arr as $k=>$v)
		{
			if (is_array($v)) $new[$prefix.$k] = RtbrAddPrefix($v, $prefix);
			else  $new[$prefix.$k] = $v;
		}
	}
	return $new;
}

$GLOBALS['DATA_PTBR_EMPTY'] = array(
    'fields' => array(
        'foo' => LiskType::TYPE_HIDDEN
    )
);

/**
 * Render Table By Rows, for complicated tables
 *
 * @param Data $di
 * @param integer $cols
 * @param string $tplName
 * @param string $blockName
 */

function RenderTableByRows(Data $di, $cols, $tplName, $blockName)
{
    GLOBAL $Parser;
	STATIC $p;
	if (!$p) $p = new Parser();
	
	$captionVars = $Parser->captionVariables;
	//rows for final list to render
	$rows = array();
	
	if (!Utils::IsArray($di->values))
	{
	    $rows[]['_row'] = $p->GetHtml($tplName, $blockName.'_additional_empty');
	    $Parser->SetCaptionVariables($captionVars);
	    return $p->MakeList($rows, $tplName, $blockName);
	}
	
    //total records
    $qty = count($di->values);
	//full rows
	$fullRows = floor($qty/$cols);
	//additional row, if any
	$incompleteRow = $qty - $fullRows*$cols;

	$rowNum = 0;
	//make all full rows
	$len = $fullRows*$cols;
	for ($i=0; $i<$len; $i+=$cols)
	{
		$arr = array();
		$columnNum = 1;
		while ($columnNum<=$cols)
		{
			$arr = Utils::MergeArrays($arr, RtbrAddPrefix($di->values[$rowNum], 'c'.$columnNum.'_'));
			$columnNum++;
			$rowNum++;
		}
		
		$bufDI = Data::Create('ptbr_empty');
		
		foreach ($di->fields as $name=>$object)
		{
			for ($element=1; $element<=$cols; $element++)
			{
				$bufDI->fields['c'.$element.'_'.$name] = clone $object;
			}
		}

		$bufDI->value = $arr;
		$rows[]['_row'] = $p->MakeView($bufDI, $tplName, $blockName.'_additional_'.$cols);
	}


	//and last additional row if any
	if ($incompleteRow > 0)
	{
		$arr = array();
		$columnNum = 1;
		while ($columnNum <= $incompleteRow)
		{
			$arr = Utils::MergeArrays($arr, RtbrAddPrefix($di->values[$rowNum], 'c'.$columnNum.'_'));
			$columnNum++;
			$rowNum++;
		}
		
		$bufDI = Data::Create('ptbr_empty');

		foreach ($di->fields as $name=>$object)
		{
			for ($element=1; $element<=$incompleteRow; $element++)
			{
				$bufDI->fields['c'.$element.'_'.$name] = clone $object;
			}
		}

		$bufDI->value = $arr;
		$rows[]['_row'] = $p->MakeView($bufDI, $tplName, $blockName.'_additional_'.$incompleteRow);
	}
	
	$Parser->SetCaptionVariables($captionVars); //Global parser! not local
	return $p->MakeList($rows, $tplName, $blockName);
}

?>