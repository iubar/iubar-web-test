<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
namespace Iubar\Build;

class Guzzle_RoboTask extends Root_RoboTask {
	
	private $phpunit_xml_file = null;

	public function __construct($working_path) {
		parent::__construct($working_path);
	}

	public function test($type = 'local-dev') {
		parent::init();
		$this->config($type);
		$this->printConfig();
		$this->composer();
		$this->phpUnit($this->phpunit_xml_file);
	}
}