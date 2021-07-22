<?php
chdir('../');
require_once('init/init.php');

class CpOrderPage extends CPPage
{
    function __construct()
    {
        parent::__construct();
        $this->SetPostAction('submit', 'Save');
        $this->SetGetPostAction('inline_order', 'submit', 'InlineOrderSubmit');
    }

    function Page()
    {
        $this->cmsOrder = new CMSOrder($_GET['type']);
        
        $this->SetTitle('Order '.$this->cmsOrder->dataItem->label, 'cms/order/uho.gif');

        $this->pageContent .= $this->cmsOrder->Render();
        $this->ParseBack();
    }

    function Save()
    {
        $this->cmsOrder = new CMSOrder($_GET['type']);
        $this->cmsOrder->Save();
        Navigation::JumpBack($this->back);
    }
    
    function InlineOrderSubmit()
    {
        GLOBAL $Paging;
        
        $pcp = intval($_POST['paging_pcp']);
        $diName = $_POST['dataitem_name'];
        $newOrder = $_POST['new_order'];
        
        $di = null;
        try
        {
            $di = Data::Create($diName);
        }
        catch (Exception $e)
        {
            exit();
        }
        
        if ($di instanceof Data)
        {
            $ids = explode(';', $newOrder);
            
            //select page, where items were re-ordered
            $Paging->SwitchOn('cp');
            $startIdx = $pcp * $Paging->GetItemsPerPage();
            
            foreach ($ids as $k=>$id)
            {
                $id = intval($id);
                $di->Update('id='.$id, array('oder' => $startIdx + $k));
            }
        }
        
        
        $response = new StdClass();
        echo json_encode($response);
        exit();
    }
}

$Page = new CpOrderPage();
$Page->Render();

?>