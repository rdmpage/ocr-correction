<?php

/**
 * Unit tests for DjVuView
 */

class DjVuTest extends PHPUnit_Framework_TestCase {

   protected $djvu;

   protected function setUp() {
      $root = dirname(dirname(__FILE__));
      $PageID = 16002437;
      $xml_filename = $root . '/examples/' . $PageID . '.xml';
      $this->djvu = new DjVu($xml_filename);
   }

   public function test_cleaned_xml() {
     $dirty_string = "This is a unit separator &#31; and a vertical tab &#11;";
     $clean_string = DjVu::clean_xml($dirty_string);
     $this->assertEquals(strlen($dirty_string),strlen($clean_string)+10);
   }

   public function test_presence_of_page_structure() {
     $this->assertTrue(property_exists($this->djvu, 'page_structure'));
   }

   public function test_presence_of_regions() {
     $this->assertTrue(property_exists($this->djvu->page_structure, 'regions'));
   }

   public function test_dpi() {
     $this->assertEquals($this->djvu->page_structure->dpi, 500);
   }

   public function test_construction_of_bbox() {
     $bbox = array(0,3186,1903,0);
     $this->assertEquals($this->djvu->page_structure->bbox, $bbox);
   }

   public function test_construction_of_text_bbox() {
     $text_bbox = array(1903,0,0,3186);
     $this->assertEquals($this->djvu->page_structure->text_bbox, $text_bbox);
   }
   
   public function test_construction_of_line_coordinates() {
     $line = $this->djvu->page_structure->regions[0]->paragraphs[0]->lines[0];
     $text = $line->text;
     $bbox = $line->bbox;
     $this->assertEquals($text, "On a new Banded Mungoose from Somaliland. 531 ");
     $this->assertEquals($bbox, array(363, 150, 1649, 92));
   }

   public function test_merging_of_coordinates() {
     $coord1 = array(196, 254, 1653, 198);
     $coord2 = array(194, 313, 1652, 256);
     $expected = array(194, 313, 1653, 198);
     $this->assertEquals(DjVu::merge_coordinates($coord1, $coord2), $expected);
   }

   public function test_extraction_of_words() {
     $word = $this->djvu->page_structure->regions[0]->paragraphs[0]->lines[0]->words[0];
     $text = $word->text;
     $bbox = $word->bbox;
     $this->assertEquals($text, "On");
     $this->assertEquals($bbox, array(363, 136, 426, 94));
   }

   public function test_calculation_of_mean() {
     $array = array(12,10,44,24,12);
     $mean = DjVu::mean($array);
     $this->assertEquals($mean, 20.4);
   }

}

?>