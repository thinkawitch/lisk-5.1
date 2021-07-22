<?php

function stat_visit()
{
	GLOBAL $App;

	$App->Load('stat_visit', 'mod');

	$StatVisit = new StatVisit();
	$StatVisit->InitVisit();

	if ($StatVisit->visitId == null)
	{
		// First Time Visit
		return $StatVisit->GetJSString();
	}
	else
	{
		$StatVisit->StatPage();
	}
	
	return '';
}

?>