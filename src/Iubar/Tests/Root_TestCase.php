<?php
namespace Iubar\Tests;

use PHPUnit\Framework\TestCase;
use League\CLImate\CLImate;

/**
 *
 * @author Matteo
 */
abstract class Root_TestCase extends TestCase {


    // easily output colored text and special formatting
    protected static $climate = null;
    
    protected static function init(){
        self::$climate = new CLImate();
    }
        
    /**
     * Check if the path is a directory and is readable
     *
     * @param string $path
     * @throws \Exception the exception
     * @return boolean true if readable and is a directory
     */
    protected static function checkPath($path) {
        if (!is_dir($path)) {
            $error = 'Path not found: ' . $path;
            self::$climate->error(PHP_EOL . 'Exception: ' . $error);
            throw new \Exception($error);            
        }
        if (!is_readable($path)) {
            $error = 'Path not readable: ' . $path;
            self::$climate->error(PHP_EOL . 'Exception: ' . $error);
            throw new \Exception($error);
        }
        return true;
    }

    /**
     * Check if the path is writable
     *
     * @param string $path
     * @throws \Exception the exception
     */
    protected static function isPathWritable($path) {
        if (self::checkPath($path) && !is_writable($path)) {
            $error = 'Path not writable: ' . $path;
            self::$climate->error(PHP_EOL . 'Exception: ' . $error);
            throw new \Exception($error);
        }
        return true;
    }

    /**
     *
     * @param string $file
     * @throws \Exception
     * @return boolean
     */
    protected static function checkFile($file) {
        if (!is_file($file)) {
            $error = 'File not found: ' . $file;
            self::$climate->error(PHP_EOL . 'Exception: ' . $error);
            throw new \Exception($error);
        }
        if (!is_readable($file)) {
            $error = 'File not readable: ' . $file;
            self::$climate->error(PHP_EOL . 'Exception: ' . $error);
            throw new \Exception($error);
        }
        return true;
    }
}