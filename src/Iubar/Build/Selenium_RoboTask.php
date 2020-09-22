<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
namespace Iubar\Build;

use Iubar\Tests\Web_TestCase;
use Robo\Result;
use Robo\ResultData;
use Robo\Common\IO;

class Selenium_RoboTask extends Root_RoboTask {

	use IO;

	const EXIT_ERROR = 1;

	const EXIT_OK = 0;

	private $browser = null;

	private $browser_version = null;

	private $os_version = null;

	private $selenium_server = null;

	private $selenium_port = null;

	private $selenium_path = null;

	private $start_selenium = true;

	private $phantomjs_binary = null;

	private $open_slideshow = true;

	private $open_dump_file = true;

	private $batch_mode = true;

	private $logs_path = null;

	private $screenshots_path = null;
	
	private $phpunit_xml_file = null;
	
	private $selenium_jar = null;
	
	private $chrome_driver = null;
	
	private $geko_driver = null;
	
	private $edge_driver = null;

	public function __construct($working_path) {
		parent::__construct($working_path);
	}

	public function test() {
		parent::init();
		$this->config();
		$this->printConfig();
		$this->composer();
		$this->runPhpUnit();
	}

	private function runPhpUnit() {
		$result1 = null;
		if ($this->start_selenium) {
			$this->say('Starting Selenium...');
			$result1 = $this->startSelenium();
			if (!$result1 || !$result1->wasSuccessful()) {
				$error = 'Failed to start Selenium';
				$this->yell($error);
				return new ResultData(ResultData::EXITCODE_ERROR, $error);
			}
		}
		$result2 = $this->phpUnit($this->phpunit_xml_file);

		if (!$result2 || !$result2->wasSuccessful()) {
			$error = 'Some tests failed.';
			$this->yell($error);
			return new ResultData(ResultData::EXITCODE_ERROR, $error);
		} else {
			$msg = 'Tests completed successfully.';
			$this->say($msg);
			return new ResultData(ResultData::EXITCODE_OK, $msg);
		}
		$this->afterTestRun();
	}

	private function afterTestRun() {
		if ($this->start_selenium) {
			// Don't need to explicitly close selenium when usign Robo
			// echo 'Shutting down Selenium...' . PHP_EOL;
			// $this->stopSelenium();
		}

		$screenshots_count = getenv('SCREENSHOTS_COUNT');
		$this->yell("Screnshots taken: " . $screenshots_count);
		if ($screenshots_count) {
			$this->say("Screnshots path: " . $this->screenshots_path);
		}
		if (!$this->batch_mode) {
			// Screenshots
			if ($this->open_slideshow && $screenshots_count) {
				$confirmed = $this->confirm('Do you want to see the slideshow ?');
				if ($confirmed) {
					$host = 'localhost';
					$port = '8000';
					$this->say("Running slideshow on local php integrated webserver at $host:$port...");
					$this->startHttpServer();
					$url = 'http://' . $host . ':' . $port . '/slideshow/index.php';
					$this->browser($url);
				}
			}
			// Console output
			$json_dump_files = getenv('DUMP_FILES');
			$dump_files = array();
			if ($json_dump_files) {
				$dump_files = json_decode($json_dump_files);
			}
			$this->yell("Console dumps: " . count($dump_files));
			if ($this->open_dump_file) {
				foreach ($dump_files as $filename) {
					$this->browser($filename);
				}
			}
		}
	}

	/**
	 *
	 * @param string $str Returns TRUE for "1", "true", "on" and "yes". Returns FALSE otherwise. (If FILTER_NULL_ON_FAILURE is set, FALSE is returned only for "0", "false", "off", "no", and "", and NULL is returned for all non-boolean values)
	 */
	protected function parseBoolean($str) {
		$b = filter_var($str, FILTER_VALIDATE_BOOLEAN);
		;
		return $b;
	}

	/**
	 * From Selenium 3.x it works only if it's runnning in "node" mode
	 */
	private function stopSelenium() {
		$url = 'http://' . $this->selenium_server . '/selenium-server/driver/?cmd=shutDownSeleniumServer';
		$this->browser($url);
	}

	/**
	 * It doesn't work
	 */
	private function closeSeleniumSession() {
		$url = 'http://' . $this->selenium_server . '/selenium-server/driver/?cmd=shutDown';
		$this->browser($url);
	}

	/**
	 *
	 * @param unknown $path
	 * @return boolean
	 */
	private function isRelativePath($path) {
		$tmp = realpath($path);
		$path = str_replace('\\', '/', $path);
		$tmp = str_replace('\\', '/', $tmp);
		if ($path != $tmp) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param unknown $var_name
	 * @param unknown $path
	 */
	private function putEnv($var_name, $path) {
		if (!getenv($var_name)) {
			if ($this->isRelativePath($path)) {
				$path = $this->working_path . DIRECTORY_SEPARATOR . $path;
			}
		}
		if (!is_dir($path)) {
			$error = 'Path not found: ' . $path;
			$this->yell($error);
			exit(1);
		}
		if (!getenv($var_name)) {
			putenv($var_name . '=' . $path);
		}
	}

	/**
	 */
	protected function config() {
		if (!getenv('BROWSER')) {
			$this->browser = $this->ini_array['browser'];
			putenv('BROWSER=' . $this->browser);
		} else {
			$this->browser = getenv('BROWSER');
		}

		if (!getenv('BROWSER_VERSION')) {
			$this->browser_version = $this->ini_array['browser_version'][$this->browser];
			putenv('BROWSER_VERSION=' . $this->browser_version);
		}

		if (!getenv('OS_VERSION')) {
			$this->os_version = $this->ini_array['os_version'][$this->browser];
			putenv('OS_VERSION=' . $this->os_version);
		}

		if (!getenv('SELENIUM_SERVER')) {
			$this->selenium_server = $this->ini_array['selenium_server'];
			putenv('SELENIUM_SERVER=' . $this->selenium_server);
		}

		if (!getenv('SELENIUM_PORT')) {
			$this->selenium_port = $this->ini_array['selenium_port'];
			putenv('SELENIUM_PORT=' . $this->selenium_port);
		}

		if (!getenv('PHANTOMJS_BINARY')) {
			$this->phantomjs_binary = $this->ini_array['selenium_path'] . DIRECTORY_SEPARATOR . $this->ini_array['phantomjs_binary'];
			putenv('PHANTOMJS_BINARY=' . $this->phantomjs_binary);
		}

		if (!getenv('APP_HOST')) {
			$app_host = $this->ini_array['app_host'];
			putenv('APP_HOST=' . $app_host);
		}

		if (!getenv('APP_USERNAME')) {
			$app_username = $this->ini_array['app_username'];
			putenv('APP_USERNAME=' . $app_username);
		}

		// Posso specificare la password a) come variabile d'ambiente, b) nel file .ini, c) in modo interattivo da console

		if (!getenv('APP_PASSWORD')) {
			if (isset($this->ini_array['app_password'])) {
				$app_password = $this->ini_array['app_password'];
			}
			if (!$app_password) {
				if (!$this->batch_mode) {
					$app_password = $this->ask('Please enter password for ' . $app_username . ':'); // TODO: Change ask() with askHidden();
						                                                                                // FIXME: https://github.com/consolidation-org/Robo/issues/376
						                                                                                // @see https://github.com/symfony/console/blob/master/Resources/bin/hiddeninput.exe
				} else {
					$error = 'Enviroment var not set: APP_PASSWORD';
					$this->yell($error);
					exit(1);
				}
			}
			putenv('APP_PASSWORD=' . $app_password);
		}

		$this->logs_path = $this->ini_array['logs_path'];
		$this->putEnv('LOGS_PATH', $this->logs_path);

		$this->screenshots_path = $this->ini_array['screenshots_path'];
		$this->putEnv('SCREENSHOTS_PATH', $this->screenshots_path);

		// Non uso variabili d'ambiente per i seguenti valori, perchè riguardano solo il testing con Robo
		$this->selenium_path = $this->ini_array['selenium_path'];
		$this->selenium_jar = $this->selenium_path . DIRECTORY_SEPARATOR . $this->ini_array['selenium_jar'];
		$this->chrome_driver = $this->selenium_path . DIRECTORY_SEPARATOR . $this->ini_array['chrome_driver'];
		$this->geko_driver = $this->selenium_path . DIRECTORY_SEPARATOR . $this->ini_array['geko_driver'];
		$this->edge_driver = $this->selenium_path . DIRECTORY_SEPARATOR . $this->ini_array['edge_driver'];
		$this->phantomjs_binary = $this->selenium_path . DIRECTORY_SEPARATOR . $this->ini_array['phantomjs_binary'];

		$this->open_slideshow = $this->parseBoolean($this->ini_array['open_slideshow']);
		$this->open_dump_file = $this->parseBoolean($this->ini_array['open_dumpfile']);
		$this->batch_mode = $this->parseBoolean($this->ini_array['batch_mode']);
		$this->start_selenium = $this->parseBoolean($this->ini_array['start_selenium']);

		$this->env_cfg['BROWSER'] = getenv('BROWSER');
		$this->env_cfg['BROWSER_VERSION'] = getenv('BROWSER_VERSION');
		$this->env_cfg['OS_VERSION'] = getenv('OS_VERSION');
		$this->env_cfg['SELENIUM_SERVER'] = getenv('SELENIUM_SERVER');
		$this->env_cfg['SELENIUM_PORT'] = getenv('SELENIUM_PORT');
		$this->env_cfg['PHANTOMJS_BINARY'] = getenv('APP_USERNAME');
		$this->env_cfg['APP_HOST'] = getenv('APP_HOST');
		$this->env_cfg['APP_USERNAME'] = getenv('APP_USERNAME');
		$this->env_cfg['APP_PASSWORD'] = $this->formatPassword(getenv('APP_PASSWORD'));
		$this->env_cfg['LOGS_PATH'] = getenv('LOGS_PATH');
		$this->env_cfg['SCREENSHOTS_PATH'] = getenv('SCREENSHOTS_PATH');

		$this->other_cfg['batch mode'] = $this->formatBoolean($this->batch_mode);
		$this->other_cfg['selenium path'] = $this->selenium_path;
		$this->other_cfg['selenium jar'] = $this->selenium_jar;
		$this->other_cfg['chrome driver'] = $this->chrome_driver;
		$this->other_cfg['geko driver'] = $this->geko_driver;
		$this->other_cfg['edge driver'] = $this->edge_driver;
		$this->other_cfg['phantomjs binary'] = $this->phantomjs_binary;
		$this->other_cfg['start selenium'] = $this->formatBoolean($this->start_selenium);
		$this->other_cfg['open slideshow'] = $this->formatBoolean($this->open_slideshow);
		$this->other_cfg['open dumpfile'] = $this->formatBoolean($this->open_dump_file);
	}

	private function startSeleniumAllDrivers() {
		$result = null;
		$cmd = $this->getSeleniumAllCmd();
		$this->say('Cmd is: ' . $cmd);
		// launches Selenium server
		$result = $this->taskExec($cmd)
			->background()
			->printed(true)
			->run();
		return $result;
	}

	private function startSelenium() {
		$result = null;
		$cmd = $this->getSeleniumCmd();
		// launches Selenium server
		$result = $this->taskExec($cmd)
			->background()
			->printed(true)
			->run();
		return $result;
	}

	private function getSeleniumAllCmd() {
		$cmd = null;
		$this->checkFile($this->selenium_jar, true);
		// https://github.com/SeleniumHQ/selenium/issues/2571 for the command
		$cmd = 'java -jar';
		$this->checkFile($this->chrome_driver, true);
		$this->checkFile($this->geko_driver, true);
		$this->checkFile($this->phantomjs_binary, true);
		$cmd .= ' -Dwebdriver.chrome.driver=' . $this->chrome_driver . ' -Dwebdriver.gecko.driver=' . $this->geko_driver . ' -Dphantomjs.binary.path=' . $this->phantomjs_binary;
		$cmd .= ' ' . $this->selenium_jar;
		return $cmd;
	}

	private function getSeleniumCmd() {
		// https://github.com/SeleniumHQ/selenium/issues/2571 for the command
		$this->checkFile($this->selenium_jar, true);
		$cmd = 'java -jar';
		switch ($this->browser) {
			case Web_TestCase::CHROME:
				$this->checkFile($this->chrome_driver, true);
				$cmd .= ' -Dwebdriver.chrome.driver=' . $this->chrome_driver;
				break;
			case Web_TestCase::MARIONETTE:
				$this->checkFile($this->geko_driver, true);
				// per scegliere eseguibile: ' -Dwebdriver.firefox.bin=' . '\'C:/Program Files (x86)/Firefox Developer Edition/firefox.exe\'';
				$cmd .= ' -Dwebdriver.gecko.driver=' . $this->geko_driver;
				break;
			case Web_TestCase::FIREFOX:
				// nothing to do
				break;
			case Web_TestCase::SAFARI:
				// SafariDriver now requires manual installation of the extension prior to automation.
				// (So I think no driver is required)
				// see https://github.com/SeleniumHQ/selenium/wiki/SafariDriver
				break;
			case Web_TestCase::PHANTOMJS:
				$this->checkFile($this->phantomjs_binary, true);
				$phantomjs_log_file = realpath($this->logs_path) . DIRECTORY_SEPARATOR . 'phantomjsdriver.log';
				// see: https://github.com/detro/ghostdriver
				// OK:
				// $cmd = $cmd_prefix . ' -Dphantomjs.ghostdriver.cli.args=[\'--loglevel=DEBUG\'] -Dphantomjs.binary.path=' . $this->phantomjs_binary;
				// FIXME: non scrive il file di log:
				$cmd .= ' -Dphantomjs.ghostdriver.cli.args=[\'--loglevel=DEBUG\'] -Dphantomjs.cli.args=[\'--debug=true --webdrive --loglevel=DEBUG --webdriver-logfile=' . $phantomjs_log_file . '\'] -Dphantomjs.binary.path=' . $this->phantomjs_binary;
				break;
			default:
				$error = 'The browser ' . $this->browser . ' is not supported';
				$this->yell($error);
				exit(1);
		}
		$cmd .= ' ' . $this->selenium_jar;
		return $cmd;
	}

	/**
	 */
	private function startHttpServer() {
		$dir = realpath($this->screenshots_path);
		if (!is_dir($dir)) {
			$error = 'Path not found: ' . $dir;
			$this->yell($error);
			exit(1);
		}
		// starts PHP built-in server in background
		$this->taskServer(8000)
			->dir($dir)
			->background()
			->run();
	}

	/**
	 *
	 * @param unknown $url
	 * @return string
	 */
	private function browser($url, $default = false) {
		$browser = self::$browser;
		if (self::$browser == self::PHANTOMJS) {
			$default = true;
		} else {
			if (self::$browser == self::MARIONETTE) {
				$default = true;
			}
		}
		$this->say('Opening browser at: ' . $url);
		$cmd = null;
		if (self::isWindows()) {
			if (!$default) {
				$cmd = "start \"\" \"$browser $url\""; // opening the same browser that was choosen for the test
			} else {
				$cmd = "start \"\" $url"; // opening the default system browser
			}
		} else {
			$error = 'Warning: Linux Os not yet supported'; // FIXME:
			$this->yell($error);
		}
		if ($cmd) {
			$this->taskExec($cmd)
				->arg('')
				->printed(true)
				->run();
		}
		return true;
	}
}
