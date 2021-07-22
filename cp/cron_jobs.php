<?php
require_once('init/init.php');

class CpCronJobsPage extends CPPage
{
    private $selfUri = 'cron_jobs.php?a=b';

	function __construct()
	{
		parent::__construct();
		$this->App->Load('cron_job', 'obj');
		
		$this->SetGetAction('enable', 'EnableJob');
		$this->SetGetAction('disable', 'DisableJob');
	}

	function Page()
	{
		$this->title = 'Cron Jobs';
		
		$list = new CMSList('cron_jobs');
		$list->Load('cron_job', 'obj');
		$list->Init();
		$list->AlphabeticNavigation = true;
		$list->AlphabeticField = 'name';
		$list->buttonCheckbox = $list->buttonDeleteAll = false;
		$list->RemoveButton('Delete', '[id]==1 || [id]==2');
		$list->RemoveButton('Edit', '[id]==1 || [id]==2');
		$list->RemoveButton('View', '[id]==1 || [id]==2');
		
		$list->AddButton('Enable', $this->selfUri.'&action=enable&id=[id]');
		$list->RemoveButton('Enable', '[status]=='.CronJob::STATUS_ENABLED);
		
		$list->AddButton('Disable', $this->selfUri.'&action=disable&id=[id]');
		$list->RemoveButton('Disable', '[status]=='.CronJob::STATUS_DISABLED);
		
		$list->MakeLinkButtons();
		
		$this->SetBack();
		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $list->Render();
	}
	
	function EnableJob()
	{
	    GLOBAL $Db;
	    $id = intval($_GET['id']);
	    
	    $Db->Update('id='.$id, array('status' => CronJob::STATUS_ENABLED), 'sys_cron_jobs');
	    
	    Navigation::Jump($this->selfUri);
	}
    
    function DisableJob()
	{
	    GLOBAL $Db;
	    $id = intval($_GET['id']);
	    
	    $Db->Update('id='.$id, array('status' => CronJob::STATUS_DISABLED), 'sys_cron_jobs');
	    
	    Navigation::Jump($this->selfUri);
	}

}

$Page = new CpCronJobsPage();
$Page->Render();
?>