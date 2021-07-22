<?php

/******************************* System block function **************************************/

/**
 * Render SS menu (without sub levels)
 * arr. params:
 * block_name - tpl block name in scms_blocks.htm
 * parent - parentID or parent name i.e. 'products/'
 * example: <lisk:snippet name="ss_menu" parent="products/" block_name="menu" />
 *
 * @param array $params
 * @return html
 */
function ss_menu($params=null)
{
	GLOBAL $Scms;
	$cl = $params['parent'];
	$blockName = $params['block_name'];
	$blockName = strtolower($blockName);
	return $Scms->RenderMenu($cl, $blockName);
}

/**
 * Render full menu (with sub levels)
 * arr. params:
 * tpl_name - tpl file name
 * start_level - level the menu starts from
 * example: <lisk:snippet name="ss_full_menu" start_level="2" tpl_name="ss/blocks/full_menu" />
 *
 * @param array $params
 * @return html
 */
function ss_full_menu($params=null)
{
	GLOBAL $Scms;
	$tplName = strtolower($params['tpl_name']);
	$startLevel = (int)$params['start_level'];

	return $Scms->RenderFullMenu($tplName, $startLevel);
}


//<lisk:snippet name="ss_navigation" />
function ss_navigation($params=null)
{
	GLOBAL $Scms;
	return $Scms->RenderNavigation();
}

//<lisk:snippet name="ss_name" />
function ss_name($params=null)
{
	GLOBAL $Scms;
	return $Scms->values['name'];
}

//<lisk:snippet name="ss_parent_name" />
function ss_parent_name($params=null)
{
	GLOBAL $Scms;
	//return $Scms->values['name'];
	return $Scms->GetParentName();
}

function appname($params=null)
{
	GLOBAL $App;
	return $App->appName;
}

function back($params=null)
{
	return Navigation::Back();
}

// <lisk:snippet name="get_block" tpl="tpl_path/tpl_name" block="block_name" />
// param 'tpl' is optional
function get_block($params)
{
    GLOBAL $Parser;
    return $Parser->GetHTML($params['tpl'] ? $params['tpl'] : 'blocks', $params['block']);
}

?>