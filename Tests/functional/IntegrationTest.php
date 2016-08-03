<?php

/**
 * IntegrationTest
 */
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase {

  protected $webDriver;
  protected $base_url;

  public static function setUpBeforeClass() {
    $root = dirname(__DIR__);
    require_once $root . '../../config/config.php';
  }

  protected function setUp() {
    $this->base_url = HTTP_HOST;
    $host = 'http://localhost:4444/wd/hub';
    $capabilities = array(WebDriverCapabilityType::BROWSER_NAME => BROWSER, WebDriverCapabilityType::HANDLES_ALERTS => true);
    $this->webDriver = RemoteWebDriver::create($host, $capabilities);
    $this->webDriver->manage()->window()->setSize(new WebDriverDimension(1280, 1024));
  }

  public function tearDown() {
    if(method_exists($this->webDriver, 'close')) {
        $this->webDriver->close();
    }
  }

  public function testPageTitle() {
    $this->webDriver->get($this->base_url);
    $title = $this->webDriver->getTitle();
    $this->assertEquals('OCR Correction', $title);
  }
}
?>