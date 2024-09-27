<?php

namespace Iubar\Tests;

/**
 * Hmac
 *
 * @author Daniele
 *
 */
class SafeRestApi_TestCase extends SimpleSafeRestApi_TestCase {
	protected function calcHash() {
		$hash = null;
		$user = $this->user;
		$api_key = $this->api_key;
		$ts_str = self::getTimeStampString();
		if ($user && $ts_str && $api_key) {
			$data = $this->url . $user . $ts_str . $api_key;
			$raw_hash = hash_hmac('sha256', $data, $api_key, true); // Vedi https://en.wikipedia.org/wiki/Hash-based_message_authentication_code
			$hash = base64_encode($raw_hash);
		}
		return $hash;
	}
}
