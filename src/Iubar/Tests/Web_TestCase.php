<?php
namespace Iubar\Tests;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use \League\CLImate\CLImate;

/**
 * Note
 * @see see https://github.com/facebook/php-webdriver/issues/469
 * 1) PHP WebDriver does not support W3C syntax at this time
 * 2) Safari is not working on Saucelabs
 * @see also https://webdriver.io/blog/2019/09/16/devtools.html
 *
 * @author Matteo
 * @global env BROWSER
 * @global env SELENIUM_SERVER
 * @global env SELENIUM_PORT
 * @global env SCREENSHOTS_PATH
 * @global env APP_HOST
 * @global env APP_USERNAME
 * @global env APP_PASSWORD
 * @global env TRAVIS
 * @see : https://gist.github.com/huangzhichong/3284966 Cheat sheet for using php webdriver
 * @see : https://gist.github.com/aczietlow/7c4834f79a7afd920d8f Cheat sheet for using php webdriver
 * @see : https://github.com/facebook/php-webdriver/wiki
 * @see : https://docs.travis-ci.com/user/gui-and-headless-browsers/
 */
abstract class Web_TestCase extends Root_TestCase {

    const DEBUG = false;

    const TAKE_SCREENSHOTS = true;

    // seconds
    const DEFAULT_WAIT_TIMEOUT = 30;

    // the interval in miliseconds
    const DEFAULT_WAIT_INTERVAL = 250;

    // Browser
    const PHANTOMJS = 'phantomjs';

    const CHROME = 'chrome';

    const FIREFOX = 'firefox';

    const MARIONETTE = 'marionette';

    const SAFARI = 'safari';

    const JS_DRAG_SCRIPT = 'js/drag_to_upload_3.js';

    const JS_DELETECOOKIES_SCRIPT = 'js/delete_cookies.js';

    const SAUCELABS = 'saucelabs';

    const BROWSERSTACK = 'browserstack';

    const HIDDEN = '**********';

//     protected static $openLastScreenshot = false;

//     protected static $openLastDumpFile = false;

    protected static $screenshots = array();

    protected static $dump_files = array();

    protected static $webDriver = null;

    protected static $selenium_server_shutdown;

    protected static $selenium_session_shutdown;

    protected static $files_to_del = array();

    protected static $browser = null;

    protected static $browser_version = null;

    protected static $os_version = null;

    protected static $selenium_server = null;

    protected static $selenium_port = null;

    protected static $selenium_path = null;

    protected static $phantomjs_binary = null;

    protected static $logs_path = null;

    protected static $screenshots_path = null;

    protected static $app_host = null;

    protected static $app_username = null;

    protected static $app_password = null;

    protected static $travis_job_number = null;

    protected static $sauce_access_username = null;

    protected static $sauce_access_key = null;

    protected static $browserstack_username = null;

    protected static $browserstack_acces_key = null;

    private static $travis = null;

    /**
     * Start the WebDriver
     */
    public static function setUpBeforeClass() : void {
        self::init();
        self::$browser = getenv('BROWSER');

        // Setting the default enviroment variables when not set

        if (false && !getenv('BROWSER_VERSION')) {
			// On Saucelabs the selenium version depends on the browser verions.
			// https://wiki.saucelabs.com/display/DOCS/Platform+Configurator#/
			// https://saucelabs.com/platform/supported-browsers-devices
            // See https://wiki.saucelabs.com/display/DOCS/Test+Configuration+Options#TestConfigurationOptions-ChromeDriverVersion
            $def_browser_version = "";
            switch (self::$browser) {
                case self::PHANTOMJS:
                    $def_browser_version = "";
                    break;
                case self::CHROME:
                    $def_browser_version = "78.0";
                    break;
                case self::FIREFOX:
                    $def_browser_version = "48.0"; // "48.0" se sistema è "Linux", "70.0" se il sistema è "Windows 10"
                    break;
                case self::MARIONETTE:
                    $def_browser_version = "70.0";
                    break;
                case self::SAFARI:
                    $def_browser_version = "11.0";
                    break;
            }
            putenv('BROWSER_VERSION=' . $def_browser_version);
        }
        if (false && !getenv('OS_VERSION')) {
            $def_os_version = "";
            switch (self::$browser) {
                case self::PHANTOMJS:
                    $def_os_version = "Windows 10";
                    break;
                case self::CHROME:
                    $def_os_version = "Windows 10";
                    break;
                case self::FIREFOX:
                    $def_os_version = "Linux";
                    break;
                case self::MARIONETTE:
                    $def_os_version = "Windows 10";
                    break;
                case self::SAFARI:
                    $def_os_version = "MacOS 10.13";
                    break;
            }
            putenv('OS_VERSION=' . $def_os_version);
        }

        self::printEnviroments();

        self::$browser_version = getenv('BROWSER_VERSION');
        self::$os_version = getenv('OS_VERSION');
        self::$selenium_server = getenv('SELENIUM_SERVER');
        self::$selenium_port = getenv('SELENIUM_PORT');
        self::$phantomjs_binary = getenv('PHANTOMJS_BINARY');
        self::$logs_path = getenv('LOGS_PATH');
        self::$screenshots_path = getenv('SCREENSHOTS_PATH');
        self::$app_host = getenv('APP_HOST');
        self::$app_username = getenv('APP_USERNAME');
        self::$app_password = getenv('APP_PASSWORD');
        self::$travis = getenv('TRAVIS');
        self::$travis_job_number = getenv('TRAVIS_JOB_NUMBER');
        self::$sauce_access_username = getenv('SAUCE_USERNAME');
        self::$sauce_access_key = getenv('SAUCE_ACCESS_KEY');
        self::$browserstack_username = getenv('BROWSERSTACK_USERNAME');
        self::$browserstack_acces_key = getenv('BROWSERSTACK_ACCESS_KEY');

        self::checkPaths();

        $capabilities = null;

        self::$climate->info("Inizializing " . self::$browser . " browser");
        // set capabilities according to the browers
        // @see: https://wiki.saucelabs.com/display/DOCS/Platform+Configurator
        switch (self::$browser) {
            case self::PHANTOMJS:
                $capabilities = DesiredCapabilities::phantomjs();
                $cli_args = array( // TODO: da verificare se svolgono il loro compito
                    "--debug" => "true",
                    "--webdrive" => "",
                    "--loglevel" => "DEBUG",
                    "--webdriver-logfile" => self::$logs_path . "/phantomjsdriver.log"
                );
                $ghostdriver_cli_args = array(
                    "--loglevel" => "DEBUG",
                    "--webdriver-loglevel" => "DEBUG"
                );
                $capabilities->setCapability("phantomjs.ghostdriver.cli.args", $ghostdriver_cli_args);
                $capabilities->setCapability("phantomjs.cli.args", $cli_args);
                $capabilities->setCapability("phantomjs.binary.path", self::$phantomjs_binary);
                $capabilities->setCapability("screenResolution", "1024x768");
                break;
            case self::CHROME:
                $capabilities = DesiredCapabilities::chrome();
                //$options = new ChromeOptions(); // https://github.com/facebook/php-webdriver/wiki/ChromeOptions
                //$options->setExperimentalOption("w3c", true); // https://facebook.github.io/php-webdriver/master/ChromeOptions.html
                $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
                break;
			case self::FIREFOX:
				$capabilities = DesiredCapabilities::firefox();
				$capabilities->setCapability(self::MARIONETTE, false); // Gecko driver (require Firefox 48+)
				// Lo statment sopra sembra essere ignorato da saucelabs.com chenel log riporta sempre il valore "marionette": true
				$capabilities->setCapability('acceptSslCerts', true);
				$capabilities->setCapability('acceptInsecureCerts', true); // https://github.com/SeleniumHQ/selenium/wiki/DesiredCapabilities
				// Senza lo statement sopra, il certificato di Comodo da Firefox 48.0 non verrebbe accettato
				// TODO: Perchè ? E' forse solo una questione di versione di Firefox ?
                break;
            case self::MARIONETTE:
                $capabilities = DesiredCapabilities::firefox();
                $capabilities->setCapability(self::MARIONETTE, true); // Marionette driver
                // OPZIONALE: $capabilities->setCapability('firefox_binary', 'C:/Program Files (x86)/Firefox Developer Edition/firefox.exe');
                // Useful to use the portable version of Firefox
                break;
            case self::SAFARI:
                if (self::isWindows()) {
                    $error = 'Can\'t test with Safari on Windows Os';
                    self::$climate->error($error);
                    exit(1);
                }
                $capabilities = DesiredCapabilities::safari();
                $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
                // $capabilities.setCapability("version", "11.0");
                // DOESN'T WORK: $capabilities->setCapability('options', array("cleanSession"=>"true"));
                // see: https://github.com/SeleniumHQ/selenium/wiki/DesiredCapabilities#safari-specific
                break;
            default:
                self::$climate->error("Browser '" . self::$browser . "' not supported. (you should set the BROWSER global var with a supported browser name)");
                exit(1);
        }

        if (self::$browser_version) {
            $capabilities->setCapability("version", self::$browser_version);
        }
        if (self::$os_version) {
            $capabilities->setCapability("platform", self::$os_version);
        }

        // create the WebDriver
        $connection_timeout_in_ms = 10 * 1000; // Set the maximum time of a request
        $request_timeout_in_ms = 20 * 1000; // Set the maximum time of a request

        $server_root = null;
        // set Travis params
        if (self::$travis) {
            self::$climate->info("Travis detected...");
            $msg = "WebDriver test";
            if (self::isSaucelabs()) {
				self::$climate->info("isSaucelabs()...true");
                $capabilities->setCapability('tunnel-identifier', self::$travis_job_number);
                $capabilities->setCapability('name', $msg);
                $username = self::$sauce_access_username;
                $access_key = self::$sauce_access_key;
            } else if (self::isBrowserstack()) {
				self::$climate->info("isBrowserstack()...true");
                $capabilities->setCapability('browserstack.debug', true);
                $capabilities->setCapability('browserstack.local', true);
                $capabilities->setCapability('browserstack.localIdentifier', $msg);
                $capabilities->setCapability('takesScreenshot', true);
                $username = self::$browserstack_username;
                $access_key = self::$browserstack_acces_key;
            }
            $server_root = "http://" . $username . ":" . $access_key . "@" . self::$selenium_server; // Attention: never print-out this string.
            $server_printable = "http://" . self::HIDDEN . ":" . self::HIDDEN . "@" . self::$selenium_server;
        } else {
            $server_root = "http://" . self::$selenium_server;
            $server_printable = $server_root;
        }
        if (self::$selenium_port) {
            $server_root = $server_root . ":" . self::$selenium_port;
            $server_printable = $server_printable . ":" . self::$selenium_port;
        }
        self::$selenium_server_shutdown = $server_root . '/selenium-server/driver/?cmd=shutDownSeleniumServer';
        self::$selenium_session_shutdown = $server_root . '/selenium-server/driver/?cmd=shutDown';
        $server = $server_root . "/wd/hub"; // Attention: never print-out this string.

        self::$climate->info("Server: " . $server_printable);

        try {

			self::$climate->info("RemoteWebDriver::create()...");
            self::$webDriver = RemoteWebDriver::create($server, $capabilities, $connection_timeout_in_ms, $request_timeout_in_ms); // This is the default

			self::$climate->info("pageLoadTimeout()...");



			if (self::$browser != self::SAFARI) {
			// set some timeouts
        self::$webDriver->manage()
            ->timeouts()
            ->pageLoadTimeout(60); // Set the amount of time (in seconds) to wait for a page load to complete before throwing an error

			self::$climate->info("setScriptTimeout()...");
        self::$webDriver->manage()
            ->timeouts()
            ->setScriptTimeout(240); // Set the amount of time (in seconds) to wait for an asynchronous script to finish execution before throwing an error.

			}

        // Window size $self::$webDriver->manage()->window()->maximize(); $window = new WebDriverDimension(1024, 768); $this->webDriver->manage()->window()->setSize($window);


		// 1) Marionetteis the new driver that is shipped/included with Firefox.
		// This driver has it's own protocol which is not directly compatible with the Selenium/WebDriver protocol.
		// 2) The Gecko driver (previously named wires) is an application server implementing
		// the Selenium/WebDriver protocol. It translates the Selenium commands and forwards them to the Marionette driver.
		//
		// In other words:
		//
		// Selenium uses W3C Webdriver protocol to send requests to Geckodriver, which translates them and uses Marionette protocol to send them to Firefox
		// Selenium<--(W3C Webdriver)-->Geckodriver<---(Marionette)--->Firefox


		if (self::$browser == self::CHROME) { 	// Write avaiable browser logs (works only on Chrome)
            // Console
			self::$climate->info("invoking getAvailableLogTypes()...");
            $types = self::$webDriver->manage()->getAvailableLogTypes();
            if (self::DEBUG) {
                self::$climate->info('Avaiable browser logs types:');
                self::$climate->out($types);
                $input = self::$climate->input('Press Enter to continue');
                $response = $input->prompt();
            }
		}else{
			self::$climate->info("Skipping getAvailableLogTypes()...");
		}

		} catch (\Exception $e) {
			self::$climate->error("Exception: " . $e->getMessage());
			self::$climate->error("QUIT.");
			exit(1);
		}

    }

    protected static function isSaucelabs(){
        $b = false;
        $findme   = 'saucelabs';
        $pos = strpos(self::$selenium_server, $findme);
        if ($pos !== false) {
            $b = true;
        }
        return $b;
    }

    protected static function isBrowserstack(){
        $b = false;
        $findme   = 'browserstack';
        $pos = strpos(self::$selenium_server, $findme);
        if ($pos !== false) {
            $b = true;
        }
        return $b;
    }

    /**
     * Close the WebDriver and show the screenshot in the browser
     */
    public static function tearDownAfterClass() : void {
        if (self::$browser != self::PHANTOMJS) {
            self::$climate->info('Closing all windows...');
            self::closeAllWindows();
        }
        self::$climate->info('Quitting webdriver...');
        self::$webDriver->quit();

        $screenshots_count = count(self::$screenshots);
        self::$climate->comment('putenv SCREENSHOTS_COUNT=' . $screenshots_count);
        putenv('SCREENSHOTS_COUNT=' . $screenshots_count);

        $dumpfile_count = count(self::$dump_files);
        if ($dumpfile_count) {
            $json = json_encode(self::$dump_files);
            self::$climate->comment('putenv DUMP_FILES=' . $json);
            putenv("DUMP_FILES=" . $json);
        }

        // delete all temp files
        foreach (self::$files_to_del as $file) {
            self::$climate->comment('Deleting file ' . $file);
            unlink($file);
        }

    }

    /**
     */
    protected static function printEnviroments() {
        self::$climate->underline()->bold("Enviroment variables for PhpUnit");

        // @see https://github.com/thephpleague/climate/issues/9 (search for: yparisien)

        $padding = self::$climate->padding(25);
        $padding->label('LOGS_PATH: ')->result(getenv("LOGS_PATH"));
        $padding->label('SCREENSHOTS_PATH: ')->result(getenv("SCREENSHOTS_PATH"));
        $padding->label('SELENIUM SERVER: ')->result(getenv("SELENIUM_SERVER"));
        $padding->label('SELENIUM_PORT: ')->result(getenv("SELENIUM_PORT"));
        $padding->label('BROWSER: ')->result(getenv("BROWSER"));
        $padding->label('BROWSER VERSION: ')->result(getenv("BROWSER_VERSION"));
        $padding->label('OS VERSION: ')->result(getenv("OS_VERSION"));
        $padding->label('APP_HOST: ')->result(getenv("APP_HOST"));
        $padding->label('APP_USERNAME: ')->result(getenv("APP_USERNAME"));
        $padding->label('APP_PASSWORD: ')->result(self::formatPassword(getenv("APP_PASSWORD")));
        $padding->label('TRAVIS: ')->result(getenv("TRAVIS"));
        $padding->label('TRAVIS_JOB_NUMBER: ')->result(getenv("TRAVIS_JOB_NUMBER"));
        $padding->label('SAUCE_USERNAME: ')->result(getenv("SAUCE_USERNAME"));
        $padding->label('SAUCE_ACCESS_KEY: ')->result(self::formatPassword(getenv("SAUCE_ACCESS_KEY")));
        $padding->label('BROWSERSTACK_USERNAME: ')->result(getenv("BROWSERSTACK_USERNAME"));
        $padding->label('BROWSERSTACK_ACCESS_KEY: ')->result(self::formatPassword(getenv("BROWSERSTACK_ACCESS_KEY")));
        $padding->label('PHANTOMJS_BINARY: ')->result((getenv("PHANTOMJS_BINARY")));
    }

    /**
     */
    protected static function checkPaths() {
        if (self::$browser == self::PHANTOMJS) {
            self::checkFile(self::$phantomjs_binary);
        }
        if (self::$logs_path) {
            self::isPathWritable(self::$logs_path);
        }
        if (self::TAKE_SCREENSHOTS) {
            if (self::$browser != self::PHANTOMJS) {
                if (self::$screenshots_path) {
                    self::isPathWritable(self::$screenshots_path);
                }
            }
        }
    }

    /**
     * Close all browser windows
     */
    protected static function closeAllWindows() {
        $wd = self::$webDriver;
        $handlers = $wd->getWindowHandles();
        // close each tabs of the browser
        foreach ($handlers as $handler) {
            $wd->switchTo()->window($handler);
            $wd->close();
        }
    }

    /**
     * Start a shell and execute the command
     *
     * @param string $cmd the command to execute
     * @return string the output command
     */
    protected static function startShell($cmd) {
        self::$climate->comment('Command is : ' . $cmd);
        $output = shell_exec($cmd);
        return $output;
    }

    /**
     * Open the browser at the specific url
     *
     * @param string $url the url
     */
    protected static function openBrowser($url) {
        $browser = self::$browser;
        self::$climate->info('Opening browser at: ' . $url);
        if (self::$browser == self::PHANTOMJS) {
            $browser = null;
        } else {
            if (self::$browser == self::MARIONETTE) {
                $browser = null;
            }
        }
        if (self::isWindows()){
            if($browser){
                $cmd = "start \"\" \"$browser $url\"";;  // opening the same browser that was choosen for the test
            }else{
                $cmd = "start \"\" $url"; // opening the default system browser
            }
        }else{
            $error = 'Warning: Linux Os not supported';
            self::$climate->error($error);
            exit(1);
        }
        self::startShell($cmd);
    }

    /**
     *
     * @param unknown $file
     */
    protected static function openFile($file) {
        if (self::isWindows()){
            $cmd = "start \"\" \"$file\"";
            self::$climate->info('Command is : ' . $cmd);
        }else{
            $error = 'Warning: Linux Os not supported';
            self::$climate->error($error);
            exit(1);
        }
        self::startShell($cmd);
    }

    /**
     * Return if the Os is Windows
     *
     * @return boolean true if the Os is Windows
     */
    protected static function isWindows() {
        $b = false;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $b = true;
        }
        return $b;
    }

    /**
     *
     * @param unknown $env
     * @return string
     */
    private static function formatPassword($env) {
        $str = '<not set>';
        if ($env) {
            $str = self::HIDDEN;
        }
        return $str;
    }

    /**
     * This method is called when a test method did not execute successfully
     *
     * @param $e
     */
    public function onNotSuccessfulTest($e) : void {
        // reduced the error message
        $msg = $this->formatErrorMsg($e);
        self::$climate->error("EXCEPTION: " . $msg);
        if (self::TAKE_SCREENSHOTS) {
            if (self::$browser != self::PHANTOMJS) {
                $this->takeScreenshot($msg);
            }
        }
        parent::onNotSuccessfulTest($e);
    }

    /**
     * Return true is Travis is set
     *
     * @return string true is Travis is set
     */
    protected function isTravis() {
        return self::$travis;
    }

    /**
     * Return the WebDriver
     *
     * @return RemoteWebDriver
     */
    protected function getWd() {
        return self::$webDriver;
    }

    /**
     * Click on a element that has the corresponding xpath and wait some time if the element is not immediately present
     *
     * @param string $xpath the xpath of the element
     * @param real $wait the time to wait
     */
    protected function click($xpath, $wait = 0.50) {
        $this->getWd()
            ->findElement(WebDriverBy::xpath($xpath))
            ->click();

        $this->getWd()
            ->manage()
            ->timeouts()
            ->implicitlyWait($wait);  // FIXME: non compatibile con Safari (soluzione https://github.com/facebook/php-webdriver/wiki/HowTo-Wait)
    }

    /**
     * Wait at most $timeout seconds until at least one result is shown
     *
     * @param string $id the id of the element
     * @param int $timeout the timeout in seconds
     */
    protected function waitForId($id, $timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id($id)));
    }

    /**
     * Wait at most $timeout seconds until at least one result is shown
     *
     * @param string $xpath the xpath of the element
     * @param int $timeout the timeout in seconds
     */
    protected function waitForXpath($xpath, $timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath($xpath)));
    }

    /**
     *
     * @param string $xpath
     * @param float $timeout
     */
    protected function waitForXpathToBeClickable($xpath, $timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::xpath($xpath)));

        // TODO: Verificare differenze con
        // $wait = new WebDriverWait($wd, $timeout, self::DEFAULT_WAIT_INTERVAL);
        // $wait->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::xpath($xpath)));
    }

    /**
     * Wait at most $timeout seconds until at least one result is shown
     *
     * @param string $tag the tag of the element
     * @param int $timeout the timeout in seconds
     */
    protected function waitForTag($tag, $timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::tagName($tag)));
    }

    /**
     * Wait at most $timeout seconds until at least one result is shown
     *
     * @param string $css the css of the element
     * @param int $timeout the timeout in seconds
     */
    protected function waitForCss($css, $timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($css)));
    }

    /**
     *
     * @param string $css
     * @param float $timeout
     */
    protected function waitForCssToBeClickable($css, $timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::cssSelector($css)));
    }

    /**
     * Wait at most $timeout seconds until at least one result is shown
     *
     * @param string $className the class name of the element
     * @param int $timeout the timeout in seconds
     */
    protected function waitForClassName($className, $timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::className($className)));
    }

    /**
     * Wait at most $timeout seconds until at least one result is shown
     *
     * @param int $timeout the timeout in seconds
     */
    protected function waitForEmailLink($timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $partial_link_text = "mailto:";
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::partialLinkText($partial_link_text)));
    }

    /**
     * waitForAjax : wait for all ajax request to close
     *
     * @param int $timeout the timeout in seconds
     * @return void
     */
    protected function waitForAjax($timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(function () {
            // jQuery: "jQuery.active" or $.active
            // Prototype: "Ajax.activeRequestCount"
            // Dojo: "dojo.io.XMLHTTPTransport.inFlight.length"
            $condition = 'return ($.active == 0);';
            return $this->getWd()
                ->executeScript($condition);
        });
    }

    /**
     * waitForAjax : wait for all ajax request to close
     *
     * @param int $timeout the timeout in seconds
     */
    protected function waitAjaxLoad2($timeout = 10) {
        $this->getWd()->waitForJS('return !!window.jQuery && window.jQuery.active == 0;', $timeout);
        $this->getWd()->wait(1);
    }

    /**
     * Wait at most $timeout seconds until at least one result is shown
     *
     * @param string $tag the tag
     * @param string $substr the text of the tag
     * @param int $timeout the timeout in seconds
     */
    protected function waitForTagWithText($tag, $substr, $timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(WebDriverExpectedCondition::textToBePresentInElement(WebDriverBy::tagName($tag), $substr));
    }

    /**
     *
     * @param string $txt
     * @param float $timeout
     */
    protected function waitForPartialLinkText($txt, $timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::partialLinkText($txt)));
    }

    /**
     *
     * @param string $txt
     * @param float $timeout
     */
    protected function waitForPartialLinkTextToBeClickable($txt, $timeout = self::DEFAULT_WAIT_TIMEOUT) {
        $this->getWd()
            ->wait($timeout, self::DEFAULT_WAIT_INTERVAL)
            ->until(WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::partialLinkText($txt)));
    }

    /**
     * Assert that an element was not found
     *
     * @param string $by the element
     */
    protected function assertElementNotFound($by) {
        $els = $this->getWd()->findElements($by);
        if (count($els)) {
            $this->fail("Unexpectedly element was found");
        }
        $this->assertTrue(true);
    }

    /**
     * Wait for user input
     */
    protected function waitForUserInput() {
        if (trim(fgets(fopen("php://stdin", "r"))) != chr(13)) // chr(13) == "\n"
            return;
    }

    /**
     */
    protected function deleteAllCookies() { // TODO: metodo da testare
        $arguments = array();
        $this->getWd()->executeScript($this->get_js_contents(self::JS_DELETECOOKIES_SCRIPT), $arguments);
    }

    /**
     * Click on the drop area and execute the js script to upload a file
     *
     * @fixme: la soluzione seguente è incompatibile con MARIONETTE e SAFARI
     *
     * @param string $drop_area the area to click where upload the file
     * @param string $file the file to upload
     */
    protected function dragFileToUpload($drop_area, $file) {

        // check the drop area
        if (!$drop_area) {
            $this->fail("\$drop_area is null" . PHP_EOL);
        }

        // check the file
        self::checkFile($file);

        $wd = $this->getWd();

        $file = realpath($file);

        // Execute the js drag file script
        // @see also: https://github.com/facebook/php-webdriver/blob/787e71db74e42cdf13a41d500f75ea43da84bc75/tests/functional/FileUploadTest.php
        $return = $wd->executeScript($this->get_js_contents(self::JS_DRAG_SCRIPT), array($drop_area));

        self::$climate->info("Waiting the js script execution...");
        $wd->manage()
            ->timeouts()
            ->implicitlyWait(3);  // FIXME: non compatibile con Safari (soluzione https://github.com/facebook/php-webdriver/wiki/HowTo-Wait)

        $file_input = $wd->findElement(WebDriverBy::id("upload")); // return an RemoteWebElement obj
                                                                   // "upload" is the id of the input tag added by the js script

        if (!$file_input) {
            $this->fail("\$file_input is null" . PHP_EOL);
        } else {
            // upload the file
            self::$climate->info("Uploading file: " . $file);

            $file_input->setFileDetector(new LocalFileDetector())->sendKeys($file); // Soluzione incompatibile con il browser MARIONETTE
                                                                                    // https://bugzilla.mozilla.org/show_bug.cgi?id=941085
                                                                                    // Lo statement è equivalente a $file_input->sendKeys($file);

        }
    }

    /**
     * Shutdown Selenium Server Metodo non utilizzato. L'azione è delegata allo script che avvia il test.
     */
    protected function quitSelenium() {
		self::$climate->info('Quitting Selenium...');
		$selenium_shutdown_url = null; // TODO: valorizzare
        self::openBrowser($selenium_shutdown_url);
    }

    /**
     * Clear the log buffer of the browser, log buffer is reset after each request
     */
    protected function clearBrowserConsole() {
        if (self::$browser == self::CHROME) { // Write avaiable browser logs (works only on Chrome)
            $this->getWd()
                ->manage()
                ->getLog('browser');
        } else {
            self::$climate->error("Warning: can't use clearBrowserConsole() with " . self::$browser);
        }
    }

    /**
     * Assert that the browser's console has $n errors
     *
     * @param int $n the number of the errors you want DEFAULT:zero
     */
    protected function assertErrorsOnConsole($n = 0) {
        $console_error = $this->countErrorsOnConsole();
        self::$climate->info('Errors on console: ' . $console_error);
        $this->assertEquals($n, $console_error);
    }

    /**
     * Assert that the browser's console has zero errors
     */
    protected function assertNoErrorsOnConsole() {
        return $this->assertErrorsOnConsole(0);
    }

    /**
     *
     * @return boolean
     */
    protected function isFirefoxOnSaucelabs() {
        return (self::$browser == self::FIREFOX && self::$sauce_access_key);
    }

    /**
     *
     * @return boolean
     */
    protected function isChromeOnSaucelabs() {
        return (self::$browser == self::CHROME && self::$sauce_access_key);
    }

    /**
     * Search if the browser's console has error
     *
     * @return number $console_error
     */
    protected function countErrorsOnConsole() {
        $console_error = 0;
        if (self::$browser == self::CHROME) { 	// Write avaiable browser logs (works only on Chrome)

            $wd = $this->getWd();
            $records = $wd->manage()->getLog('browser');

            $severe_records = array();

            // search for the error in the console
            foreach ($records as $record) {
                if ($record['level'] == 'SEVERE') {
                    if (!self::shouldSkip($record['message'])) {
                        $severe_records[] = $record;
                    }
                }
            }
            $console_error = count($severe_records);

            if($console_error>0){
                self::$climate->yellow()->blink()->out("Errors on console: " . $console_error . ". Url is: " . $wd->getCurrentURL());
                // write the console error in log file
                if (!$this->isTravis()) {
                    $this->dumpConsoleError($severe_records);
                }else{
                    self::$climate->dump($severe_records);
                }
            }

        } else {
            self::$climate->error("Warning: can't use countErrorsOnConsole() with the browser " . self::$browser);
        }
        return $console_error;
    }

    /**
     * Write the console error in log file
     *
     * @param string $records the errors' record
     */
    private function dumpConsoleError($records) {
        $data = json_encode($records, JSON_PRETTY_PRINT);
        $logs_path = self::$logs_path;
        if ($logs_path && self::isPathWritable($logs_path)){
            $dump_file = $logs_path . DIRECTORY_SEPARATOR . date('Y-m-d_His') . "_console.json";
            file_put_contents($dump_file, $data);
            self::$dump_files[] = $dump_file;
        }
    }

    /**
     * Control if an error from the browser console log must be skipped
     *
     * @param string $message the message
     * @return boolean true if the error should be skipped
     */
    private function shouldSkip($message) {
        $array = array(
            "Error reading remote data. Status is 0"
        );
        foreach ($array as $findme) {
            $pos = strpos($message, $findme);
            if ($pos !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reduced the error msg until the first '\n'
     *
     * @param string $e the error message
     * @return string the reduced error message
     */
    private function formatErrorMsg($e) {
        $msg = $e->getMessage();
        $array = explode('\n', $msg);
        $msg = $array[0];
        if (count($array) > 1) {
            $msg = $msg . "...";
        }
        return $msg;
    }

    /**
     * Take a screenshot of the webpage
     *
     * @param string $element the element to capture
     */
    private function takeScreenshot($msg, $element = null) {
        $screenshots_path = self::$screenshots_path;
        if ($screenshots_path && self::isPathWritable($screenshots_path)) {
            self::$climate->error('Taking a screenshot...');

            // The path where save the screenshot
            $save_as = $screenshots_path . DIRECTORY_SEPARATOR . date('Y-m-d_His') . ".png";
            // $this->getWd()->takeScreenshot($save_as);
            $this->takeScreenshot2($msg, $element, $save_as);

            if (!file_exists($save_as)) {
                $error = 'Error saving the screenshot file: ' . $save_as;
                self::$climate->error($error);
                exit(1);
            }
            self::$screenshots[] = $save_as;
        }
    }

    /**
     * Create the screenshot with an error message on the bottom of the image
     *
     * @param string $msg the message
     * @param string $element the element to capture
     * @param string $save_as the path where save the screenshot
     */
    private function takeScreenshot2($msg, $element, $save_as = null) {
        $screenshot = base64_decode($this->getWd()->execute(DriverCommand::SCREENSHOT));
        $im = imagecreatefromstring($screenshot);

        if ($element) { // Cut the element from the image
            $element_width = $element->getSize()->getWidth();
            $element_height = $element->getSize()->getHeight();
            $element_src_x = $element->getLocation()->getX();
            $element_src_y = $element->getLocation()->getY();

            // Create image instances
            $dest = imagecreatetruecolor($element_width, $element_height);

            // Copy
            imagecopy($dest, $im, 0, 0, $element_src_x, $element_src_y, $element_width, $element_height);
            imagedestroy($im);
            $im = $dest;

        }

        // Add the text message to the image
        $im = $this->addTextToimage($im, $msg);

        if ($save_as) {
            // output the image to file
            imagepng($im, $save_as);
        }

        // tidy up
        imagedestroy($im);
    }

    private function addTextToimage($im, $msg){
        // define some colours to use with the image
        $yellow = imagecolorallocate($im, 255, 255, 0);
        $black = imagecolorallocate($im, 0, 0, 0);

        // get the width and the height of the image
        $width = imagesx($im);
        $height = imagesy($im);

        // Split the message in two lines
        // $msg = wordwrap($msg, 7, "\n");
        $first = null;
        $second = null;
        $box_height = 25;
        $box_inner_height = 24;
        if (strlen($msg) > 80) {
            $pos = strlen($msg) / 2;
            $first = substr($msg, 0, $pos);
            $second = substr($msg, $pos + 1);
            $box_height = $box_height * 2;
            $box_inner_height = $box_inner_height * 2;
        } else {
            $first = $msg;
        }

        // draw a black rectangle across the bottom, say, 50 pixels of the image:
        imagefilledrectangle($im, 0, ($height - $box_height), $width, $height, $black);

        // now we want to write in the centre of the rectangle:
        $font = 24; // store the int ID of the system font we're using in $font

        // calculate the left position of the text:
        $leftTextPos1 = ($width - imagefontwidth($font) * strlen($first)) / 2;
        if ($second) {
            $leftTextPos2 = ($width - imagefontwidth($font) * strlen($second)) / 2;
        }

        // finally, write the string:
        imagestring($im, $font, $leftTextPos1, $height - $box_inner_height, $first, $yellow);
        if ($second) {
            imagestring($im, $font, $leftTextPos2, $height - ($box_inner_height / 2), $second, $yellow);
        }

        return $im;
    }

    /**
     *
     * @return string
     */
    private function getJsPath() {
        return __DIR__ . "/../../..";
    }

    /**
     *
     * @param string $js_file
     * @return string
     */
    private function get_js_contents($js_file) {
        $js_file = $this->getJsPath() . DIRECTORY_SEPARATOR . $js_file;
        self::checkFile($js_file);
        $script = file_get_contents($js_file);
        return $script;
    }

    /**
     * Write into the respective field
     *
     * @param string $id the id of the elem
     * @param string $sendKey what do you wanna write in the elem
     */
    protected function fillField($id, $sendKey) {
        $wd = $this->getWd();
        $this->waitForId($id); // Wait until the element is visible
        $elem = $wd->findElement(WebDriverBy::id($id));
        $elem->sendKeys($sendKey);
        $this->assertNotNull($elem);
        $this->assertStringContainsStringIgnoringCase($sendKey, $elem->getText());
    }

    /**
     * Take a temporary directory
     *
     * @return string the temporary directory
     */
    protected function getTmpDir() {
        $tmp_dir = sys_get_temp_dir();
        if ($this->isTravis()) {
            $tmp_dir = __DIR__;
        }
        if (!is_writable($tmp_dir)) {
            $this->fail("Temp dir not writable: " . $tmp_dir);
        }
        return $tmp_dir;
    }


}
