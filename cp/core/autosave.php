<?php
chdir('../');
require_once('init/init.php');

class CpAutosavePage extends CPPage
{
	function __construct()
	{
		parent::__construct();
		
		$this->SetPostAction('autosave', 'AutoSave');
		
		header('Content-type: application/json');
	}

	function Page()
	{
		$this->ReturnError();
	}
	
    private function ReturnResult($data=null)
    {
        echo json_encode(array(
            'result' => true,
            'data' => $data
        ));
        exit();
    }
    
    private function ReturnError()
    {
        echo json_encode(array(
            'error' => 1,
            'error_description' => 'Access denied'
        ));
        exit();
    }
    
    function AutoSave()
    {
        $diName = isset($_POST['di']) ? $_POST['di'] : null;
        $id = isset($_POST['itemId']) ? intval($_POST['itemId']) : null;
        $field = isset($_POST['field']) ? $_POST['field'] : null;
        $value = isset($_POST['value']) ? $_POST['value'] : '';
        
        if ($diName === null || $id === null || $field === null)
        {
            $this->ReturnError();
        }
        
        $di = Data::Create($diName);
        $result = $di->Update('id='.$id, array($field => $value));
        
        if ($result) $this->ReturnResult();
        else $this->ReturnError();
    }
}

$Page = new CpAutosavePage();
$Page->Render();
?>