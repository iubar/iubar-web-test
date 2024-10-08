<?php

namespace Iubar\Tests;

/**
 * Hmac
 *
 * @author Daniele
 *
 */
class SimpleSafeRestApi_TestCase extends RestApi_TestCase {
	protected $user = null;
	protected $api_key = null;
	protected $url = null;

	// Esempio di authenticazione basic su web server:
	//
	// 	$response = $client->request('GET', $this->$url, [
	// 			'auth' => ['user', 'pass'],
	// 			'query' => $this->getQuery()
	// 	]);

	protected function calcHash() {
		$hash = null;
		$user = $this->user;
		$api_key = $this->api_key;
		$ts_str = self::getTimeStampString();
		if ($user && $ts_str && $api_key) {
			$data = $user . $ts_str . $api_key;
			$raw_hash = hash_hmac('sha256', $data, $api_key, true); // Vedi https://en.wikipedia.org/wiki/Hash-based_message_authentication_code
			$hash = base64_encode($raw_hash);
		}
		return $hash;
	}

	protected function getAuthData() {
		$user = $this->user;
		$ts = self::getTimeStampString();
		$hash = $this->calcHash();

		$query = [
			'user' => $user,
			'ts' => $ts,
			'hash' => $hash
		];

		return $query;
	}

	protected static function getTimeStampString() {
		$now = new \DateTime();
		$ts_str = $now->format(\DateTime::RFC3339);
		return $ts_str;
	}

	protected function setUrl($url) {
		$this->url = $url;
	}
}
