<?php

/*******************************************************************************
The MIT License (MIT)

Copyright (c) 2014
Roderic Page, David P. Shorthouse, Kevin Richards, Marko Tähtinen
and the agents they represent

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*******************************************************************************/

class DjVu {

  private $filename;
  public $page_structure;

  public static function mean($a){
    $average = 0;
    $n = count($a);
    $sum = 0;
    foreach ($a as $x) {
      $sum += $x;
    }
    $average = $sum/$n;
    return $average;
  }

  public static function clean_xml($xml) {
    $xml = str_replace("&#31;", "", $xml);
    $xml = str_replace("&#11;", "", $xml);
    return $xml;
  }

  public static function merge_coordinates($c1, $c2){
    $coords = array();
    $coords[0] = min($c1[0], $c2[0]); // min-x
    $coords[1] = max($c1[1], $c2[1]); // max-y
    $coords[2] = max($c1[2], $c2[2]); // max-x
    $coords[3] = min($c1[3], $c2[3]); // min-y
    return $coords;
  }

  function __construct($filename) {
    $this->filename = $filename;
    $this->build_page_structure();
  }

  public function build_page_structure() {
    $xml = file_get_contents($this->filename);

    // Remove any spurious things which break XML parsers
    $xml = self::clean_xml($xml);

    $dom = new DOMDocument;
    $dom->loadXML($xml);
    $xpath = new DOMXPath($dom);

    // Create page object from XML file to hold things such as bounding boxes
    $bbox = $this->page_bbox($xpath);

    $this->page_structure = new stdClass;
    $this->page_structure->regions = array();
    $this->page_structure->dpi = 0;

    // Get DPI
    $nodes = $xpath->query('//PARAM[@name="DPI"]/@value');
    foreach($nodes as $node)
    {
      $this->page_structure->dpi = $node->firstChild->nodeValue;
    }

    // Get physical page bounding box
    $this->page_structure->bbox = array(0,0,0,0);
    $nodes = $xpath->query('//OBJECT/@width');
    foreach($nodes as $node)
    {
      $this->page_structure->bbox[2] = $node->firstChild->nodeValue;
    }
    $nodes = $xpath->query('//OBJECT/@height');
    foreach($nodes as $node)
    {
      $this->page_structure->bbox[1] = $node->firstChild->nodeValue;
    }
    $this->page_structure->text_bbox = array($this->page_structure->bbox[2],0,0,$this->page_structure->bbox[1]);

      //------------------------------------------------------------------------------------------
    // Regions, paragraphs, and lines on page
    $regions = $xpath->query('//REGION');
    foreach($regions as $region)
    {
      $region_object = new stdClass;

      // Initialise region bounding box
      $region_object->bbox = array(10000,0,0,10000);

      // Paragraphs
      $region_object->paragraphs = array();

      $paragraphs = $xpath->query('PARAGRAPH', $region);
      foreach ($paragraphs as $paragraph)
      {
        $paragraph_object = new stdClass;

        // Initialise paragraph bounding box
        $paragraph_object->bbox = array(10000,0,0,10000);

        $paragraph_object->line_heights = array();

        // Lines
        $paragraph_object->lines = array();
        $lines = $xpath->query('LINE', $paragraph);
        foreach ($lines as $line)
        {
          $line_object = new stdClass;
          $line_object->text = '';

          // Add line bbox to paragraph bbox
          $line_object->bbox = $this->line_coordinates($xpath, $line, $line_object->text);
          $paragraph_object->bbox = self::merge_coordinates($paragraph_object->bbox, $line_object->bbox);

          // Extract words
          $line_object->words = $this->extract_words($xpath, $line);

          // Font info
          $line_object->baseline = $this->page_structure->bbox[1];
          $line_object->capheight = 0;
          $line_object->descender = $this->page_structure->bbox[1];
          $line_object->ascender = 0;

          foreach ($line_object->words as $word)
          {
            //echo $word->text . " ";

            // Get font dimensions for this line

            if (preg_match('/[tdfhklb]/', $word->text))
            {
              $line_object->ascender = max($line_object->ascender, $word->bbox[3]);
              $line_object->baseline = min($line_object->baseline, $word->bbox[1]);
            }

            if (preg_match('/[qypgj]/', $word->text))
            {
              $line_object->descender = min($line_object->descender, $word->bbox[1]);
            }

            if (preg_match('/[A-Z0-9]/', $word->text))
            {
              $line_object->capheight = max($line_object->capheight, $word->bbox[3]);
              $line_object->baseline = min($line_object->baseline, $word->bbox[1]);
            }

          } 

          $line_object->fontmetrics = new stdClass;

          if ($line_object->baseline != $this->page_structure->bbox[1])
          {
            $line_object->fontmetrics->baseline = $line_object->baseline;

            if ($line_object->ascender != 0)
            {
              $line_object->fontmetrics->ascender = $line_object->baseline - $line_object->ascender;
            }

            if ($line_object->capheight != 0)
            {
              $line_object->fontmetrics->capheight = $line_object->baseline - $line_object->capheight;
            }

            if ($line_object->descender != $this->page_structure->bbox[1])
            {
              $line_object->fontmetrics->descender = $line_object->descender - $line_object->baseline;
            }
          }

          $paragraph_object->baselines[] = $line_object->baseline;

          $paragraph_object->lines[] = $line_object;
        }

        // Add paragraph bbox to region bbox
        $region_object->bbox = self::merge_coordinates($region_object->bbox, $paragraph_object->bbox);    

        $region_object->paragraphs[] = $paragraph_object;
      }

      $this->page_structure->regions[] = $region_object;
    }
    return $this;
  }

  private function page_bbox($xpath) {
    $bbox = array(0,0,0,0);

    $nodeCollection = $xpath->query('//OBJECT/@height');
    foreach($nodeCollection as $node) {
      $bbox[1] = $node->firstChild->nodeValue;
    }

    $nodeCollection = $xpath->query('//OBJECT/@width');
    foreach($nodeCollection as $node) {
      $bbox[2] = $node->firstChild->nodeValue;
    }

    return $bbox;
  }

  private function line_coordinates($xpath, $node, &$line_text){
    $line_bbox = array(100000,0,0,100000);
    $line_text = '';
    $word_count = 0;
    $words = $xpath->query ('WORD', $node);
    foreach($words as $word)
    {
      // coordinates
      if ($word->hasAttributes()) 
      { 
        $attributes2 = array();
        $attrs = $word->attributes; 

        foreach ($attrs as $i => $attr)
        {
          $attributes2[$attr->name] = $attr->value; 
        }
      }

      $line_text .= $word->firstChild->nodeValue . ' ';
      $coords = explode(",", $attributes2['coords']);

      $line_bbox = self::merge_coordinates($line_bbox, $coords);
      $word_count++;
    }

    return $line_bbox;
  }

  private function extract_words($xpath, $node){
    $x = 0;
    $y = 0;

    $word_list = array();

    $words = $xpath->query('WORD', $node);
    foreach($words as $word)
    {
      // coordinates
      if ($word->hasAttributes())
      { 
        $attributes2 = array();
        $attrs = $word->attributes;

        foreach ($attrs as $i => $attr)
        {
          $attributes2[$attr->name] = $attr->value; 
        }
      }

      $coords = explode(",", $attributes2['coords']);

      $w = new stdClass;
      $w->text = $word->firstChild->nodeValue;
      $w->bbox = $coords;

      $word_list[] = $w;
    }

    return $word_list;
  }

}

?>