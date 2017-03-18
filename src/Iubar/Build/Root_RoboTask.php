<?php
namespace Iubar\Build;

use Robo\Common\Timer;

class Root_RoboTask extends \Robo\Tasks {

	use Timer;

	const HIDDEN = '**********';

	protected $working_path = '';

	protected $env_cfg = array();

	protected $other_cfg = array();

	protected $phpunit_xml_path = null;

	protected $update_vendor = false;

	protected $composer_json_path = null;

	protected $ini_array = array();

	public function __construct($working_path) {
		$this->working_path = $working_path;
		$this->other_cfg['working path'] = $this->working_path;
	}

	public function __destruct() {
		$this->say("Destroying Root_RoboTask class...");
		$this->stopTimer();
		$this->say('Total execution time: ' . $this->getExecutionTime());
	}

	/**
	 *
	 * @param unknown $file
	 * @return \Exception|boolean
	 */
	protected function checkFile($file, $is_link = false) {
		// In alternativa si potrebbe utilizzare https://github.com/consolidation-org/Robo/blob/master/src/Common/ResourceExistenceChecker.php
		if (!is_file($file)) {
			$error = 'File not found: ' . $file;
			$this->yell($error);
			exit(1);
		}
		if (!$is_link && !is_readable($file)) {
			$error = 'File not readable: ' . $file;
			$this->yell($error);
			exit(1);
		}
		return true;
	}

	protected function composer() {
		$result = null;
		if (!is_dir($this->composer_json_path)) {
			die('Path not found: ' . $this->composer_json_path);
		}
		$vendor_folder = $this->composer_json_path . DIRECTORY_SEPARATOR . 'vendor';
		if (!is_dir($vendor_folder)) {
			$this->say('Vendor folder not found: ' . $vendor_folder);
			$this->say('Installing vendor...');
			$result = $this->taskComposerInstall()
				->dir($this->composer_json_path)
				->run();
		} else
			if ($this->update_vendor) {
				$this->say('Updating vendor...');
				$result = $this->taskComposerUpdate()
					->dir($this->composer_json_path)
					->run();
			}
		return $result;
	}

	protected function phpUnit($phpunit_xml_file) {
		$this->say('Running php unit tests...');
		$this->checkFile($phpunit_xml_file);
		$result = $this->taskPHPUnit('phpunit')
			->
		// ->debug()
		// ->dir(__DIR__ . '/../test/phpunit/unit')
		configFile($phpunit_xml_file)
			->printed(true)
			->run();
		return $result;
	}

	private function readConfig($ini_file) {
		$this->say('Reading config file: ' . $ini_file);
		$this->checkFile($ini_file);
		$this->ini_array = parse_ini_file($ini_file);

		$this->update_vendor = $this->ini_array['update_vendor'];
		$this->composer_json_path = $this->ini_array['composer_json_path'];
		$this->phpunit_xml_file = $this->ini_array['phpunit_xml_file'];
		putenv('PHPUNIT_XML_FILE=' . $this->phpunit_xml_file);

		$this->env_cfg['PHPUNIT_XML_FILE'] = getenv('PHPUNIT_XML_FILE');
		$this->other_cfg['update vendor'] = $this->formatBoolean($this->update_vendor);
		$this->other_cfg['composer_json_path'] = getenv('composer_json_path');
	}

	protected function printConfig() {
		$this->say('--------------------------------------------------');
		$this->say('Enviroment variables');
		$this->say('--------------------------------------------------');
		foreach ($this->env_cfg as $key => $value) {
			$this->say($key . "\t" . $value);
		}
		$this->say('--------------------------------------------------');
		$this->say('Settings');
		$this->say('--------------------------------------------------');
		foreach ($this->other_cfg as $key => $value) {
			$this->say($key . "\t" . $value);
		}
	}

	protected function init() {
		$this->say('Initializing...');
		$this->startTimer();
		$this->readConfig('config.ini');
	}

	/**
	 *
	 * @param unknown $b
	 * @return string
	 */
	protected function formatBoolean($b) {
		if ($b) {
			return 'true';
		} else {
			return 'false';
		}
	}

	protected function formatPassword($secret) {
		$str = '<not set>';
		if ($secret) {
			$str = self::HIDDEN;
		}
		return $str;
	}
}