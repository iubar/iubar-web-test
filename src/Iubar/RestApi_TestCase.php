<?php
namespace Iubar;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

/**
 * PHPUnit_Framework_TestCase Develop
 *
 * @author Matteo
 *        
 */
class RestApi_TestCase extends Root_TestCase {

    /**
     * Handle the RequestException writing his msg
     *
     * @param RequestException $e the exception
     */
    protected function handleException(RequestException $e) {
        echo "REQUEST: " . Psr7\str($e->getRequest());
        echo "ECCEZIONE: " . $e->getMessage() . PHP_EOL;
    }
}