<?php
require_once 'init/init.php';

class CpGaPage extends CPPage
{
    private $idGoogleAnalytics = 1;
    
    function __construct()
    {
        parent::__construct();
        
        $this->SetPostAction('submit', 'SubmitGa');
    }
    
    function Page()
    {
        $this->SetTitle('Google Analytics', 'cms/list/uho.gif');
        $this->SetBack();
        
        $caption = array();
        $di = Data::Create('system_footer');
        $di->Get('id='.$this->idGoogleAnalytics);
        
        if ($this->IsInstalled($di->value['content'])) $caption['status'] = $this->Parser->GetHtml('google_analytics', 'installed');
        else $caption['status'] = $this->Parser->GetHtml('google_analytics', 'not_installed');
        
        if ($this->setBack>0) $this->ParseBack();
        
        $this->Parser->SetCaptionVariables($caption);
        $this->pageContent .= $this->Parser->MakeForm($di, 'google_analytics', 'form');
    }
    
    function SubmitGa()
    {
        $di = Data::Create('system_footer');
        $di->Update('id='.$this->idGoogleAnalytics, $_POST);
        
        Navigation::JumpBack($this->back);
    }
    
    function IsInstalled($text)
    {
        $codes = array(
            '_gat._getTracker(', 
            'urchinTracker(',
            '_gaq.push',
        );
        foreach ($codes as $code)
        {
            if (strstr($text, $code)) return true;
        }
        return false;
    }
}

$Page = new CpGaPage();
$Page->Render();

?>