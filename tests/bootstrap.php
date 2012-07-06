<?php
// preload source files
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src.php';

// autoload test files
spl_autoload_register(function($class) {
    $file = dirname(__DIR__). DIRECTORY_SEPARATOR
          . 'tests' . DIRECTORY_SEPARATOR
          . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
