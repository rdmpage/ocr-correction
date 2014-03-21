<?php

/**
 * IntegrationTest
 */

class IntegrationTest extends PHPUnit_Framework_TestCase {

  protected $webDriver;
  protected $base_url;

/* TODO: configure .travis/yml and script the config for CouchDB

  public function setUp() {
    $this->base_url = HTTP_HOST;
    $host = 'http://localhost:4444/wd/hub';
    $capabilities = array(WebDriverCapabilityType::BROWSER_NAME => BROWSER);
    $this->webDriver = RemoteWebDriver::create($host, $capabilities);
  }

  public function tearDown() {
    $this->webDriver->close();
  }

  public function testPageTitle() {
    $this->webDriver->get($this->base_url);
    $title = $this->webDriver->getTitle();
    $this->assertEquals('OCR Correction', $title);
  }
*/

  public function testDummy() {
    $this->assertEquals('test', 'test');
  }

}
?>