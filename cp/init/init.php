<?php
header('Content-type:text/html; charset=utf-8');

if (!defined('INIT_NAME')) define('INIT_NAME', 'cp'); // name of site partition
if (!defined('ROOT_PATH')) define('ROOT_PATH', '../');

define('STATIC_URLS',	false);

require_once(ROOT_PATH.'init/project.cfg.php');
require_once(ROOT_PATH.'init/utils/utils.class.php');
require_once(ROOT_PATH.'init/license.php');

$App->Load('cppage', 'core');
$App->Load('cpmodulepage', 'core');

define('LANGUAGE', 'en');

$App->Load('cp', 'obj');
$App->Load('cms_core', 'cms');
$App->Load('filemanager', 'class');

// additional modules

?>