<?php

/**
 * parse view handler
 *
 * @param mixed $obj
 * @param string $blockName
 * @param Template $tpl
 * @param string $mode
 * @return string
 */
function ParserViewHandler($obj, $blockName, Template $tpl, $mode)
{
	GLOBAL $Parser;
		
	$objType = (is_object($obj)) ? 'DataItem' : 'array';
	
	if ($objType == 'DataItem') $values = (Utils::IsArray($obj->value)) ? $obj->value : $obj->values[0];
	else $values = $obj;
	
	// get add variables
	$values = Utils::MergeArrays($values, $Parser->GetAddVariables());
	
	// get caption variable
	$values = Utils::MergeArrays($values, $Parser->GetCaptionVariables());
	
	if ($objType == 'DataItem')
    {
        $parsedFields = array();
        $tpl->SetCurrentBlock($blockName);
        //parse di fields
        foreach ($obj->fields as $key=>$field)
        {
            if (isset($values[$key])) $parsedFields[$key] = true;
            
            $field->value = isset($values[$key]) ? $values[$key] : null;
            $tpl->SetVariable($key, $field);
        }
        
        //parse add variables
        if (Utils::IsArray($values))
        {
            foreach ($values as $key => $value)
        	{
        	    if (!isset($parsedFields[$key])) $tpl->SetVariable($key, $value);
        	}
        }
        $tpl->ParseCurrentBlock();
    }
    else
    {
        if (Utils::IsArray($values))
        {
            $tpl->SetCurrentBlock($blockName);
            foreach ($values as $key => $value)
        	{
        	    $tpl->SetVariable($key, $value);
        	}
        	$tpl->ParseCurrentBlock();
        }
    }

	// remove caption & add variables
	$Parser->ClearAddVariables();
	
	if ($objType == 'DataItem') $obj->ClearFieldsValues();
	
	if ($mode == 'make') return $tpl->Get();
	else return '';
}
?>