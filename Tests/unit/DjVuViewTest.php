<?php
/**
 * Unit tests for DjVuView
 */
class DjVuViewTest extends DjVuTest
{

  protected $djvu;

  protected function setUp()
  {
    $id = 16002437;
    $width = 800;
    $xml_filename = ROOT . '/public/examples/' . $id . '.xml';
    $image_filename = ROOT . '/public/examples/' . $id . '.png';

    $this->djvu = new \OCRCorrection\DjVuView($xml_filename);
    $this->djvu->setImageWidth($width)
         ->setImageURL($image_filename)
         ->addFontmetrics()
         ->addLines();
  }

  public function test_addition_of_fontmetrics()
  {
    $fontmetrics = (array)$this->djvu->page_structure->regions[0]->paragraphs[0]->lines[0]->fontmetrics;
    $expected = array('baseline' => 134, 'ascender' => 40, 'capheight' => 39, 'descender' => 16);
    $this->assertEquals($fontmetrics, $expected);
  }

  public function test_regions_size()
  {
    $this->assertEquals(count($this->djvu->page_structure->regions), 2);
  }

  public function test_lines_size()
  {
    $this->assertEquals(count($this->djvu->page_structure->regions[1]->paragraphs[1]->lines), 8);
  }

}
