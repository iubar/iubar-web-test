<?php
namespace Iubar\Tests;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

/**
 * PHPUnit_Framework_TestCase Develop
 *
 * @author Matteo
 */
class RestApi_TestCase extends Root_TestCase {

    const APP_JSON_CT = 'application/json';
    
    const HTTP_OK = 200;
    
    const CONTENT_TYPE = 'Content-Type';
    
    protected $client = null;
        
    /**
     * Handle the RequestException writing his msg
     *
     * @param RequestException $e the exception
     */
    protected function handleException(RequestException $e) {
        echo "REQUEST: " . Psr7\str($e->getRequest());
        echo "ECCEZIONE: " . $e->getMessage() . PHP_EOL;
        $this->fail();
    }
    
    /**
     * Send an http request and return the response
     *
     * @param string $method the method
     * @param string $partial_uri the partial uri
     * @param string $array the query
     * @param int $timeout the timeout
     * @return string the response
     */
    protected function sendRequest($method, $partial_uri, $array, $timeout) {
        $response = null;
        try {
            $request = new Request($method, $partial_uri);
            $response = $this->client->send($request, [
                'timeout' => $timeout,
                'query' => $array
            ]);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
        return $response;
    }
    
    /**
     * Check the OK status code and the APP_JSON_CT content type of the response
     *
     * @param string $response the response
     * @return string the body of the decode response
     */
    protected function checkResponse($response) {
        $data = null;
        if ($response) {
            // Response
            $this->assertContains(self::APP_JSON_CT, $response->getHeader(self::CONTENT_TYPE)[0]);
            $this->assertEquals(self::HTTP_OK, $response->getStatusCode());
    
            echo 'Response code is: ' . $response->getStatusCode() . PHP_EOL;
            // Getting data
            $data = json_decode($response->getBody(), true);
        }
        return $data;
    }
    
    
}