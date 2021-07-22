<?php
require_once('init/init.php');

class AjaxHandler extends Page
{
    function __construct()
    {
        parent::__construct();
        
        $this->SetGetAction('suggest', 'Suggest');
    }
    
    function Page()
    {
        exit();
    }
    
    function Suggest()
    {
        $type = $_GET['type'];
        $name = $_GET['name'];
        $query = trim(urldecode($_GET['term']));
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
        
        $di = Data::Create($type);
        
        if (!isset($di->fields[$name]) || !($di->fields[$name] instanceof T_suggest_list))
        {
            exit('');
        }
        
        $field = $di->fields[$name];
        echo $field->GetSuggestion($query, $limit);
        exit();
    }
}

$ah = new AjaxHandler();
$ah->Process();

?>