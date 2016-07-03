<?php
namespace Iubar\Tests;

class Root_TestCase extends \PHPUnit_Framework_TestCase {

    protected function checkFile($file){
        if (!is_file($file)) {
            $error = "File not found: " . $file;
            throw new \PHPUnitException($error);            
        } else {
            if (!is_readable($file)) {
                $error = "File not readable: " . $file;
                throw new \PHPUnitException($error);
            }
        }
        return true;
    }
    
    protected static function checkPath($path){
        if (!is_dir($path)) {
                $error = "Path not found: " . $path;
                throw new \PHPUnitException($error);
        } else {
            if (!is_readable($path)) {
                $error = "Path not readable: " . $path;
                throw new \PHPUnitException($error);
            }
        }
        return true;
    }    
    
}