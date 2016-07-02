<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

namespace Iubar\Build;

use Iubar\Tests\Web_TestCase;

class RoboFile extends \Robo\Tasks {

    private $climate = null;

    private $browser = null;

    private $selenium_server = null;

    private $selenium_path = null;

    private $start_selenium = true;

    private $open_slideshow = true;

    private $update_vendor = false;
    
    private $phpunit_xml_path = null;
    
    private $composer_json_path = null;
    
    private $logs_path = null;
    
    private $screenshots_path = null;
    
    public function start() {
        $this->climate = new League\CLImate\CLImate();
        echo "Iinitializing..." . PHP_EOL;
        $this->init();
        
        if ($this->update_vendor) {
            echo "Updating vendor..." . PHP_EOL;
            $this->taskComposerUpdate()->dir($this->composer_json_path)->run();
        }
        
        if ($this->start_selenium) {
            echo "Starting Selenium..." . PHP_EOL;
            $this->startSelenium();
            echo PHP_EOL;
        }
        // TODO: check if selenium is running, or fail
        echo "Running php unit tests..." . PHP_EOL;
        $this->runPhpunit();
        echo PHP_EOL;
        if ($this->start_selenium) {
            echo "Shutting down Selenium..." . PHP_EOL;
            $this->stopSelenium(); // TODO: verificare se necessario o se ci pensa Robo
            echo PHP_EOL;
        }
        if ($this->browser != Web_TestCase::PHANTOMJS) {
            if ($this->open_slideshow) {
                $host = 'localhost';
                $port = '8000';
                echo "Running slideshow on $host:$port..." . PHP_EOL;
                $this->startHttpServer();                
                $url = 'http://' . $host . ':' . $port . '/slideshow/index.php';
                $this->browser($url);
                echo PHP_EOL;
                $input = $this->climate->password('Press Enter to stop the slideshow:');
                $dummy = $input->prompt();
            }
        }
        echo "Done." . PHP_EOL;
    }

    private function stopSelenium() {
        $url = 'http://' . $this->selenium_server . '/selenium-server/driver/?cmd=shutDownSeleniumServer';
        $this->browser($url);
    }

    private function closeSeleniumSession() {
        $url = 'http://' . $this->selenium_server . '/selenium-server/driver/?cmd=shutDown';
        $this->browser($url);
    }

    private function isRelativePath($path) {
        $tmp = realpath($path);
        if (!$tmp) { // then it's a relative path
            return true;
        }
        return false;
    }

    private function putenv($var_name, $path){
        if (!getenv($var_name)) {
            if ($this->isRelativePath(path)) {
                $path = __DIR__ . path;
            }
        }        
        if (!is_dir(path)) {
            die("Path not found: " . path . PHP_EOL);
        }
        if (!getenv($var_name)) {
            putenv($var_name . '=' . path);
        }
    }
  
    private function init() {
        $ini_file = "config.ini";
        if (!is_file($ini_file)) {
            die("File not found: " . $ini_file . PHP_EOL);
        }
        $ini_array = parse_ini_file($ini_file);
       
        if(!getenv('BROWSER')){
            $this->browser = $ini_array['browser'];        
            putenv('BROWSER=' . $this->browser);
        }
        
        if(!getenv('SELENIUM_SERVER')){
            $this->selenium_server = $ini_array['selenium_server'];
            putenv('SELENIUM_SERVER=' . $this->selenium_server);
        }
        
        if(!getenv('APP_HOST')){
            $app_host = $ini_array['app_host'];
            putenv('APP_HOST=' . $app_host);
        }
        
        if(!getenv('APP_USERNAME')){
            if (!$app_username) {
                $app_username = $ini_array['app_username'];
                putenv('APP_USERNAME=' . $app_username);
            }
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
        
        $this->selenium_path = $ini_array['selenium_path'];
        $this->putenv('SELENIUM_PATH', $this->selenium_path);
        
        $this->logs_path = $ini_array['logs_path'];
        $this->putenv('LOGS_PATH', $this->logs_path);
        
        $this->screenshots_path = $ini_array['screenshots_path'];
        $this->putenv('SCREENSHOTS_PATH', $this->screenshots_pat);
        
        $this->composer_json_path = $ini_array['composer_json_path'];
        $this->putenv('COMPOSER_JSON_PATH', $this->composer_json_path);
        
        $this->phpunit_xml_path = $ini_array['phpunit_xml_path'];
        $this->putenv('PHPUNIT_XML_PATH', $this->phpunit_xml_path);
        
        // Non uso variabili d'ambiente per i seguenti valori, perchè riguardano solo il testing con Robo
        $this->update_vendor = $ini_array['update_vendor'];
        $this->open_slideshow = $ini_array['open_slideshow'];
        $this->start_selenium = $ini_array['start_selenium'];
         
        echo PHP_EOL . "Enviroment variables for Robo" . PHP_EOL . PHP_EOL;        
        echo "PHPUNIT_XML_PATH: " . getenv("PHPUNIT_XML_PATH") . PHP_EOL;
        echo "COMPOSER_JSON_PATH: " . getenv("COMPOSER_JSON_PATH") . PHP_EOL;
        
        echo PHP_EOL . "Robo options" . PHP_EOL . PHP_EOL;        
        echo "update vendor: " . $this->formatBoolean($this->update_vendor) . PHP_EOL;
        echo "open slideshow: " . $this->formatBoolean($this->open_slideshow) . PHP_EOL;
        echo "start selenium: " . $this->formatBoolean($this->start_selenium) . PHP_EOL;
                
        echo PHP_EOL . PHP_EOL;
    }

    protected function formatBoolean($b){
        if($b){
            return 'true';
        }else{
            return 'false';
        }
    }
    
    private function formatPassword($env) {
        $str = '<not set>';
        if ($env) {
            $str = '**********';
        }
        return $str;
    }

    private function startSelenium() {
        $cmd = null;
        
        $selenium_path = $this->selenium_path;
        
        // TODO: se non trovo selenium-server.jar o i drivers (!!!), allora il test deve fallire
        
        $cmmd_prefix = "java -jar $selenium_path/selenium-server\selenium-server-standalone.jar";
        switch ($this->browser) {
            case Web_TestCase::CHROME:
                $cmd = $cmmd_prefix . " -Dwebdriver.chrome.driver=" . "$selenium_path/drivers/chrome/chromedriver.exe";
                break;
            case Web_TestCase::MARIONETTE:
                // $cmd = $cmmd_prefix . " -Dwebdriver.gecko.driver=" . "$selenium_path/drivers/marionette/wires-0.6.2.exe" . " -Dwebdriver.firefox.bin=" . "\"C:/Program Files (x86)/Firefox Developer Edition/firefox.exe\"";
                $cmd = $cmmd_prefix . " -Dwebdriver.gecko.driver=" . "$selenium_path/drivers/marionette/wires-0.6.2.exe";
                break;
            case Web_TestCase::FIREFOX:
                $cmd = $cmmd_prefix . "";
                break;
            case Web_TestCase::PHANTOMJS:
                $cmd = $cmmd_prefix . " -Dphantomjs.ghostdriver.cli.args=[\"--loglevel=DEBUG\"] -Dphantomjs.binary.path=" . "$selenium_path/phantomjs-2.1.1-windows\bin\phantomjs.exe";
                break;
            case 'all':
                $cmd = $cmmd_prefix . " -Dwebdriver.chrome.driver=" . "$selenium_path/drivers/chrome/chromedriver.exe" . " -Dwebdriver.gecko.driver=" . "$selenium_path/drivers/wires-0.6.2.exe" . " -Dphantomjs.binary.path=" . "$selenium_path/phantomjs-2.1.1-windows\bin\phantomjs.exe";
                break;
            default:
                die("Browser '" . $this->browser . "' not supported" . PHP_EOL);
        }
        
        if ($cmd) {
            // launches Selenium server
            $this->taskExec($cmd)
                ->background()
                ->run();
        }
    }

    private function startHttpServer() {
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

    private function browser($url) {
        // TODO: valutare se è meglio avviare il browser $this->browser pittuosto che quello di default di sistema
        $this->climate->info("opening browser at: " . $url);
        $input = $this->climate->input('Invio per continuare');
        $response = $input->prompt();
        $this->taskOpenBrowser([
            $url
        ])->run();
    }    
    
    private function runPhpunit() {
        $cfg_file = $this->phpunit_xml_path . "\phpunit.xml";        
        if (!is_file($cfg_file)) {
            die("File not found: " . $cfg_file . PHP_EOL);
        }
        // runs PHPUnit tests
        $this->taskPHPUnit('phpunit')
        ->configFile($cfg_file)            
        // ->bootstrap('test/bootstrap.php')
        ->run();
    }
}