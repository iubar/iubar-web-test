<?php

namespace Iubar\Tests;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;

/**
 * Hmac
 * 
 * @author Daniele
 *
 */
class SafeRestApi_TestCase extends RestApi_TestCase {
    
    protected $user = null;
    protected $api_key = null;
    
    // Esempio di authenticazione basic su web server:
    //
    // 	$response = $client->request('GET', $this->$url, [
    // 			'auth' => ['user', 'pass'],
    // 			'query' => $this->getQuery()
    // 	]);
    
    private function calcHash($url){
        $hash = null;
        $user = $this->user;
        $api_key = $this->api_key;
        $ts_str = self::getTimeStampString();
        if($user && $ts_str && $api_key){
            $data = $url . $user . $ts_str . $api_key;
            $raw_hash = hash_hmac('sha256', $data, $api_key, true); // Vedi https://en.wikipedia.org/wiki/Hash-based_message_authentication_code
            $hash = base64_encode($raw_hash);
        }
        return $hash;
    }
    
    protected static function getTimeStampString(){
        $now = new \DateTime();
        $ts_str = $now->format(\DateTime::RFC3339);
        return $ts_str;
    }
    
    protected function getAuthData($url){
        $user = $this->user;
        $ts = rawurlencode(self::getTimeStampString()); // Nota: il valore del timestamp deve essere codificato con urlencode perchè contiene il simbolo '+'
        $hash = $this->calcHash($url);
    
        $query = [
            'user' 	=> $user,
            'ts' 	=> $ts,
            'hash' 	=> $hash
        ];
    
        return $query;
    }
    
    
}