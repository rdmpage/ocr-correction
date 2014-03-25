<?php

/**
 * IntegrationTest
 */

class IntegrationTest extends PHPUnit_Framework_TestCase {

  protected $webDriver;
  protected $base_url;

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
/*
  public function testOriginalTextAppears() {
    //TODO: how to get correct clipping?
  }
  
  public function testAnonUserEditInScroller() {
    
  }

  public function testLogIn() {
    
  }
  
  public function testEditIsPreserved() {
    //TODO: have to do page refresh here
  }
  
  public function testAuthenticatedUserEditInScroller() {
    
  }

  public function testHighlightingCorrection() {
    
  }
  
  public function testScientificNameRecognized() {
    
  }
  
  public function testWordReplacements() {
    //TODO: have to do page refresh here
  }
*/
}
?>