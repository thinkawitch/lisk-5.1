<?php

class Libchart
{
    private static $dir;
    
    public static function Prepare()
    {
        GLOBAL $App;
        $App->LoadModule('modules/libchart/classes/libchart.php', 1);
        self::$dir = getcwd();
        chdir($App->sysRoot.$App->initPath.'modules/libchart/');
    }
    
    public static function Restore()
    {
        chdir(self::$dir);
    }
    
}
?>