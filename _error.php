<?php
include_once 'init/project.cfg.php';
$hostName = 'host'.$hostMode;
extract($$hostName);
// remove http root from REQUEST_URI
$url = $_SERVER['REQUEST_URI'];
$prefixLen = strlen($rootHttpPath);
if (substr($url, 0, $prefixLen)==$rootHttpPath) $url = substr($url, $prefixLen);

// retrieve paging params
$arrUrl = explode('/', $url);
if (is_array($arrUrl) && count($arrUrl))
{
    $unsetKeys = array();
    foreach ($arrUrl as $k=>$v)
    {
        if ($v == '')
        {
            $unsetKeys[] = $k;
            continue;
        }
        if (substr($v, 0, 5) == 'page_')
        {
            $_GET['pcp'] = intval(substr($v, 5)) - 1;
            if ($_GET['pcp'] < 0)  $_GET['pcp'] = 0;
            $unsetKeys[] = $k;
        }
        elseif (substr($v, 0, 3) == 'pi_')
        {
            $_GET['pi'] = substr($v, 3);
            $unsetKeys[] = $k;
        }
    }
    
    foreach ($unsetKeys as $k) unset($arrUrl[$k]);
    
    $cleanUrl = implode('/', $arrUrl);
    if (substr($url, -1, 1) == '/') $cleanUrl .= '/';
    
    $url = $cleanUrl;
}


//add standart _GET variables into $_GET
if ('' == $_SERVER['QUERY_STRING'])
{
    //est' li voobsche u nas stroka parametrov
    $queryParamsStart = strpos($url, '?');
    if ($queryParamsStart !== false)
    {
        $queryString = substr($url, $queryParamsStart + 1);
        //esli stroka ne pustaia, to razberem ee
        if (strlen($queryString))
        {
            $queryArr = explode('&', $queryString);
            //esli vse zhe est' kluchi i znachenia
            if (is_array($queryArr) && count($queryArr))
            {
                foreach ($queryArr as $pair)
                {
                    list ($pairKey, $pairValue) = explode('=', $pair);
                    if ($pairValue == null) $_GET[$pairKey] = $pairKey;
                    else $_GET[$pairKey] = urldecode($pairValue);
                }
            }
        }
    }
}

// get GET values
$values = explode('/', $url);
$contentUrl = $url;
$base = '';
$parameters = array();
$GLOBALS['path'] = '';
// check current url for scripts & set $GLOBALS['']
foreach ($values as $key => $value)
{
    $base .= $value;
    if (file_exists($base.'.php'))
    {
        foreach ($values as $key1 => $value1)
        {
            if ($key1 > $key) $parameters[] = $value1;
        }
        if (is_array($parameters) && count($parameters) > 1)
        {
            if (! $parameters[count($parameters) - 1]) unset($parameters[count($parameters) - 1]);
        }
        $_SERVER['SCRIPT_NAME'] = $value . '.php';
        $_SERVER['PHP_SELF'] = $value . '.php';
        include $base.'.php';
        exit();
    }
    $base .= '/';
}

//clean url is used to detect handler, if _GET params passed
$cleanUrl = '';
if (false !== strpos($contentUrl, '?'))
{
	$cleanUrl = dirname($url);
	if (strlen($cleanUrl) > 1) $cleanUrl .= '/';
}

if (strlen($contentUrl)>0)
{
	if (
		(substr($contentUrl, -1) != '/' && !strlen($cleanUrl)) 
		&& substr($contentUrl, -4) != '.htm' 
		&& substr($contentUrl, -5) != '.html'
	)
	{
		header('HTTP/1.0 404 Not Found', true, 404);
        echo file_get_contents('tpl/404.htm');
	}
	else
	{
		include 'scms.php';
	}
}
else
{
    $_SERVER['SCRIPT_NAME'] = 'index.php';
    $_SERVER['PHP_SELF'] = 'index.php';
    include 'index.php';
}

exit();
?>