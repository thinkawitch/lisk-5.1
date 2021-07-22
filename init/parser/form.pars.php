<?php

/**
 * Parser Form Handler
 *
 * @param Data $dataItem
 * @param string $blockName
 * @param Template $tpl
 * @param string $mode [make|parse]
 * @return string
 */
function ParserFormHandler(Data $dataItem, $blockName, Template $tpl, $mode)
{
	GLOBAL $Parser;
	$tpl->SetCurrentBlock($blockName);
	
	foreach ($dataItem->fields as $key => $obj)
	{
		if (isset($dataItem->value[$key])) $obj->value = $dataItem->value[$key];
		$tpl->SetVariable($key, $obj);
	}
	
	// Set Add Variables
	foreach ($Parser->GetAddVariables() as $key=>$value)
	{
		$tpl->SetVariable($key, $value);
	}
	
	// Set Caption Variables
	foreach ($Parser->GetCaptionVariables() as $key=>$value)
	{
		$tpl->SetVariable($key, $value);
	}
	
	// insert check variable
	$tpl->SetVariable(array(
		'JS_CHECK' => $dataItem->checkString,
	    'CHECK_FORM' => 'onSubmit="return CheckForm(this,'.$dataItem->checkString.')"'
	));
	
	$tpl->ParseCurrentBlock();
	
	// remove caption & add variables
	$Parser->ClearAddVariables();
	
	$dataItem->ClearFieldsValues();
	
	if ($mode == 'make') return $tpl->Get();
	else return '';
}

?>