<?php

namespace Jeremy379\JsonToSelenium\Selenium;


use Facebook\WebDriver\Exception\WebDriverException;
use Jeremy379\JsonToSelenium\Selenium;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\NoSuchFrameException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;

class Play
{
    protected $flow;
    protected $test;
    protected $selenium;
    protected $result;
    protected $startTime;

    public function __construct(Selenium $selenium, $playJson, $testJson = null, $withScreenShot = false)
    {
        $this->selenium = $selenium;
        if($withScreenShot)
            $this->selenium->enableScreenshot();

        $this->flow = json_decode($playJson, true);
        $this->test = json_decode($testJson, true);
        $this->startTime = time();
    }

    public function play() {
        $step = 1;

        foreach($this->flow['tests'][0]['commands'] as $cmd) {
            $this->writeResult($step, 'command', $cmd['command']);
            $this->writeResult($step, 'target', $cmd['target']);

            if(method_exists($this, 'runCommand'.ucfirst($cmd['command']))) {
                try {
                    $this->{'runCommand' . ucfirst($cmd['command'])}($cmd);
                } catch (\Exception $e) {
                    $this->writeResult($step, 'error', $e->getMessage());
                    $this->writeResult($step, 'status', 'error');
                    break;
                }
            }

            $this->selenium->getDriver()->wait(500)->until(
                WebDriverExpectedCondition::titleMatches('#(.*)#')//will match all, just to make sure it wait.
            );

            $tests = [];
            $status = 'passed';
            if(isset($this->test[$step])) {
                foreach($this->test[$step] as $k=>$test) {
                    $runTestResult = $this->runTest($test);
                    $tests[$k] = [
                            'passed' => $runTestResult,
                            'test'   => $test
                        ];

                    $status = $runTestResult ? $status : 'error';
                }
            }

            $this->writeResult($step, 'test', $tests);
            $this->writeResult($step, 'page_title', $this->selenium->getDriver()->getTitle());
            $this->writeResult($step, 'screenshot', $this->selenium->takeScreenshotIfEnabled($step) );
            $this->writeResult($step, 'time_elapsed', time() - $this->startTime);
            $this->writeResult($step, 'status', $status);

            $step++;
        }
    }

    protected function runCommandOpen($cmd) {
        $this->selenium->getDriver()->get($this->flow['url'] . $cmd['target']);
    }

    protected function runCommandClick($cmd) {
        $link = $this->getLink($this->selenium->getDriver(), $cmd['target']);
        $link->click();
    }

    protected function runCommandSelect($cmd) {
        $link = $this->getLink($this->selenium->getDriver(), $cmd['target']);
        $link = $link->click();

        $wds = new WebDriverSelect($link);
        $wds->selectByVisibleText( $this->getCleanLabel($cmd['value']));
    }

    protected function runCommandType($cmd) {
        $link = $this->getLink($this->selenium->getDriver(), $cmd['target']);
        $link->sendKeys($cmd['value']);
    }

    protected function runCommandSleep($cmd) {
        sleep($cmd['target']);
    }

    protected function runCommandSelectFrame($cmd) {
        $target = $cmd['target'];

        if(strpos($target, 'index=') === 0) {
            $target = $this->getIframe( $this->selenium->getDriver(), $this->getCleanTarget($target));
            if($target === FALSE) {
                Throw new \Exception('Iframe ' . $target . ' is not reachable. Please replace index by class, id or name');
            }
        }

        sleep(3);

        if($target == 'relative=parent') {
            $this->selenium->getDriver()->switchTo()->defaultContent();
        } else {

            $frame = $this->getLink($this->selenium->getDriver(), $target);

            $sfi = 0;
            do {
                try {
                    $this->selenium->getDriver()->switchTo()->frame($frame);
                    $switched = true;
                } catch (NoSuchFrameException $e) {
                    $switched = false;
                    sleep(1);

                    if ($sfi > $this->selenium->getDefaultTimeoutToFindElement()) {
                        Throw new NoSuchFrameException($e);
                    }
                    $sfi++;
                }
            } while (!$switched);
        }
    }

    protected function runCommandRunScript($cmd) {
        $this->selenium->getDriver()->executeScript($cmd['target']);
    }

    /**
     * $test['operation'] page_title , target_content, target_content_match, target_exists, count_target_element, link_valid
     * $test['target_prefix'] : id=, css=, name=, linkText=, xpath= , + $test['target']
     * check if = $test['expected']
     *
     * @param array $test
     * @return bool
     */
    protected function runTest($test = [])
    {
        $target = $test['target_prefix'] . $test['target'];
        $operation = $test['operation'];
        $expected = $test['expected'];

        try {
            $element =  $this->getLink($this->selenium->getDriver(), $target);
            switch ($operation) {
                case 'page_title':
                    return ($this->selenium->getDriver()->getTitle() == $expected);
                    break;
                case 'target_exists':
                    return true;//the try catch already returned false if no element
                break;
                case 'target_content':
                    return ($element->getText() == $expected);
                    break;
                case 'target_content_match':
                    return preg_match('#'.$expected.'#', $element->getText());
                    break;
                case 'count_target_element':
                    return (count($this->getLinks($this->selenium->getDriver(), $target)) == $expected);
                    break;
                case 'link_valid':
                    $href = $element->getAttribute('href');
                    try {
                        $check = get_headers($href, 1);
                        $status = substr($check[0], 9, 3);
                    } catch(\Exception $e) {
                        return false;
                    }

                    if(empty($status) || !empty($status) && $status != 200) {
                        return false;
                    }
                    break;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getIframe(RemoteWebDriver $driver, $index) {
        $iframes = $driver->findElements(WebDriverBy::cssSelector('iframe'));

        $i = 0;
        foreach($iframes as $iframe) {
            $name = $iframe->getAttribute('name');
            $class = $iframe->getAttribute('class');
            $id = $iframe->getAttribute('id');

            if($index == $i) {
                if(!empty($id)) {
                    return 'id='.$id;
                }
                if(!empty($class)) {
                    return 'css='.$class;
                }
                if(!empty($name)) {
                    return 'name='.$name;
                }
            }
        }

        return false;
    }

    /**
     * @param RemoteWebDriver $driver
     * @param $target
     * @param int $ii
     * @return \Facebook\WebDriver\Remote\RemoteWebElement|mixed
     * @throws NoSuchElementException
     * @throws StaleElementReferenceException
     */
    protected function getLink(RemoteWebDriver $driver,$target, $ii = 0) {

        try {
            $byMethod = $this->getByMethod($target);
            $cleanTarget = $this->getCleanTarget($target);
            $el = $driver->findElement(WebDriverBy::$byMethod($cleanTarget));

            $i = 0;
            while (!$el->isDisplayed() && $i <  $this->selenium->getDefaultTimeoutToFindElement()) {
                sleep(1);
                $i++;
            }

            return $el;
        } catch( NoSuchElementException $e) {
            $ii++;
            if($ii <  $this->selenium->getDefaultTimeoutToFindElement()) {
                sleep(1);
                return $this->getLink($driver, $target, $ii);
            } else {
                Throw new NoSuchElementException($e);
            }
        } catch (StaleElementReferenceException $e) {
            $ii++;
            if($ii <  $this->selenium->getDefaultTimeoutToFindElement()) {
                sleep(1);
                return $this->getLink($driver, $target, $ii);
            } else {
                Throw new StaleElementReferenceException($e);
            }
        } catch (WebDriverException $e) {
            $ii++;
            if($ii <  $this->selenium->getDefaultTimeoutToFindElement()) {
                sleep(1);
                return $this->getLink($driver, $target, $ii);
            } else {
                Throw new WebDriverException($e);
            }
        }
    }

    protected function getLinks(RemoteWebDriver $driver,$target) {
            $byMethod = $this->getByMethod($target);
            $cleanTarget = $this->getCleanTarget($target);
            $elements = $driver->findElements(WebDriverBy::$byMethod($cleanTarget));

            return $elements;
    }

    protected function getByMethod($target) {

        if(strpos( $target, 'css=') === 0) {
            return 'cssSelector';
        }
        if(strpos($target, 'id=') === 0) {
            return 'id';
        }
        if(strpos($target, 'linkText=') === 0) {
            return 'linkText';
        }
        if(strpos($target, 'xpath=') === 0) {
            return 'xpath';
        }
        if(strpos($target, 'name=') === 0) {
            return 'name';
        }

    }

    protected function getCleanTarget($target) {
        return substr($target, (strpos($target,'=')+1) );
    }

    protected function getCleanLabel($label) {
        return substr($label, 6);
    }

    protected function writeResult($step, $key, $data) {
        $this->result[$step][$key] = $data;
    }

    public function getResult() {
        return $this->result;
    }
}