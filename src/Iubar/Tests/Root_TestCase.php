<?php
namespace Iubar\Tests;

class Root_TestCase extends \PHPUnit_Framework_TestCase {

    protected function checkFile($file){
        if (!is_file($file)) {
            $this->fail("File not found: " . $file . PHP_EOL);
        } else {
            if (!is_readable($file)) {
                $this->fail("File not readable: " . $file . PHP_EOL);
            }
        }
        return true;
    }
    
    protected static function checkPath($path){
        if (!is_dir($path)) {
            $this->fail("Path not found: " . $path . PHP_EOL);
        } else {
            if (!is_readable($path)) {
                $this->fail("Path not readable: " . $path . PHP_EOL);
            }
        }
        return true;
    }    
    
}