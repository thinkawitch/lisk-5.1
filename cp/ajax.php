<?php
include('init/init.php');

$handler = isset($_GET['handler']) ? $_GET['handler'] : null;
$param1 = isset($_GET['param1']) ? $_GET['param1'] : null;
$param2 = isset($_GET['param2']) ? $_GET['param2'] : null;

$methodName = 'Ajax'.$handler;

$result = new stdClass();
$result->result = 'error';

if (is_callable($methodName))
{
    $result->result = $methodName($param1, $param2);
}

echo json_encode($result);
exit();

function AjaxFileExists($path)
{
	GLOBAL $App;
	if (file_exists($App->sysRoot.$App->filePath.$path)) return 'error';
	else return 'available';
}
?>