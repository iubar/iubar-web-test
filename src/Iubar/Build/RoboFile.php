<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
namespace Iubar\Build;

use Iubar\Tests\Web_TestCase;
use League\CLImate\CLImate;
use Robo\Tasks;
use Robo\Result;
use Robo\Common\IO;
use Robo\Common\Timer;

class RoboFile extends Tasks {

    use IO;
    use Timer;
    
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

    private $update_vendor = false;

    private $phpunit_xml_path = null;

    private $composer_json_path = null;

    private $logs_path = null;

    private $screenshots_path = null;

    private $working_path = '';

    function __construct($working_path) {
        $this->working_path = $working_path;
        $this->say('Working path: ' . $this->working_path);
    }

    public function run() {
       
        $this->startTimer();        
        $this->say('Initializing...');
        $this->init();
        
        if ($this->update_vendor) {
            $this->say('Updating vendor...');
            $this->taskComposerUpdate()
                ->dir($this->composer_json_path)
                ->run();
        }
        
        $result1 = null;
        if ($this->start_selenium) {
            $this->say('Starting Selenium...');
            $result1 = $this->startSelenium();
        }
        
        if(!$result1 || !$result1->wasSuccessful()){
            $this->yell('Can\'t start Selenium');
        }else{
            $this->say('Running php unit tests...');
            $result2 = $this->runPhpunit();
                
            if(!$result2 || !$result2->wasSuccessful()){
                $this->yell('Done with errors.');                
            }else{
                $this->say('Done without errors.');
            }        
            
            $this->afterTestRun();
         
        }
        
        $this->stopTimer();
        
        $this->say('Total execution time: ' . $this->getExecutionTime());
        
        if(!$this->batch_mode){
            $dummy = $this->ask('Press Enter to quit:');
        }
        
    }
    
    private function afterTestRun(){
        if ($this->start_selenium) {        
            // Don't need to explicitly close selenium when usign Robo
            // echo 'Shutting down Selenium...' . PHP_EOL;
            // $this->stopSelenium();        
        }        
        if(!$this->batch_mode){
            // Screenshots
            $screenshots_count = getenv('SCREENSHOTS_COUNT');
            if ($this->open_slideshow && $screenshots_count) {
                $confirmed = $this->confirm('Do you want to see the slideshow ?');
                if ($confirmed) {
                    $this->say('Screenshots taken: ' . $screenshots_count);
                    $host = 'localhost';
                    $port = '8000';
                    $this->say("Running slideshow on local php integrated webserver at $host:$port...");
                    $this->startHttpServer();
                    $url = 'http://' . $host . ':' . $port . '/slideshow/index.php';
                    $this->browser($url);
                }
            }
            // Console output
            $dump_file = getenv('DUMP_FILE');
            if ($this->open_dump_file && $dump_file) {
                $this->say('Opening the last console dump: ' . $dump_file);
                $this->browser($dump_file);
            }              
        }        
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
     * 
     * @param string $str
     * 
     * Returns TRUE for "1", "true", "on" and "yes". 
     * Returns FALSE otherwise.
     * (If FILTER_NULL_ON_FAILURE is set, FALSE is returned only for "0", "false", "off", "no", and "", 
     * and NULL is returned for all non-boolean values)
     */
    protected function parseBoolean($str) {        
        $b = filter_var($str, FILTER_VALIDATE_BOOLEAN);;
        return $b;
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
    private function init() {
        $ini_file = 'config.ini';
        if (!is_file($ini_file)) {            
            $error = 'File not found: ' . $ini_file;
            $this->yell($error);
            exit(1);
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
                if(!$this->batch_mode){
                    $app_password = $this->ask('Please enter password for ' . $app_username . ':'); // Change in askHidden();
                                                                                                    // FIXME: https://github.com/consolidation-org/Robo/issues/376
                                                                                                    // see https://github.com/symfony/console/blob/master/Resources/bin/hiddeninput.exe
                }else{
                    $error = 'Enviroment var not set: APP_PASSWORD';
                    $this->yell($error);
                    exit(1);
                }
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
        
        $this->update_vendor = $this->parseBoolean($ini_array['update_vendor']);
        $this->open_slideshow = $this->parseBoolean($ini_array['open_slideshow']);
        $this->open_dump_file = $this->parseBoolean($ini_array['open_dumpfile']);
        $this->batch_mode = $this->parseBoolean($ini_array['batch_mode']);
        $this->start_selenium = $this->parseBoolean($ini_array['start_selenium']);
        
        $this->say('--------------------------------------------------');
        $this->say('Enviroment variables');
        $this->say('--------------------------------------------------');
        $this->say('PHPUNIT_XML_PATH: ' . getenv('PHPUNIT_XML_PATH'));
        $this->say('COMPOSER_JSON_PATH: ' . getenv('COMPOSER_JSON_PATH'));        
        $this->say('Specific Robo (only) settings');
        $this->say('selenium path: ' . $this->selenium_path);
        $this->say('selenium jar: ' . $this->selenium_jar);
        $this->say('chrome driver: ' . $this->chrome_driver);
        $this->say('geko driver: ' . $this->geko_driver);
        $this->say('edge driver: ' . $this->edge_driver);
        $this->say('phantomjs binary: ' . $this->phantomjs_binary);
        $this->say('update vendor: ' . $this->formatBoolean($this->update_vendor));
        $this->say('open slideshow: ' . $this->formatBoolean($this->open_slideshow));      
        $this->say('open dumpfile: ' . $this->formatBoolean($this->open_dump_file));
        $this->say('batch mode: ' . $this->formatBoolean($this->batch_mode));
        $this->say('start selenium: ' . $this->formatBoolean($this->start_selenium));
        
    }
    
    private function startSeleniumAllDrivers() {
        $result = null;
        $cmd = $this->getSeleniumAllCmd();
        $this->say('Selenium cmd: ' . $cmd);
        // launches Selenium server
        $result = $this->taskExec($cmd)->background()->run();
        return $result;
    }
    
    private function startSelenium() {
        $result = null;
        $cmd = $this->getSeleniumCmd();               
        $this->say('Selenium cmd: ' . $cmd);
        // launches Selenium server
        $result = $this->taskExec($cmd)->background()->run();
        return $result;
    }

    private function getSeleniumAllCmd(){
        $cmd = null;
        $this->checkFile($this->selenium_jar);
        $cmd_prefix = 'java -jar ' . $this->selenium_jar;        
        $this->checkFile($this->chrome_driver);
        $this->checkFile($this->geko_driver);
        $this->checkFile($this->phantomjs_binary);
        $cmd = $cmd_prefix . ' -Dwebdriver.chrome.driver=' . $this->chrome_driver . ' -Dwebdriver.gecko.driver=' . $this->geko_driver . ' -Dphantomjs.binary.path=' . $this->phantomjs_binary;
        return $cmd;
    }
    
    private function getSeleniumCmd(){
        $cmd = null;
        $this->checkFile($this->selenium_jar);
        $cmd_prefix = 'java -jar ' . $this->selenium_jar;
        switch ($this->browser) {
            case Web_TestCase::CHROME:
                $this->checkFile($this->chrome_driver);
                $cmd = $cmd_prefix . ' -Dwebdriver.chrome.driver=' . $this->chrome_driver;
                break;
            case Web_TestCase::MARIONETTE:
                $this->checkFile($this->geko_driver);
                // per scegliere eseguibile: ' -Dwebdriver.firefox.bin=' . '\'C:/Program Files (x86)/Firefox Developer Edition/firefox.exe\'';
                $cmd = $cmd_prefix . ' -Dwebdriver.gecko.driver=' . $this->geko_driver;
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
                // $cmd = $cmd_prefix . ' -Dphantomjs.ghostdriver.cli.args=[\'--loglevel=DEBUG\'] -Dphantomjs.binary.path=' . $this->phantomjs_binary;
                // FIXME: non scrive il file di log:
                $cmd = $cmd_prefix . ' -Dphantomjs.ghostdriver.cli.args=[\'--loglevel=DEBUG\'] -Dphantomjs.cli.args=[\'--debug=true --webdrive --loglevel=DEBUG --webdriver-logfile=' . $phantomjs_log_file . '\'] -Dphantomjs.binary.path=' . $this->phantomjs_binary;
                break;
            default:
                $error = 'Browser ' . $this->browser . ' not supported';
                $this->yell($error);
                exit(1);
        }
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
    private function browser($url, $default=false) {
        $browser = self::$browser;
        
        if (self::$browser == self::PHANTOMJS) {
            $default = true;
        } else {
            if (self::$browser == self::MARIONETTE) {
                $default = true;
            }
        }

        $this->say('Opening browser at: ' . $url);

        if (self::isWindows()){
            if(!$default){
                $cmd = "start \"\" \"$browser $url\""; // opening the same browser that was choosen for the test 
            }else{
                $cmd = "start \"\" $url"; // opening the default system browser
            }
        }else{
            $error = 'TODO: Linux Os not yet supported'; // FIXME: 
            $this->yell($error);
            exit(1);
        }
        
        $this->say('Command is : ' . $cmd);
        self::startShell($cmd);
        return $output;
    }

    /**
     */
    private function runPhpunit() {
        $result = null;
        $cfg_file = $this->phpunit_xml_path . DIRECTORY_SEPARATOR . 'phpunit.xml';
        $this->checkFile($cfg_file);
        // runs PHPUnit tests
        $result = $this->taskPHPUnit('phpunit')->configFile($cfg_file)->run();
        return $result;
    }

    /**
     *
     * @param unknown $file
     * @return \Exception|boolean
     */
    private function checkFile($file) {
        // In alternativa si potrebbe utilizzare https://github.com/consolidation-org/Robo/blob/master/src/Common/ResourceExistenceChecker.php
        if (!is_file($file)) {
            $error = 'File not found: ' . $file;
            $this->yell($error);
            exit(1);
        }
        if (!is_readable($file)) {
            $error = 'File not found: ' . $file;
            $this->yell($error);
            exit(1);
        }
        return true;
    }

}
