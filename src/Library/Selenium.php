<?php
/**
 * Created by PhpStorm.
 * User: jeremy.dillenbourg
 * Date: 20/09/2018
 * Time: 14:11
 */

namespace Jeremy379\JsonToSelenium;


use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;

class Selenium
{
    protected $driver;
    protected $screenshot = false;
    protected $screenshotPath;
    protected $absoluteScreenshotPath;

    /**
     * Selenium constructor.
     * @param $host
     * @param array $options
     *  - capabilities : DesiredCapabilities object
     *  - port : 4444 by default
     *  - keepCookies : if true, cookie are not removed before starting
     *  - width : window width in pixel
     *  - height : window height in pixel
     */
    public function __construct($host, $options = [])
    {
        $capabilities = isset($options['capabilities']) ? $options['capabilities'] : DesiredCapabilities::chrome()->setCapability('acceptSslCerts', true);
        $port = isset($options['port']) ? $options['port'] : '4444';
        $width = isset($options['width']) ? $options['width'] : 1440;
        $height = isset($options['height']) ? $options['height'] : 767;


        $this->driver = RemoteWebDriver::create('http://'.$host.':'.$port.'/wd/hub', $capabilities, 0, 0);

        if(!isset($options['keepCookies']) || $options['keepCookies'] == false) {
            $this->driver->manage()->deleteAllCookies();
        }

        $this->driver->manage()->window()->setSize( new WebDriverDimension($width, $height));
    }

    /**
     * @return RemoteWebDriver
     */
    public function getDriver() {
        return $this->driver;
    }

    public function getSessionId() {
        return $this->getDriver()->getSessionID();
    }

    /**
     *
     */
    public function enableScreenshot() {
        $this->screenshotPath  = '/storage/screenshot/'. $this->getSessionId();
        $this->absoluteScreenshotPath = public_path($this->screenshotPath);
        mkdir($this->absoluteScreenshotPath);
        $this->screenshot = true;
    }

    public function takeScreenshotIfEnabled($step) {
        if($this->screenshot) {
            $this->getDriver()->takeScreenshot( $this->absoluteScreenshotPath . '/' . $step . '.png');
            return url('/') . '/'.$this->screenshotPath . '/' . $step . '.png';
        }
    }


    public function stop() {
        $this->getDriver()->quit();
    }
}