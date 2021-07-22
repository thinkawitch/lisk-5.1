<?php
chdir('../');
require_once('init/init.php');

class CpSuggestListPage extends CPPage
{
	function __construct()
	{
		parent::__construct();
	}

	function Page()
	{
		$type = $_GET['type'];
		$name = $_GET['name'];
		$query = trim(urldecode($_GET['q']));
		$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
		
		$di = Data::Create($type);
		
		if (!isset($di->fields[$name]) || !($di->fields[$name] instanceof T_suggest_list))
		{
            echo '';
            exit();
		}
		
		$field = $di->fields[$name];
		echo $field->GetSuggestion($query, $limit);
	    exit();
	}
}

$Page = new CpSuggestListPage();
$Page->Render();

?>