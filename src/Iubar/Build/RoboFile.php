<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
namespace Iubar\Build;

use \Iubar\Tests\Web_TestCase;
use \League\CLImate\CLImate;

class RoboFile extends \Robo\Tasks {

    private $climate = null;

    private $browser = null;

    private $browser_version = null;

    private $os_version = null;

    private $selenium_server = null;

    private $selenium_port = null;

    private $selenium_path = null;

    private $start_selenium = true;

    private $phantomjs_binary = null;

    private $open_slideshow = true;
    
    private $open_dumpfile = true;

    private $update_vendor = false;

    private $phpunit_xml_path = null;

    private $composer_json_path = null;

    private $logs_path = null;

    private $screenshots_path = null;

    private $working_path = '';

    function __construct($working_path) {
        $this->working_path = $working_path;
        echo "Working path: " . $this->working_path . PHP_EOL;
    }

    /**
     */
    public function start() {
        $this->climate = new CLImate();
        $this->climate->info("Initializing...");
        $this->init();
        
        if ($this->update_vendor) {
            $this->climate->info("Updating vendor...");
            $this->taskComposerUpdate()
                ->dir($this->composer_json_path)
                ->run();
        }
        
        if ($this->start_selenium) {
            $this->climate->info("Starting Selenium...");
            $this->startSelenium();
        }
        
        $this->climate->info("Running php unit tests...");
        $this->runPhpunit();
        if ($this->start_selenium) {
            
            // Don't need to explicitly close selenium when usign Robo
            // echo "Shutting down Selenium..." . PHP_EOL;
            // $this->stopSelenium(); // TODO: verificare se necessario o se ci pensa Robo
            // echo PHP_EOL;
        }
        if ($this->browser != Web_TestCase::PHANTOMJS) {
            if ($this->open_slideshow && getenv("SCREENSHOTS_COUNT") ) {
                
                
                $input = $climate->confirm('Do you want to see the slideshow ?');
                if ($input->confirmed()) {                
                    $this->climate->info("Screenshots taken: " . getenv("SCREENSHOTS_COUNT")); 
                    $host = 'localhost';
                    $port = '8000';
                    $this->climate->info("Running slideshow on $host:$port...");
                    $this->startHttpServer();
                    $url = 'http://' . $host . ':' . $port . '/slideshow/index.php';
                    $this->browser($url);     
                    // $input = $this->climate->password('Press Enter to quit the slideshow:');
                    // $dummy = $input->prompt();                    
                }             
            }            
        }
        
        $dump_file = getenv("DUMPFILE");
        if ($this->open_dumpfile && $dump_file ) {   
            self::$climate->info('Opening the last console dump: ' .  $dump_file);
            self::openBrowser($dump_file);
        }
        
            $input = $this->climate->password('Press Enter to quit:');
            $dummy = $input->prompt();
        $this->climate->info("Done.");
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

    /**
     */
    private function stopSelenium() {
        $url = 'http://' . $this->selenium_server . '/selenium-server/driver/?cmd=shutDownSeleniumServer';
        $this->browser($url);
    }

    /**
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
            die("Path not found: " . $path . PHP_EOL);
        }
        if (!getenv($var_name)) {
            putenv($var_name . '=' . $path);
        }
    }

    /**
     */
    private function init() {
        $ini_file = "config.ini";
        if (!is_file($ini_file)) {
            die("File not found: " . $ini_file . PHP_EOL);
        }
        $ini_array = parse_ini_file($ini_file);
        
        if (!getenv('BROWSER')) {
            $this->browser = $ini_array['browser'];
            putenv('BROWSER=' . $this->browser);
        }
        
        if (!getenv('BROWSER_VERSION')) {
            $this->browser_version = $ini_array['browser_version'][$this->browser];
            putenv('BROWSER_VERSION=' . $this->browser_version);
        }
        
        if (!getenv('OS_VERSION')) {
            $this->os_version = $ini_array['os_version'][$this->browser];
            putenv('OS_VERSION=' . $this->os_version);
        }
        
        if (!getenv('SELENIUM_SERVER')) {
            $this->selenium_server = $ini_array['selenium_server'];
            putenv('SELENIUM_SERVER=' . $this->selenium_server);
        }
        
        if (!getenv('SELENIUM_PORT')) {
            $this->selenium_port = $ini_array['selenium_port'];
            putenv('SELENIUM_PORT=' . $this->selenium_port);
        }
        
        if (!getenv('PHANTOMJS_BINARY')) {
            $this->phantomjs_binary = $ini_array['selenium_path'] . DIRECTORY_SEPARATOR . $ini_array['phantomjs_binary'];
            putenv('PHANTOMJS_BINARY=' . $this->phantomjs_binary);
        }
        
        if (!getenv('APP_HOST')) {
            $app_host = $ini_array['app_host'];
            putenv('APP_HOST=' . $app_host);
        }
        
        if (!getenv('APP_USERNAME')) {
            $app_username = $ini_array['app_username'];
            putenv('APP_USERNAME=' . $app_username);
        }
        
        // Posso specificare la password a) come variabile d'ambiente, b) nel file .ini, c) in modo interattivo da console
        
        if (!getenv('APP_PASSWORD')) {
            if (isset($ini_array['app_password'])) {
                $app_password = $ini_array['app_password'];
            }
            if (!$app_password) {
                $input = $this->climate->password('Please enter password for ' . $app_username . ':');
                $app_password = $input->prompt();
            }
            putenv('APP_PASSWORD=' . $app_password);
        }
        
        $this->logs_path = $ini_array['logs_path'];
        $this->putEnv('LOGS_PATH', $this->logs_path);
        
        $this->screenshots_path = $ini_array['screenshots_path'];
        $this->putEnv('SCREENSHOTS_PATH', $this->screenshots_path);
        
        $this->composer_json_path = $ini_array['composer_json_path'];
        $this->putEnv('COMPOSER_JSON_PATH', $this->composer_json_path);
        
        $this->phpunit_xml_path = $ini_array['phpunit_xml_path'];
        $this->putEnv('PHPUNIT_XML_PATH', $this->phpunit_xml_path);
        
        // Non uso variabili d'ambiente per i seguenti valori, perchè riguardano solo il testing con Robo
        $this->selenium_path = $ini_array['selenium_path'];
        $this->selenium_jar = $this->selenium_path . DIRECTORY_SEPARATOR . $ini_array['selenium_jar'];
        $this->chrome_driver = $this->selenium_path . DIRECTORY_SEPARATOR . $ini_array['chrome_driver'];
        $this->geko_driver = $this->selenium_path . DIRECTORY_SEPARATOR . $ini_array['geko_driver'];
        $this->edge_driver = $this->selenium_path . DIRECTORY_SEPARATOR . $ini_array['edge_driver'];
        $this->phantomjs_binary = $this->selenium_path . DIRECTORY_SEPARATOR . $ini_array['phantomjs_binary'];
        $this->update_vendor = $ini_array['update_vendor'];
        $this->open_slideshow = $ini_array['open_slideshow'];
        $this->start_selenium = $ini_array['start_selenium'];
        
        $this->climate->underline()->bold("Enviroment variables for the generic building tool");
        $this->climate->info("PHPUNIT_XML_PATH: " . getenv("PHPUNIT_XML_PATH"));
        $this->climate->info("COMPOSER_JSON_PATH: " . getenv("COMPOSER_JSON_PATH"));
        
        $this->climate->info("Specific Robo (only) settings");
        $this->climate->info("selenium path: " . $this->selenium_path);
        $this->climate->info("selenium jar: " . $this->selenium_jar);
        $this->climate->info("chrome driver: " . $this->chrome_driver);
        $this->climate->info("geko driver: " . $this->geko_driver);
        $this->climate->info("edge driver: " . $this->edge_driver);
        $this->climate->info("phantomjs binary: " . $this->phantomjs_binary);
        $this->climate->info("update vendor: " . $this->formatBoolean($this->update_vendor));
        $this->climate->info("open slideshow: " . $this->formatBoolean($this->open_slideshow));
        $this->climate->info("start selenium: " . $this->formatBoolean($this->start_selenium));
    }

    /**
     */
    private function startSelenium() {
        $cmd = null;
        $this->checkFile($this->selenium_jar);
        $cmd_prefix = 'java -jar ' . $this->selenium_jar;
        switch ($this->browser) {
            case Web_TestCase::CHROME:
                $this->checkFile($this->chrome_driver);
                $cmd = $cmd_prefix . " -Dwebdriver.chrome.driver=" . $this->chrome_driver;
                break;
            case Web_TestCase::MARIONETTE:
                $this->checkFile($this->geko_driver);
                // per scegliere eseguibile: " -Dwebdriver.firefox.bin=" . "\"C:/Program Files (x86)/Firefox Developer Edition/firefox.exe\"";
                $cmd = $cmd_prefix . " -Dwebdriver.gecko.driver=" . $this->geko_driver;
                break;
            case Web_TestCase::FIREFOX:
                
                $cmd = $cmd_prefix . '';
                break;
            case Web_TestCase::SAFARI:
                $cmd = $cmd_prefix . ''; // SafariDriver now requires manual installation of the extension prior to automation.
                                         // (So I think no driver is required)
                                         // see https://github.com/SeleniumHQ/selenium/wiki/SafariDriver
                break;
            case Web_TestCase::PHANTOMJS:
                $this->checkFile($this->phantomjs_binary);
                $phantomjs_log_file = realpath($this->logs_path) . DIRECTORY_SEPARATOR . 'phantomjsdriver.log';
                // see: https://github.com/detro/ghostdriver
                // OK:
                // $cmd = $cmd_prefix . " -Dphantomjs.ghostdriver.cli.args=[\"--loglevel=DEBUG\"] -Dphantomjs.binary.path=" . $this->phantomjs_binary;
                // FIXME: non scrive il file di log:
                $cmd = $cmd_prefix . " -Dphantomjs.ghostdriver.cli.args=[\"--loglevel=DEBUG\"] -Dphantomjs.cli.args=[\"--debug=true --webdrive --loglevel=DEBUG --webdriver-logfile=" . $phantomjs_log_file . "\"] -Dphantomjs.binary.path=" . $this->phantomjs_binary;
                break;
            
            case 'all':
                $this->checkFile($this->chrome_driver);
                $this->checkFile($this->geko_driver);
                $this->checkFile($this->phantomjs_binary);
                $cmd = $cmd_prefix . " -Dwebdriver.chrome.driver=" . $this->chrome_driver . " -Dwebdriver.gecko.driver=" . $this->geko_driver . " -Dphantomjs.binary.path=" . $this->phantomjs_binary;
                break;
            default:
                die("Browser '" . $this->browser . "' not supported" . PHP_EOL);
        }
        
        if ($cmd) {
            $this->climate->info("Selenium cmd: " . $cmd);
            // launches Selenium server
            $this->taskExec($cmd)
                ->background()
                ->run();
        }
    }

    /**
     */
    private function startHttpServer() {
        $dir = realpath($this->screenshots_path);
        if (!is_dir($dir)) {
            die("Path not found: " . $dir . PHP_EOL);
        }
        // starts PHP server
        $this->taskServer(8000)
            ->dir($dir)
            ->
        // ->host('0.0.0.0')
        background()
            ->
        // execute server in background
        run();
    }

    /**
     *
     * @param unknown $url
     * @return string
     */
    private function browser($url) {
        // TODO: valutare se è meglio avviare il browser $this->browser piuttosto che quello di default di sistema
        $this->climate->info("Opening browser at: " . $url);
        // $input = $this->climate->input('Invio per continuare');
        // $response = $input->prompt();
        // NON VA: $this->taskOpenBrowser($url)->run();
        $cmd = "start \"\" \"" . $url . "\"";
        $output = shell_exec($cmd);
        return $output;
    }

    /**
     */
    private function runPhpunit() {
        $cfg_file = $this->phpunit_xml_path . "\phpunit.xml";
        $this->checkFile($cfg_file);
        // runs PHPUnit tests
        $this->taskPHPUnit('phpunit')
            ->configFile($cfg_file)
            ->
        // ->bootstrap('test/bootstrap.php')
        run();
    }

    /**
     *
     * @param unknown $file
     * @return \Exception|boolean
     */
    private function checkFile($file) {
        if (!is_file($file)) {
            $error = "File not found: " . $file;
            return new \Exception($error);
        }
        if (!is_readable($file)) {
            $error = "File not found: " . $file;
            return new \Exception($error);
        }
        return true;
    }
}