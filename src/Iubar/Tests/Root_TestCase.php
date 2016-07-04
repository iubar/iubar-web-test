<?php
namespace Iubar\Tests;

/**
 *
 * @author Matteo
 */
class Root_TestCase extends \PHPUnit_Framework_TestCase {

    /**
     *Check if the path is a directory and is readable
     *
     * @param string $path
     * @throws \Exception the exception
     * @return boolean true if readable and is a directory
     */
    protected static function checkPath($path) {
        if (!is_dir($path)) {
            $error = "Path not found: " . $path;
            throw new \Exception($error);
        }
        if (!is_readable($path)) {
            $error = "Path not readable: " . $path;
            throw new \Exception($error);
        }
        return true;
    }

    /**
     *Check if the path is writable
     * @param string $path
     * @throws \Exception the exception
     */
    protected static function isPathWritable($path) {
        if (self::checkPath($path) && !is_writable($path)) {
            $error = "Path not writable: " . $path;
            throw new \Exception($error);
        }
    }

    /**
     *
     * @param string $file
     * @throws \Exception
     * @return boolean
     */
    protected function checkFile($file) {
        if (!is_file($file)) {
            $error = "File not found: " . $file;
            throw new \Exception($error);
        }
        if (!is_readable($file)) {
            $error = "File not readable: " . $file;
            throw new \Exception($error);
        }
        return true;
    }
}