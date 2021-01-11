<?php

/**
 * Class PPMFWC_Autoload
 * Generic autoloader for classes named in WordPress coding style.
 */
class PPMFWC_Autoload
{

    public static function ppmfwc_register()
    {
        spl_autoload_register(array(__CLASS__, 'spl_autoload_register'));
    }

    /**
     * @param $class_name
     */
    public static function spl_autoload_register($class_name)
    {
        $class_path = dirname(__FILE__) . '/' . str_replace('_', '/', $class_name) . '.php';

        if (file_exists($class_path)) {
            require_once $class_path;
        }
    }

}