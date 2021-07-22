<?php
require_once 'init/init.php';

class CpVsWrapperPage extends CPPage
{
    function __construct()
    {
        parent::__construct();
    }
    
    function Page()
    {
        $this->SetTitle('Visits Statistics', 'cms/list/uho.gif');
        $this->SetBack();
        
        $row = $this->Db->Get('name="stat_visit"', 'id,name', 'sys_modules');
        
        if (Utils::IsArray($row))
        {
            Navigation::Jump('module_stat_visit.php');
        }
        else
        {
            $this->pageContent .= '<p><strong>Module visits statistics is not installed</strong></p>';
        }
    }
}

$Page = new CpVsWrapperPage();
$Page->Render();
?>