<?php
require_once 'init/init.php';

class CpAsWrapperPage extends CPPage
{
    function __construct()
    {
        parent::__construct();
    }
    
    function Page()
    {
        $this->SetTitle('Action Statistics', 'cms/list/uho.gif');
        $this->SetBack();
        
        if (StatActionHandler::IsInstalled())
        {
            Navigation::Jump('module_stat_action.php');
        }
        else
        {
            $this->pageContent .= '<p><strong>Module action statistics is not installed</strong></p>';
        }
    }
}

$Page = new CpAsWrapperPage();
$Page->Render();
?>