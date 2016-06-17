<?php
namespace Iubar;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use League\CLImate\CLImate;
use Facebook\WebDriver\Remote\DriverCommand;

/**
 * PHPUnit_Framework_TestCase Develop
 *
 * @author Matteo
 *        
 * @see : https://gist.github.com/huangzhichong/3284966 Cheat sheet for using php webdriver
 * @see : https://gist.github.com/aczietlow/7c4834f79a7afd920d8f Cheat sheet for using php webdriver
 *     
 */
class Web_TestCase extends Root_TestCase {

    const DEBUG = true;

    const TAKE_A_SCREENSHOT = true;
    
    // seconds
    const DEFAULT_WAIT_TIMEOUT = 15;
    
    // milliseconds
    const DEFAULT_WAIT_INTERVAL = 1000;
    
    // Browser
    const PHANTOMJS = 'phantomjs';

    const CHROME = 'chrome';

    const FIREFOX = 'firefox';

    const MARIONETTE = 'marionette';

    const START = 'start';

    protected static $screenshots = array();

    protected static $webDriver;

    protected static $selenium_shutdown;
    
    // easily output colored text and special formatting
    protected static $climate;

    /**
     * Start the WebDriver
     *
     * @global string BROWSER
     * @global string TRAVIS
     *        
     */
    public static function setUpBeforeClass() {
        self::$climate = new CLImate();
        
        // Usage with SauceLabs:
        // set on Travis: SAUCE_USERNAME and SAUCE_ACCESS_KEY
        // set on .tavis.yml and env.bat: SELENIUM_SERVER (hostname + port, without protocol);
        
        // check if you can take screenshots and path exist
        if (self::TAKE_A_SCREENSHOT) {
            $screenshots_path = getEnv('SCREENSHOTS_PATH');
            if ($screenshots_path && ! is_writable($screenshots_path)) {
                die("ERRORE percorso non scrivibile: " . $screenshots_path . PHP_EOL);
            }
        }
        
        $capabilities = null;
        
        // set capabilities according to the browers
        switch (getEnv('BROWSER')) {
            case self::PHANTOMJS:
                echo "Inizializing PhantomJs browser" . PHP_EOL;
                $capabilities = DesiredCapabilities::phantomjs();
                break;
            case self::CHROME:
                echo "Inizializing Chrome browser" . PHP_EOL;
                $capabilities = DesiredCapabilities::chrome();
                break;
            case self::FIREFOX:
                echo "Inizializing Firefox browser" . PHP_EOL;
                $capabilities = DesiredCapabilities::firefox();
                break;
            case self::MARIONETTE:
                echo "Inizializing Marionette browser" . PHP_EOL;
                $capabilities = DesiredCapabilities::firefox();
                $capabilities->setCapability(self::MARIONETTE, true);
                break;
            default:
                $error = "Browser '" . getEnv('BROWSER') . "' not supported.";
                $error .= PHP_EOL . "(you should set the BROWSER global var with a supported browser name)";
                die("ERROR: " . $error . PHP_EOL);
        }
        
        // create the WebDriver
        $connection_timeout_in_ms = 10 * 1000; // Set the maximum time of a request
        $request_timeout_in_ms = 20 * 1000; // Set the maximum time of a request
        
        $server_root = null;
        // set Travis params
        if (getEnv('TRAVIS')) {
            echo "Travis detected..." . PHP_EOL;
            $capabilities->setCapability('tunnel-identifier', getEnv('TRAVIS_JOB_NUMBER'));
            $username = getEnv('SAUCE_USERNAME');
            $access_key = getEnv('SAUCE_ACCESS_KEY');
            $server_root = "http://" . $username . ":" . $access_key . "@" . getEnv('SELENIUM_SERVER');
        } else {
            $server_root = "http://" . getEnv('SELENIUM_SERVER');
        }
        self::$selenium_shutdown = $server_root . '/selenium-server/driver/?cmd=shutDownSeleniumServer';
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
         * Window size
         * $self::$webDriver->manage()->window()->maximize();
         * $window = new WebDriverDimension(1024, 768);
         * $this->webDriver->manage()->window()->setSize($window);
         */
        
        // write avaiable browser logs (not works with marionette)
        if (getEnv('BROWSER') != self::MARIONETTE) {
            // Console
            $types = self::$webDriver->manage()->getAvailableLogTypes();
            print_r($types);
        }
    }

    /**
     * Close the WebDriver and show the screenshot in the browser if there is
     */
    public static function tearDownAfterClass() {
        self::closeAllWindows();
        self::$webDriver->quit();
        
        // if there is at least a screenshot show it in the browser
        if (count(self::$screenshots) > 0) {
            echo "Taken " . count(self::$screenshots) . " screenshots" . PHP_EOL;
            $first_screenshot = self::$screenshots[0];
            self::startShell(self::START . " " . getEnv('BROWSER') . " " . $first_screenshot);
            echo PHP_EOL . self::START . " " . getEnv('BROWSER') . " " . $first_screenshot . PHP_EOL;
        }
    }

    /**
     * Close all windows
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
     * Take a screenshot of the webpage
     *
     * @param string $element the element to capture
     * @throws Exception if the screenshot or the directory where to save doesn't exist
     */
    public function takeScreenshot($msg, $element = null) {
        $screenshots_path = getEnv('SCREENSHOTS_PATH');
        if ($screenshots_path) {
            echo "Taking a screenshot..." . PHP_EOL;
            
            // The path where save the screenshot
            $save_as = $screenshots_path . date('Y-m-d_His') . ".png";
            // $this->getWd()->takeScreenshot($save_as);
            $this->takeScreenshot2($msg, $element, $save_as);
            
            if (! file_exists($save_as)) {
                throw new Exception('Could not save screenshot: ' . $save_as);
            }
            
            self::$screenshots[] = $save_as;
        }
    }

    /**
     * This method is called when a test method did not execute successfully
     *
     * @param \Exception $e the exception
     *       
     */
    public function onNotSuccessfulTest(\Exception $e) {
        // reduced the error message
        $msg = $this->formatErrorMsg($e);
        echo PHP_EOL;
        self::$climate->to('out')->red("EXCEPTION: " . $msg);
        if (self::TAKE_A_SCREENSHOT) {
            $this->takeScreenshot($msg);
        }
        parent::onNotSuccessfulTest($e);
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
     * @param int $interval the interval in miliseconds
     */
    protected function waitForTag($tag, $timeout = self::DEFAULT_WAIT_TIMEOUT, $interval = self::DEFAULT_WAIT_INTERVAL) {
        $this->getWd()
            ->wait($timeout, $interval)
            ->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::tagName($tag)));
    }

    /**
     * Wait at most $timeout seconds until at least one result is shown
     *
     * @param string $css the css of the element
     * @param int $timeout the timeout in seconds
     * @param int $interval the interval in miliseconds
     */
    protected function waitForCss($css, $timeout = self::DEFAULT_WAIT_TIMEOUT, $interval = self::DEFAULT_WAIT_INTERVAL) {
        $this->getWd()
            ->wait($timeout, $interval)
            ->until(WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector($css)));
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
     * @param integer $timeout timeout in seconds
     * @param integer $interval interval in miliseconds
     * @return void
     */
    protected function waitForAjax($timeout = self::DEFAULT_WAIT_TIMEOUT, $interval = self::DEFAULT_WAIT_INTERVAL) {
        $this->getWd()
            ->wait($timeout, $interval)
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
     * @param integer $timeout timeout in seconds
     */
    protected function waitAjaxLoad2($timeout = 10) {
        $this->getWd()->waitForJS('return !!window.jQuery && window.jQuery.active == 0;', $timeout);
        $this->getWd()->wait(1);
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
     * Shutdown Selenium Server
     *
     * Metodo non utilizzato. L'azione è delegata allo script che avvia il test.
     */
    protected function quitSelenium() {
        $this->startShell(self::START . " " . getEnv('BROWSER') . " " . self::$selenium_shutdown);
    }

    /**
     * Search if the browser's console has error and assuming that are zero
     *
     * @throws \InvalidArgumentException if the read of the browser's console isn't support from the browser
     */
    protected function assertNoErrorsOnConsole() {
        $console_error = 0;
        
        // marionette doesn't have the console
        if (getEnv('BROWSER') == self::MARIONETTE) {
            throw new \InvalidArgumentException('Browser ' . getEnv('BROWSER') . ' non supportato dal metodo');
        }
        
        $wd = $this->getWd();
        $records = $wd->manage()->getLog('browser');
        $severe_records = array();
        // search for the error in the console
        
        self::$climate->info('Records: ' . count($records));
        
        foreach ($records as $record) {
            if ($record['level'] == 'SEVERE') {
                if (! self::shouldSkip($record['message'])) {
                    $severe_records[] = $record;
                }
            }
        }
        
        self::$climate->info('Filtered records (severe): ' . count($severe_records));
        
        $console_error = count($severe_records);
        if (self::DEBUG) {
            $output = @rt($severe_records);
            echo "-->" . $output . PHP_EOL;
        }
        
        // write the console error in log file
        if (self::DEBUG && ! getEnv('TRAVIS')) {
            $data = json_encode($severe_records, JSON_PRETTY_PRINT);
            $screenshots_path = getEnv('SCREENSHOTS_PATH');
            $path = $screenshots_path . "/..";
            if (! is_dir($path)) {
                die("PATH NOT FOUND: " . $path . PHP_EOL);
            }
            file_put_contents($path . "/" . date('Y-m-d_His') . "_console.txt", $data);
        }
        
        echo PHP_EOL . 'Errori sulla console: ' . $console_error . PHP_EOL;
        $this->assertEquals(0, $console_error);
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
        $array = explode("\n", $msg);
        $msg = $array[0] . "...";
        return $msg;
    }

    /**
     * Write the message with a specific color in the output
     *
     * @param string $color the color
     * @param string $msg the message
     */
    private function write_color_msg($color, $msg) {
        self::$climate->to('out')->$color($msg);
    }

    private function takeScreenshot2($msg, $save_as = null) {
        $screenshot = base64_decode($this->getWd()->execute(DriverCommand::SCREENSHOT));
        $im = imagecreatefromstring($screenshot);
        
        // define some colours to use with the image
        $yellow = imagecolorallocate($im, 255, 255, 0);
        $black = imagecolorallocate($im, 0, 0, 0);
        
        // get the width and the height of the image
        $width = imagesx($im);
        $height = imagesy($im);
        
        // draw a black rectangle across the bottom, say, 20 pixels of the image:
        imagefilledrectangle($im, 0, ($height - 20), $width, $height, $black);
        
        // now we want to write in the centre of the rectangle:
        $font = 24; // store the int ID of the system font we're using in $font
                    // calculate the left position of the text:
        $leftTextPos = ($width - imagefontwidth($font) * strlen($text)) / 2;
        // finally, write the string:
        imagestring($im, $font, $leftTextPos, $height - 18, $msg, $yellow);
        
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
}