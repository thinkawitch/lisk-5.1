<?php
require_once 'init/init.php';

class CpSystemFooterPage extends CPPage
{
    private $idGoogleAnalytics = 1;
    
    function __construct()
    {
        parent::__construct();
    }
    
    function Page()
    {
        $this->SetTitle('System Footer Blocks', 'cms/list/uho.gif');
        $this->SetBack();
        
        $list = new CMSList('system_footer');
        $list->Init();
        $list->buttonCheckbox = false;
        $list->buttonDeleteAll = false;
        $list->RemoveButton('Delete', '[id]=='.$this->idGoogleAnalytics);
        
        $list->MakeLinkButtons();
        
        $this->Paging->SwitchOn('cp');
        
        if ($this->setBack > 0) $this->ParseBack();
        
        $this->pageContent .= $list->Render();
    }
}

$Page = new CpSystemFooterPage();
$Page->Render();
?>