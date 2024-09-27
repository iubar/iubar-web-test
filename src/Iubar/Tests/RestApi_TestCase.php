<?php
namespace Iubar\Tests;

use GuzzleHttp\Client;
use Psr\Http\Client\ClientExceptionInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\ClientInterface;

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

	protected static ClientInterface $client;
	protected static ?string $host = null;

	protected static function getHost() {
		if (!self::$host) {
			throw new \Exception('Missing host config !'); // in un contesto statico non posso usare $this->fail('Wrong config');
		}
		return self::$host;
	}

	protected static function factoryClient($host = null, $base_uri = null) {
		if (getenv('HTTP_HOST')) {
			self::$host = getenv('HTTP_HOST');
		} elseif ($host) {
			self::$host = $host;
		} else {
			die('Host non specificato');
		}

		if (!$base_uri) {
			$base_uri = self::$host . '/';
		}

		self::$climate->comment('factoryClient()');
		self::$climate->comment("\tHost:\t\t" . self::$host);
		self::$climate->comment("\tBase Uri:\t" . $base_uri);

		$userAgent = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36';
		$client = new Client([
			'base_uri' => $base_uri, // Base URI is used with relative requests
			'http_errors' => false, // Set to false to disable throwing exceptions on an HTTP protocol errors (i.e., 4xx and 5xx responses).
			// Vedi http://docs.guzzlephp.org/en/latest/request-options.html#http-errors
			// 'headers' => [
			//    'User-Agent' => $userAgent
			// ],
			'timeout' => self::TIMEOUT, // defaut timeout for all requests
			'verify' => false // Ignora la verifica dei certificati SSL (obbligatorio per accesso a risorse https)
			// @see: http://docs.guzzlephp.org/en/latest/request-options.html#verify-option

			//             "debug"      => true,
			//             "curl"        => [
			//                 CURLOPT_TIMEOUT => 0,
			//                 CURLOPT_TIMEOUT_MS => 0,
			//                 CURLOPT_CONNECTTIMEOUT => 0,
			//             ]
		]);
		return $client;
	}

	protected function sleep(int $seconds): void {
		self::$climate->comment('Waiting ' . $seconds . ' seconds...');
		sleep($seconds);
	}

	/**
	 * Handle the RequestException writing his msg
	 *
	 * @param ClientExceptionInterface $e the exception
	 */
	protected function handleException(ClientExceptionInterface $e): void {
		$this->printSeparator();
		self::$climate->flank('Http client exception catched...');
		self::$climate->error(PHP_EOL . 'Exception message: ' . PHP_EOL . $e->getMessage());
		$this->printSeparator();
	}

	/**
	 * Send an http GET request and return the response
	 *
	 *
	 * @param string $method the method
	 * @param string $partial_uri the partial uri
	 * @param string $array the query
	 * @param int $timeout the timeout
	 * @return ResponseInterface
	 */
	protected function sendGetReq($partial_uri, array $array, $timeout = null): ResponseInterface {
		$response = null;
		if (!$timeout) {
			$timeout = self::TIMEOUT;
		}
		if (!self::$client) {
			throw new \Exception('Client obj is null');
		}
		try {
			$request = new Request(self::GET, $partial_uri);

			self::$climate->comment(
				PHP_EOL .
					'Request: ' .
					PHP_EOL .
					"\tUrl:\t" .
					$partial_uri .
					PHP_EOL .
					"\tQuery:\t" .
					json_encode($array, JSON_PRETTY_PRINT)
			);

			$response = self::$client->send($request, [
				'headers' => [
					'User-Agent' => 'restapi_testcase/' . self::VERSION,
					'Accept' => 'application/json',
					'X-Requested-With' => 'XMLHttpRequest' // for Whoops' JsonResponseHandler
				],
				'query' => $array,
				'timeout' => $timeout
			]);
		} catch (ClientExceptionInterface $e) {
			$this->handleException($e);
			$this->fail('Failed beacause an ClientExceptionInterface was raised.');
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
	protected function checkResponse(ResponseInterface $response, int $expected_status_code = self::HTTP_OK): ?array {
		$data = null;
		if ($response) {
			$body = $response->getBody()->getContents(); // Warning: call 'getBody()->getContents()' only once ! getContents() returns the remaining contents, so that a second call returns nothing unless you seek the position of the stream with rewind or seek

			$this->printBody($body);

			// Format the response
			$data = json_decode($body, true); // returns an array

			$content_type = $response->getHeader(self::CONTENT_TYPE)[0];

			if ($content_type == self::APP_JSON_CT && isset($data['error'])) {
				// Intercetto le eccezioni nel formato json restituito da Whoops e stampo il messaggio di errore contenuto nella risposta json
				$this->printSeparator();
				self::$climate->flank('The json returned contains an error message...');
				$payload = $data['error'];
				$message = $payload['message'];
				self::$climate->error($message);
				$this->printSeparator();
				$this->fail('Failed');
			} else {
				if ($response->getStatusCode() != self::HTTP_OK) {
					// Response
					self::$climate->comment('Status code: ' . $response->getStatusCode());
					self::$climate->comment('Content-Type: ' . json_encode($response->getHeader('Content-Type'), JSON_PRETTY_PRINT));
					// self::$climate->info('Access-Control-Allow-Origin: '  . json_encode($response->getHeader('Access-Control-Allow-Origin'), JSON_PRETTY_PRINT));
				}

				// Asserzioni
				self::$climate->comment('Checking assertions...');
				$this->assertEquals($expected_status_code, $response->getStatusCode());
				$this->assertStringContainsStringIgnoringCase(self::APP_JSON_CT, $content_type);
				self::$climate->comment('...ok');
			}
		}
		return $data;
	}

	protected function printSeparator() {
		self::$climate->out(PHP_EOL . '--------------------------------------------' . PHP_EOL);
	}

	protected function printBody($body) {
		$max_char = 320;
		if (strlen($body) > $max_char) {
			$body = substr($body, 0, $max_char) . ' ...<truncated>';
		}
		$json = json_encode($body, JSON_PRETTY_PRINT);
		self::$climate->comment('Response body: ' . PHP_EOL . $body);
	}

	protected function printResponse(ResponseInterface $response) {
		self::$climate->flank('Response');
		$status_code = $response->getStatusCode();
		self::$climate->comment('Status Code: ' . $status_code);

		$reason = $response->getReasonPhrase();
		self::$climate->comment('Reason: ' . $reason);

		$header = $response->getHeader('content-type'); // eg: 'application/json; charset=utf8'
		self::$climate->comment('Headers: ' . json_encode($header, JSON_PRETTY_PRINT));

		$body = $response->getBody()->getContents(); // Attenzione, se il metodo getContents() è già stato invocato, qui restituisce la stringa vuota
		self::$climate->comment('Body: ' . $body);
	}

	protected function printRequest(RequestInterface $request): void {
		$body = $request->getBody();
		$query = $request->getUri()->getQuery();
		self::$climate->comment('Body: ' . $body);
		self::$climate->comment('Query: ' . $query);
	}

	protected function printHttpHeader(ResponseInterface $response): void {
		foreach ($response->getHeaders() as $name => $values) {
			$str = $name . ': ' . implode(', ', $values);
			self::$climate->info($str);
		}
	}
}
