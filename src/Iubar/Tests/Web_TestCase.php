<?php
namespace Iubar\Tests;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\LocalFileDetector;
use \League\CLImate\CLImate;

/**
 * PHPUnit_Framework_TestCase Develop
 *
 * @author Matteo
 * @global env BROWSER
 * @global env SELENIUM_SERVER
 * @global env SCREENSHOTS_PATH
 * @global env APP_HOST
 * @global env APP_USERNAME
 * @global env APP_PASSWORD
 * @global env TRAVIS
 * @see : https://gist.github.com/huangzhichong/3284966 Cheat sheet for using php webdriver
 * @see : https://gist.github.com/aczietlow/7c4834f79a7afd920d8f Cheat sheet for using php webdriver
 * @see : https://github.com/facebook/php-webdriver/wiki
 */
class Web_TestCase extends Root_TestCase {

    const DEBUG = false;

    const TAKE_SCREENSHOTS = true;
    
    // seconds
    const DEFAULT_WAIT_TIMEOUT = 15;
    
    // the interval in miliseconds
    const DEFAULT_WAIT_INTERVAL = 1000;
    
    // Browser
    const PHANTOMJS = 'phantomjs';

    const CHROME = 'chrome';

    const FIREFOX = 'firefox';

    const MARIONETTE = 'marionette';

    const SAFARI = 'safari';

    const JS_DRAG_SCRIPT = 'js/drag.js';
    
    const JS_DELETECOOKIES_SCRIPT = 'js/delete_cookies2.js';
    
    const SAUCELABS = 'saucelabs';
    
    const BROWSERSTACK = 'browserstack';
    
    // default is 4444
    public static $port = null;

    protected static $openLastScreenshot = true;

    protected static $openLastDumpFile = true;

    protected static $screenshots = array();

    protected static $dump_files = array();

    protected static $webDriver = null;

    protected static $selenium_server_shutdown;

    protected static $selenium_session_shutdown;
    
    // easily output colored text and special formatting
    protected static $climate;

    protected static $files_to_del = array();

    protected static $browser = null;

    protected static $selenium_server = null;

    protected static $selenium_path = null;

    protected static $logs_path = null;
    
    protected static $screenshots_path = null;

    protected static $app_host = null;

    protected static $app_username = null;

    protected static $app_password = null;

    private static $travis = null;
    
    protected static $travis_job_number = null;
    
    protected static $browser_testing_tool = null;
    
    protected static $sauce_access_username = null;
    
    protected static $sauce_access_key = null;
    
    protected static $browserstack_username = null;
    
    protected static $browserstack_acces_key = null;
    
    protected static function printEnviroments() {
        echo PHP_EOL . "Enviroment variables for PhpUnit" . PHP_EOL . PHP_EOL;
        echo "LOGS_PATH: " . getenv("LOGS_PATH") . PHP_EOL;
        echo "SCREENSHOTS_PATH: " . getenv("SCREENSHOTS_PATH") . PHP_EOL;
        echo "SELENIUM SERVER: " . getenv("SELENIUM_SERVER") . PHP_EOL;
        echo "BROWSER:  " . getenv("BROWSER") . PHP_EOL;
        echo "APP_HOST: " . getenv("APP_HOST") . PHP_EOL;
        echo "APP_USERNAME: " . getenv("APP_USERNAME") . PHP_EOL;
        echo "APP_PASSWORD: " . self::formatPassword(getenv("APP_PASSWORD")) . PHP_EOL;
        echo "TRAVIS: " . getenv("TRAVIS") . PHP_EOL;
        echo "TRAVIS_JOB_NUMBER: " . getenv("TRAVIS_JOB_NUMBER") . PHP_EOL;
        echo "BROWSER_TESTING_TOOL: " . getenv("BROWSER_TESTING_TOOL") . PHP_EOL;
        echo "SAUCE_USERNAME: " . getenv("SAUCE_USERNAME") . PHP_EOL;
        echo "SAUCE_ACCESS_KEY: " . self::formatPassword(getenv("SAUCE_ACCESS_KEY")) . PHP_EOL;
        echo "BROWSERSTACK_USERNAME: " . getenv("BROWSERSTACK_USERNAME") . PHP_EOL;
        echo "BROWSERSTACK_ACCESS_KEY: " . self::formatPassword(getenv("BROWSERSTACK_ACCESS_KEY")) . PHP_EOL;
        
        self::checkPath(getenv("LOGS_PATH"));
        self::checkPath(getenv("SCREENSHOTS_PATH"));
    }  

    /**
     * Start the WebDriver
     */
    public static function setUpBeforeClass() {
        
        self::printEnviroments();
        
        self::$climate = new CLImate();
        
        self::$browser = getenv('BROWSER');
        self::$selenium_server = getenv('SELENIUM_SERVER');
        self::$logs_path = getenv('LOGS_PATH');
        self::$screenshots_path = getenv('SCREENSHOTS_PATH');
        self::$app_host = getenv('APP_HOST');
        self::$app_username = getenv('APP_USERNAME');
        self::$app_password = getenv('APP_PASSWORD');
        self::$travis = getenv('TRAVIS');
        self::$travis_job_number = getenv('TRAVIS_JOB_NUMBER');
        self::$browser_testing_tool = getenv('BROWSER_TESTING_TOOL');
        self::$sauce_access_username = getenv('SAUCE_USERNAME');
        self::$sauce_access_key = getenv('SAUCE_ACCESS_KEY');
        self::$browserstack_username = getenv('BROWSERSTACK_USERNAME');
        self::$browserstack_acces_key = getenv('BROWSERSTACK_ACCESS_KEY');
        
        // Usage with SauceLabs:
        // set on Travis: SAUCE_USERNAME and SAUCE_ACCESS_KEY
        // set on .tavis.yml and env.bat: SELENIUM_SERVER (hostname + port, without protocol);
        
        // check if you can take screenshots and path exist
        if (self::TAKE_SCREENSHOTS) {
            if (self::$browser != self::PHANTOMJS) {
                $screenshots_path = self::$screenshots_path;
                if ($screenshots_path && !is_writable($screenshots_path)) {
                    die("ERRORE percorso non scrivibile: " . $screenshots_path . PHP_EOL);
                }
            }
        }
        
        $capabilities = null;
        
        // set capabilities according to the browers
        // @see: https://wiki.saucelabs.com/display/DOCS/Platform+Configurator
        switch (self::$browser) {
            case self::PHANTOMJS:
                echo "Inizializing PhantomJs browser" . PHP_EOL;
                $capabilities = DesiredCapabilities::phantomjs();                
                // $capabilities->setCapability("phantomjs.cli.args", "['--webdriver-logfile=" . $this->logs_path . "phantomjsdriver.log']");       // TODO: da verificare se svolge il suo compito           
                break;
            case self::CHROME:
                echo "Inizializing Chrome browser" . PHP_EOL;
                $capabilities = DesiredCapabilities::chrome();
                // $capabilities->setCapability("version", "51.0");
                // $capabilities->setCapability("platform", "Windows 10");
                break;
            case self::FIREFOX:
                echo "Inizializing Firefox browser" . PHP_EOL;
                $capabilities = DesiredCapabilities::firefox();
                // $capabilities->setCapability("version", "46.0");
                // $capabilities->setCapability("platform", "Windows 10");
                break;
            case self::MARIONETTE:
                echo "Inizializing Marionette browser" . PHP_EOL;
                $capabilities = DesiredCapabilities::firefox();
                $capabilities->setCapability(self::MARIONETTE, true);
                // $capabilities->setCapability('firefox_binary', 'C:/Program Files (x86)/Firefox Developer Edition/firefox.exe');
                break;
            case self::SAFARI:
                if (self::isWindows()) {
                    $this->fail("Can't test with Safari on Windows Os" . PHP_EOL);
                }
                $capabilities = DesiredCapabilities::safari();
                $capabilities->setCapability('platform', 'OS X 10.11');
                $capabilities->setCapability('version', '9.0');
                $capabilities->setCapability('safari.options', '{cleanSession: true}'); // TODO: da verificare se svolge il suo compito
                break;
            default:
                $error = "Browser '" . self::$browser . "' not supported.";
                $error .= PHP_EOL . "(you should set the BROWSER global var with a supported browser name)";
                die("ERROR: " . $error . PHP_EOL);
        }
        
        // create the WebDriver
        $connection_timeout_in_ms = 10 * 1000; // Set the maximum time of a request
        $request_timeout_in_ms = 20 * 1000; // Set the maximum time of a request
        
        $server_root = null;
        // set Travis params
        if (self::$travis) {
            echo "Travis detected..." . PHP_EOL;
            if (self::$browser_testing_tool) {
                if (self::$browser_testing_tool == self::SAUCELABS) {
                    $capabilities->setCapability('tunnel-identifier', self::$travis_job_number);
                    $capabilities->setCapability('name', 'ANTANI');
                    $username = self::$sauce_access_username ;
                    $access_key = self::$sauce_access_key;
                } else  if (self::$browser_testing_tool == self::BROWSERSTACK) {
                    $capabilities->setCapability('browserstack.debug', true);
                    $capabilities->setCapability('browserstack.local', true);
                    $capabilities->setCapability('browserstack.localIdentifier', 'change me');
                    $capabilities->setCapability('takesScreenshot', true);
                    $username = self::$browserstack_username;
                    $access_key = self::$browserstack_acces_key;
                }
                $server_root = "http://" . $username . ":" . $access_key . "@" . self::$selenium_server; // Attention: never print-out this string.
            }
            
        } else {
            $server_root = "http://" . self::$selenium_server;
        }
        if (self::$port) {
            $server_root = $server_root . ":" . self::$port;
        }
        self::$selenium_server_shutdown = $server_root . '/selenium-server/driver/?cmd=shutDownSeleniumServer';
        self::$selenium_session_shutdown = $server_root . '/selenium-server/driver/?cmd=shutDown';
        $server = $server_root . "/wd/hub";
        echo "Server: " . $server . PHP_EOL;
        
        try {
            self::$webDriver = RemoteWebDriver::create($server, $capabilities, $connection_timeout_in_ms, $request_timeout_in_ms); // This is the default
        } catch (\Exception $e) {
            $error = "Exception: " . $e->getMessage();
            die($error . PHP_EOL);
        }
        
        // set some timeouts
        self::$webDriver->manage()
            ->timeouts()
            ->pageLoadTimeout(60); // Set the amount of time (in seconds) to wait for a page load to complete before throwing an error
        self::$webDriver->manage()
            ->timeouts()
            ->setScriptTimeout(240); // Set the amount of time (in seconds) to wait for an asynchronous script to finish execution before throwing an error.
        
        /*
         * Window size $self::$webDriver->manage()->window()->maximize(); $window = new WebDriverDimension(1024, 768); $this->webDriver->manage()->window()->setSize($window);
         */
        
        // write avaiable browser logs (not works with marionette)
        if (self::$browser != self::MARIONETTE) {
            // Console
            $types = self::$webDriver->manage()->getAvailableLogTypes();
            if (self::DEBUG) {
                self::$climate->info('Avaiable browser logs types:');
                print_r($types);
            }
        }
    }

    /**
     * Close the WebDriver and show the screenshot in the browser
     */
    public static function tearDownAfterClass() {
        if (self::$browser != self::PHANTOMJS) {
            self::$climate->info('Closing all windows...');
            self::closeAllWindows();
        }
        
        self::$climate->info('Quitting webdriver...');
        self::$webDriver->quit();
        
        // if there is at least a screenshot show it in the browser
        if (self::$openLastScreenshot && count(self::$screenshots) > 0) {
            if (self::$browser == self::PHANTOMJS) {
                die("Assertion failed: There should be no screenshot for phantomjs headless browser." . PHP_EOL);
            }
            echo "Taken " . count(self::$screenshots) . " screenshots" . PHP_EOL;
            $first_screenshot = self::$screenshots[0];
            self::$climate->info('Opening the last screenshot...');
            self::openBrowser($first_screenshot);
        }
        
        if (self::$openLastDumpFile && count(self::$dump_files) > 0) {
            echo "Dump " . count(self::$dump_files) . " files" . PHP_EOL;
            $first_dump = self::$dump_files[0];
            self::$climate->info('Opening the last console dump...');
            self::openBrowser($first_dump);
        }
        
        // delete all temp files
        foreach (self::$files_to_del as $file) {
            unlink($file);
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
        if (self::$browser == self::PHANTOMJS) {
            $browser = "chrome";
        } else 
            if (self::$browser == self::MARIONETTE) {
                $browser = "firefox";
            }
        self::$climate->info('Opening browser at: ' . $url);
        
        // Warning: Windows specific code
        $cmd = "start " . $browser . " " . $url;
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
     * This method is called when a test method did not execute successfully
     *
     * @param \Exception $e the exception
     */
    public function onNotSuccessfulTest(\Exception $e) {
        // reduced the error message
        $msg = $this->formatErrorMsg($e);
        echo PHP_EOL;
        self::$climate->to('out')->red("EXCEPTION: " . $msg);
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
            ->implicitlyWait($wait);
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


    protected function deleteAllCookies() {
        $arguments = array();
        $this->getWd()->executeScript($this->get_js_contents(self::JS_DELETECOOKIES_SCRIPT), $arguments);
    }

    /**
     * Click on the drop area and execute the js script to upload a file
     *
     * @param string $drop_area the area to click where upload the file
     * @param string $file the file to upload
     */
    protected function dragfileToUpload($drop_area, $file) {
        
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
        
        echo PHP_EOL . "Waiting the js script execution..." . PHP_EOL;
        $wd->manage()->timeouts()->implicitlyWait(2);
        
        $file_input = $wd->findElement(WebDriverBy::id("upload")); // RemoteWebElement obj
        if (!$file_input) {
            $this->fail("\$file_input is null" . PHP_EOL);
        } else {
            // upload the file
            echo "Uploading file: " . $file . PHP_EOL;
            // $file_input->sendKeys($file);
            $file_input->setFileDetector(new LocalFileDetector())->sendKeys($file);
            // echo "Attribute is: " . $file_input->getAttribute('value') . PHP_EOL; // Facebook\WebDriver\Exception\StaleElementReferenceException: stale element reference: element is not attached to the page document
        }
    }

    /**
     * Shutdown Selenium Server Metodo non utilizzato. L'azione è delegata allo script che avvia il test.
     */
    protected function quitSelenium() {
        self::$climate->info('Quitting Selenium...');
        self::openBrowser(self::$selenium_shutdown);
    }

    /**
     * Clear the log buffer of the browser, log buffer is reset after each request
     */
    protected function clearBrowserConsole() {
        $this->getWd()
            ->manage()
            ->getLog('browser');
    }

    /**
     * Assert that the browser's console has $n errors
     *
     * @param int $n the number of the errors you want DEFAULT:zero
     */
    protected function assertErrorsOnConsole($n = 0) {
        $console_error = $this->countErrorsOnConsole();
        echo PHP_EOL . 'Errori sulla console: ' . $console_error . PHP_EOL;
        if ($n) {
            $this->assertEquals($n, $console_error);
        } else {
            $this->assertLessThan(5, $console_error);
        }
    }

    /**
     * Assert that the browser's console has zero errors
     */
    protected function assertNoErrorsOnConsole() {
        return $this->assertErrorsOnConsole(0);
    }

    /**
     * Search if the browser's console has error
     *
     * @throws \InvalidArgumentException if the read of the browser's console isn't support from the browser
     * @return number
     */
    private function countErrorsOnConsole() {
        $console_error = 0;
        
        // marionette doesn't have the console
        if (self::$browser == self::MARIONETTE) {
            throw new \InvalidArgumentException('Browser ' . self::$browser . ' non supportato dal metodo');
        }
        
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
        
        // write the console error in log file
        if (self::DEBUG && !self::$travis) {
            $this->dumpConsoleError($severe_records);
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
        $screenshots_path = self::$screenshots_path;
        if ($screenshots_path) {
            $path = $screenshots_path . "/..";
            if (!is_dir($path)) {
                $this->fail("Path not found: " . $path . " (check the SCREENSHOTS_PATH env variable)" . PHP_EOL);
            }
            $dump_file = $path . DIRECTORY_SEPARATOR . date('Y-m-d_His') . "_console.json";
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
     * @throws Exception if the screenshot or the directory where to save doesn't exist
     */
    private function takeScreenshot($msg, $element = null) {
        $screenshots_path = self::$screenshots_path;
        if ($screenshots_path) {
            echo PHP_EOL . 'Taking a screenshot...' . PHP_EOL;
            
            // The path where save the screenshot
            $save_as = $screenshots_path . DIRECTORY_SEPARATOR . date('Y-m-d_His') . ".png";
            // $this->getWd()->takeScreenshot($save_as);
            $this->takeScreenshot2($msg, $element, $save_as);
            
            if (!file_exists($save_as)) {
                throw new Exception('Could not save screenshot: ' . $save_as);
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
        
        if ($element) {
            $element_width = $element->getSize()->getWidth();
            $element_height = $element->getSize()->getHeight();
            $element_src_x = $element->getLocation()->getX();
            $element_src_y = $element->getLocation()->getY();
            
            // Create image instances
            $dest = imagecreatetruecolor($element_width, $element_height);
            
            // Copy
            imagecopy($dest, $im, 0, 0, $element_src_x, $element_src_y, $element_width, $element_height);
            
            if ($save_as) {
                // output the image to file
                imagepng($dest, $save_as);
            }
        } else {
            
            if ($save_as) {
                // output the image to file
                imagepng($im, $save_as);
            }
        }
        
        // tidy up
        imagedestroy($im);
    }
    

    private static function formatPassword($env) {
        $str = '<not set>';
        if ($env) {
            $str = '**********';
        }
        return $str;
    }
    
    private function getJsPath(){
        return __DIR__ . "/../../..";    
    }
    
    private function get_js_contents($js_file){
        $js_file = $this->getJsPath() . DIRECTORY_SEPARATOR . $js_file;        
        self::checkFile($js_file);
        $script = file_get_contents($js_file);
        return $script;
    }
    
}
