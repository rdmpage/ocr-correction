<?php

/**
 * Unit tests for DjVuView
 */

class DjVuViewTest extends PHPUnit_Framework_TestCase {

   protected $djvu;

   protected function setUp() {
      $root = dirname(dirname(__FILE__));
      $PageID = 16002437;
      $PageWidth = 800;
      $xml_filename = $root . '/examples/' . $PageID . '.xml';
      $image_filename = $root . '/examples/' . $PageID . '.png';

      $this->djvu = new DjVuView($xml_filename);
      $this->djvu->setImageWidth($PageWidth)
           ->setImageURL($image_filename)
           ->addFontmetrics()
           ->addLines();
   }

    public function test_regions_size() {
      $this->assertEquals(count($this->djvu->page_structure->regions), 2);
    }

    public function test_lines_size() {
      $this->assertEquals(count($this->djvu->page_structure->regions[1]->paragraphs[1]->lines), 8);
    }

}
