<?php

project_Autoloader::register();

class project_Autoloader
{
    /**
     * Register the Autoloader with SPL
     *
     */
    public static function register()
    {
        if (function_exists('__autoload')) {
            // Register any existing autoloader function with SPL, so we don't get any clashes
            spl_autoload_register('__autoload');
        }
        // Register ourselves with SPL
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            return spl_autoload_register(array('project_Autoloader', 'load'), true, true);
        } else {
            return spl_autoload_register(array('project_Autoloader', 'load'));
        }
    }

    /**
     * Autoload a class identified by name
     *
     * @param    string    $pClassName        Name of the object to load
     */
    public static function load($pClassName)
    {
        if (class_exists($pClassName, false)) {
            // Either already loaded, or not a class request
            return false;
        }

        $found = false;
        $pClassFilePath = "";

        $paths = [];
        $paths[] = PROJECT_ROOT . $pClassName . '.php';
        $paths[] = PROJECT_ROOT . $pClassName . '.class.php';
        $paths[] = PROJECT_ROOT . str_replace('_', DIRECTORY_SEPARATOR, $pClassName) . '.php';
        $paths[] = PROJECT_ROOT . str_replace('_', DIRECTORY_SEPARATOR, $pClassName) . '.class.php';

        for($i = 0; $i < count($paths); $i++){

            $pClassFilePath = $paths[$i];

            if ((file_exists($pClassFilePath) !== false) && (is_readable($pClassFilePath) !== false)) {
                $found = true;
                break;
            }
        }

        if($found){
            require($pClassFilePath);
        }else{
            // Can't load
            return false;
        }
    }
}
