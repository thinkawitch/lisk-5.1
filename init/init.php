<?php

//the lines below are duplicated in .htaccess so no need to run them here by default
/*ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);*/

header('Content-type:text/html; charset=utf-8');

if (!defined('INIT_NAME')) define('INIT_NAME', 'main'); // name of site partition
if (!defined('ROOT_PATH')) define('ROOT_PATH', './');

define('STATIC_URLS',	true);
define('DEFAULT_TITLE', 'Lisk CMS Demo');

require_once(ROOT_PATH.'init/project.cfg.php');
require_once(ROOT_PATH.'init/utils/utils.class.php');

//LISK PROFILER
define('LISK_PROFILER',	false);
if (LISK_PROFILER)
{
	require_once(ROOT_PATH.'init/core/profiler.class.php');
	$Profiler = new LiskProfiler();
}

require_once(ROOT_PATH.'init/license.php');

StatActionHandler::SetVisitor();

// modules Load here

?>