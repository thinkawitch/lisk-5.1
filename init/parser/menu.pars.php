<?php

function ParserMenuHandler(Data $Node, $cl, $startLevel, $tplName)
{
	GLOBAL $Parser;

	$parents = $Node->getValue('id='.$cl, 'parents');
	if ($parents == '') $parents = '<1>';

	// Select all related categories
	$selectCond = Utils::TreeToIn($parents);
	$categories = $Node->SelectValues("parent_id IN $selectCond", 'id,parent_id,parents,name,url');

	// get parents array
	$parentsArr = Utils::TreeToArray($parents);

	//render header
	$GLOBALS['full_menu_result'] = $Parser->GetHtml($tplName, 'header');

	//render menu
	if (Utils::IsArray($categories)) ParserMenuBuilder($categories, $parentsArr, $cl, $startLevel, $tplName);

	//render footer
	$GLOBALS['full_menu_result'] .= $Parser->GetHtml($tplName, 'footer');

	// remove global var
	$rez = $GLOBALS['full_menu_result'];
	unset($GLOBALS['full_menu_result']);

	return $rez;
}

function ParserMenuBuilder(array $categories, $parentsArr, $cl, $level, $tplName)
{
	GLOBAL $Parser;

	foreach ($categories as $row)
	{
		if (Utils::TreeLevel($row['parents'])==$level)
		{
			$blockName = ($row['id'] == $parentsArr[$level] || $row['id'] == $cl) ? 'level'.$level.'_active' : 'level'.$level;
			$GLOBALS['full_menu_result'] .= $Parser->MakeView(array(
				'name'	=> $row['name'],
				'url'	=> $row['url']
			), $tplName, $blockName);

			if ($row['id'] == $parentsArr[$level])
			{
				ParserMenuBuilder($categories, $parentsArr, $cl, $level+1, $tplName);
			}
		}
	}
}


?>