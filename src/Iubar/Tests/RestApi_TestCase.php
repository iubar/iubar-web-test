<?php
namespace Iubar\Tests;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;

/**
 * PHPUnit_Framework_TestCase Develop
 *
 * @author Matteo
 * 
 * #see https://curl.haxx.se/libcurl/c/libcurl-errors.html
 * 
 */
abstract class RestApi_TestCase extends Root_TestCase {

    const VERSION = '1.0';
    
    const GET = 'GET';
    
    const POST = 'POST';
    
    const APP_JSON_CT = 'application/json';
    
    const HTTP_OK = 200;
    
    const HTTP_BAD_REQUEST = 400;
    
    const HTTP_UNAUTHORIZED = 401;
    
    const HTTP_FORBIDDEN = 403;
    
    const HTTP_METHOD_NOT_ALLOWED = 405;
    
    const HTTP_NOT_FOUND = 404;
        
    const CONTENT_TYPE = 'Content-Type';
        
    const TIMEOUT = 4; // seconds
      
    protected static $client = null;

    protected static function factoryClient($base_uri=null){
        if(!$base_uri){            
            $base_uri = self::getHost() . '/';            
        }
        
        self::$climate->comment('factoryClient()');
        self::$climate->comment ("\tHost:\t\t" . self::getHost());
        self::$climate->comment("\tBase Uri:\t" . $base_uri);
        
        $client = new Client([
            'base_uri' => $base_uri,    // Base URI is used with relative requests
            'http_errors' => false,     // Vedi http://docs.guzzlephp.org/en/latest/request-options.html#http-errors
            // 'headers' => [
            //    'User-Agent' => "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36"            
            // ],
            'timeout' => self::TIMEOUT, // defaut timeout for all requests
            'verify' => false,          // Ignora la verifica dei certificati SSL (obbligatorio per accesso a risorse https)
                                        // @see: http://docs.guzzlephp.org/en/latest/request-options.html#verify-option
        ]);
        return $client;
    }
    
    protected function sleep($seconds){
        self::$climate->comment('Waiting ' . $seconds . ' seconds...');
        sleep($seconds);
    }
    
    protected static function getHost(){
        $http_host = getenv('HTTP_HOST');
        if(!$http_host){
            throw new \Exception('Wrong config !'); // in un contesto statico non posso usare $this->fail('Wrong config');
        }
        return $http_host;
    }
    
    /**
     * Handle the RequestException writing his msg
     *
     * @param RequestException $e the exception
     */
    protected function handleException(RequestException $e) {
        // ConnectException extends RequestException
        // ...is thrown in the event of a networking error.
        // ClientException extends BadResponseException which extends RequestException
        // ...is thrown for 400 level errors
        // ServerException extends BadResponseException which extends RequestException
        // ...is thrown for 500 level errors
        // RequestException
        // ..is thrown in the event of a networking error (connection timeout, DNS errors, etc.),
        
        $this->printSeparator();
        self::$climate->flank('Http client exception catched...');
        
// TODO: BLOCCO COMMENTATO DA CANCELLARE        
//         $client_config = self::$client->getConfig();
//         if($client_config && is_array($client_config)){
//             self::$climate->out('Client config');
//             foreach ($client_config as $key=>$value){                
//                 if($key=='handler'){
//                     self::$climate->out($key . "\t" . $value->__toString());
//                 }else{                
//                     if(is_array($value)){
//                         self::$climate->out($key . ':');
//                         print_r($value);
//                     }else{
//                         self::$climate->out($key . "\t" . $value);
//                     }
//                 }
//             }
//         }
        
        $request = $e->getRequest();      
        self::$climate->comment(PHP_EOL . 'Request: ' . trim(Psr7\str($request)));
        // oppure $this->printRequest($request);
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            self::$climate->error('Response code: ' . $response->getStatusCode());
            self::$climate->error('Response string: ' . PHP_EOL . trim(Psr7\str($response)));
            // oppure $this->printResponse($response);
        }        
        self::$climate->error(PHP_EOL . 'Exception message: ' . PHP_EOL . $e->getMessage());
        $this->printSeparator();
        $this->fail('Exception');
    }
    
    /**
     * Send an http GET request and return the response
     *
     * @param string $method the method
     * @param string $partial_uri the partial uri
     * @param string $array the query
     * @param int $timeout the timeout
     * @return string the response
     */
    protected function sendGetReq($partial_uri, array $array, $timeout=null) {
        $response = null;
        if(!$timeout){
            $timeout = self::TIMEOUT;
        }
        if(!self::$client){
            throw new \Exception("Client obj is null");
        }
        try {
            $request = new Request(self::GET, $partial_uri);

            self::$climate->comment(PHP_EOL . "Request: " . PHP_EOL . "\tUrl:\t" . $partial_uri . PHP_EOL . "\tQuery:\t" . json_encode($array, JSON_PRETTY_PRINT));
            
            $response = self::$client->send($request, [
                'headers' => [
                                'User-Agent' => 'restapi_testcase/' . self::VERSION,
                                'Accept'     => 'application/json',
                                'X-Requested-With' => 'XMLHttpRequest' // for Whoops' JsonResponseHandler
                            ],
                'query' => $array,
                'timeout' => $timeout                
            ]);
                        
        } catch (ConnectException $e) { // Is thrown in the event of a networking error. (This exception extends from GuzzleHttp\Exception\RequestException.)
            $this->handleException($e);
        } catch (ClientException $e) { // Is thrown for 400 level errors if the http_errors request option is set to true.
            $this->handleException($e);            
        } catch (RequestException $e) { // In the event of a networking error (connection timeout, DNS errors, etc.), a GuzzleHttp\Exception\RequestException is thrown.
            $this->handleException($e);
        } catch (ServerException $e) { // Is thrown for 500 level errors if the http_errors request option is set to true.
            $this->handleException($e);
        }
        return $response;
    }
    
    /**
     * Check the OK status code and the APP_JSON_CT content type of the response
     * 
     * Usage:   $data = $this->checkResponse($response); 
     *          self::$climate->info('Response Body: ' . PHP_EOL . json_encode($data, JSON_PRETTY_PRINT)); 
     *
     * @param string $response the response
     * @param int $status_code the expected http status code          
     * @return string the body of the decode response
     */
    protected function checkResponse($response, $expected_status_code = self::HTTP_OK) {
        $data = null;
        if ($response) {
                 
            $body = $response->getBody()->getContents(); // Warning: call 'getBody()->getContents()' only once ! getContents() returns the remaining contents, so that a second call returns nothing unless you seek the position of the stream with rewind or seek
            
            $this->printBody($body);
            
            // Format the response
            $data = json_decode($body, true); // returns an array
            
            $content_type = $response->getHeader(self::CONTENT_TYPE)[0];
            
            if($content_type==self::APP_JSON_CT && isset($data['error'])){ // Intercetto le eccezioni nel formato json restituito da Whoops e stampo il messaggio di errore contenuto nella risposta json
                $this->printSeparator();
                self::$climate->flank('The json returned contains an error message...');
                $payload = $data['error'];
                $message = $payload['message'];
                self::$climate->error($message);
                $this->printSeparator();
                $this->fail('Failed');
            }else{
                
                if($response->getStatusCode()!=self::HTTP_OK){
                // Response
                self::$climate->comment('Status code: ' . $response->getStatusCode());
                self::$climate->comment('Content-Type: '  . json_encode($response->getHeader('Content-Type'), JSON_PRETTY_PRINT));
                // self::$climate->info('Access-Control-Allow-Origin: '  . json_encode($response->getHeader('Access-Control-Allow-Origin'), JSON_PRETTY_PRINT));
                }
                
                // Asserzioni                
                self::$climate->comment('Checking assertions...');                                    
                $this->assertEquals($expected_status_code, $response->getStatusCode());
                $this->assertContains(self::APP_JSON_CT, $content_type);
                self::$climate->comment('...ok');
            }         
            
        }
        return $data;
    }

    protected function printSeparator(){
        self::$climate->out(PHP_EOL . '--------------------------------------------' . PHP_EOL);
    }
    
    protected function printBody($body){
        $max_char = 320;
        if(strlen($body) > $max_char){
            $body = substr($body, 0, $max_char) . ' ...<truncated>';
        }
        $json = json_encode($body, JSON_PRETTY_PRINT);
        self::$climate->comment('Response body: ' . PHP_EOL . $body);
    }
    
    protected function printResponse($response){
        self::$climate->flank('Response');
        $status_code = $response->getStatusCode();
        self::$climate->comment('Status Code: ' . $status_code);
    
        $reason = $response->getReasonPhrase();
        self::$climate->comment('Reason: ' . $reason);
    
        $header = $response->getHeader('content-type');     // eg: 'application/json; charset=utf8'
        self::$climate->comment('Headers: ' . json_encode($header, JSON_PRETTY_PRINT));
    
        $body = $response->getBody()->getContents(); // Attenzione, se il metodo getContents() è già stato invocato, qui restituisce la stringa vuota
        self::$climate->comment('Body: ' . $body);
    }
    
    protected function printRequest($request){
        $body = $request->getBody();
        $query = $request->getUri()->getQuery();
        self::$climate->comment('Body: ' . $body);
        self::$climate->comment('Query: ' . $query);
    }
        
    protected function printHttpHeader($response){
        foreach ($response->getHeaders() as $name => $values) {
            $str = $name . ': ' . implode(', ', $values);
            self::$climate->info($str);
        }        
    }    
    
}