<?php
/**
 * CLASS Settings
 * @package lisk
 *
 */
class Settings
{
    static private $cache = array();
    static private $table = 'sys_settings';

    static function Get($name)
    {
        if (array_key_exists($name, self::$cache)) return self::$cache[$name];
        
        GLOBAL $Db;
        $value = $Db->Get('name='.Database::Escape($name), 'value', self::$table);
        
        if (is_null($value)) self::$cache[$name] = null;
        else self::$cache[$name] = unserialize($value);
        
        return self::$cache[$name];
    }
    
    static function Set($name, $value)
    {
        GLOBAL $Db;
        
        //update cache first
        self::$cache[$name] = $value;
        
        //put into db
        $sql = 'REPLACE '.self::$table.' SET `name`='.Database::Escape($name).', `value`='.Database::Escape(serialize($value));
        $Db->Query($sql);
    }
}

?>