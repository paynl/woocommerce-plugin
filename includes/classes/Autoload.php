<?php

/**
 * Generic autoloader for classes named in WordPress coding style.
 */
class Pay_Autoload {

    public static function register(){
        spl_autoload_register(array(__CLASS__, 'spl_autoload_register'));
    }

    public static function spl_autoload_register($class_name) {
        
        $class_path = dirname(__FILE__) . '/' . str_replace('_', '/', $class_name) . '.php';

        if (file_exists($class_path)){
            require_once $class_path;
        }
    }

}