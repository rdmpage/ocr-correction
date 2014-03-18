<?php

class DjVu {

  private $filename;
  private $image_width = 800;
  private $image_url;

  function __construct($filename) {
    $this->filename = $filename;
    $this->build_page_structure();
    $this->add_fontmetrics();
    $this->add_lines();
  }

  public function setImageWidth($width) {
    $this->image_width = $width;
  }

  public function setImageURL($url) {
    $this->image_url = $url;
  }

  public function build_page_structure() {

    // Get XML
    $xml = file_get_contents($this->filename);

    // Remove any spurious things which break XML parsers
    $xml = $this->clean_xml($xml);

    $dom = new DOMDocument;
    $dom->loadXML($xml);
    $xpath = new DOMXPath($dom);

    // Create page object from XML file to hold things such as bounding boxes
    $bbox = $this->page_bbox($xpath);

    $this->page_structure = new stdclass;
    $this->page_structure->regions = array();
    $this->page_structure->dpi = 0;

    // Get DPI
    $nodes = $xpath->query ('//PARAM[@name="DPI"]/@value');
    foreach($nodes as $node)
    {
      $this->page_structure->dpi = $node->firstChild->nodeValue;
    }

    // Get physical page bounding box
    $this->page_structure->bbox = array(0,0,0,0);
    $nodes = $xpath->query ('//OBJECT/@width');
    foreach($nodes as $node)
    {
      $this->page_structure->bbox[2] = $node->firstChild->nodeValue;
    }
    $nodes = $xpath->query ('//OBJECT/@height');
    foreach($nodes as $node)
    {
      $this->page_structure->bbox[1] = $node->firstChild->nodeValue;
    }
    $this->page_structure->text_bbox = array($this->page_structure->bbox[2],0,0,$this->page_structure->bbox[1]);

      //------------------------------------------------------------------------------------------
    // Regions, paragraphs, and lines on page
    $regions = $xpath->query ('//REGION');    
    foreach($regions as $region)
    {
      $region_object = new stdclass;

      // Initialise region bounding box
      $region_object->bbox = array(10000,0,0,10000);

      // Paragraphs
      $region_object->paragraphs = array();

      $paragraphs = $xpath->query ('PARAGRAPH', $region);
      foreach ($paragraphs as $paragraph)
      {
        $paragraph_object = new stdclass;

        // Initialise paragraph bounding box
        $paragraph_object->bbox = array(10000,0,0,10000);

        // 
        $paragraph_object->line_heights = array();

        // Lines
        $paragraph_object->lines = array();
        $lines = $xpath->query ('LINE', $paragraph);
        foreach ($lines as $line)
        {
          $line_object = new stdclass;
          $line_object->text = '';

          // Add line bbox to paragraph bbox
          $line_object->bbox = $this->line_coordinates($xpath, $line, $line_object->text);
          $paragraph_object->bbox = $this->merge_coordinates($paragraph_object->bbox, $line_object->bbox);

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

          $line_object->fontmetrics = new stdclass;

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

          /*
          echo " ascender: " . $line_object->ascender . "\n";
          echo "capheight: " . $line_object->capheight . "\n";
          echo " baseline: " . $line_object->baseline . "\n";
          echo "descender: " . $line_object->descender . "\n";
          echo "---\n";
          echo " ascender: " . ($line_object->baseline - $line_object->ascender) . "\n";
          echo "capheight: " . ($line_object->baseline - $line_object->capheight) . "\n";
          echo " baseline: 0\n";
          echo "descender: " . ($line_object->descender - $line_object->baseline) . "\n";
          echo "---\n";
          */


          $paragraph_object->baselines[] = $line_object->baseline;

          $paragraph_object->lines[] = $line_object;
        }

        // Add paragraph bbox to region bbox
        $region_object->bbox = $this->merge_coordinates($region_object->bbox, $paragraph_object->bbox);    

        $region_object->paragraphs[] = $paragraph_object;
      }

      $this->page_structure->regions[] = $region_object;
    }
  }

  private function clean_xml($xml) {
    $xml = str_replace("&#31;", "", $xml);
    $xml = str_replace("&#11;", "", $xml);
    return $xml;
  }

  private function mean($a){
    $average = 0;
    $n = count($a);
    $sum = 0;
    foreach ($a as $x)
    {
      $sum += $x;
    }
    $average = $sum/$n;
    return $average;
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

      $line_bbox = $this->merge_coordinates($line_bbox, $coords);
      $word_count++;
    }

    return $line_bbox;
  }

  private function merge_coordinates($c1, $c2){
    $coords=array();
    $coords[0] = min($c1[0], $c2[0]); // min-x
    $coords[1] = max($c1[1], $c2[1]); // max-y
    $coords[2] = max($c1[2], $c2[2]); // max-x
    $coords[3] = min($c1[3], $c2[3]); // min-y
    return $coords;
  }

  private function extract_words($xpath, $node){
    $x = 0;
    $y = 0;

    $word_list = array();

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

      $coords = explode(",", $attributes2['coords']);

      $w = new stdclass;
      $w->text = $word->firstChild->nodeValue;
      $w->bbox = $coords;

      $word_list[] = $w;
    }

    return $word_list;
  }

  public function add_fontmetrics() {
    // Compute font sizes
    foreach ($this->page_structure->regions as $region)
    {
      foreach ($region->paragraphs as $paragraph)
      {

        $fontmetrics = new stdclass;

        $count = 0;
        $last_baseline = 0;
        foreach ($paragraph->lines as $line)
        {

          if ($count > 0 && isset($line->fontmetrics->baseline))
          {
            $fontmetrics->linespacing[] = $line->fontmetrics->baseline - $last_baseline;
          }
          $count++;
          $last_baseline = $line->fontmetrics->baseline;

          if (isset($line->fontmetrics->ascender)) { $fontmetrics->ascender[] = $line->fontmetrics->ascender; }
          if (isset($line->fontmetrics->capheight)) { $fontmetrics->capheight[] = $line->fontmetrics->capheight; }
          if (isset($line->fontmetrics->descender)) { $fontmetrics->descender[] = $line->fontmetrics->descender; }
        }

        $paragraph->fontmetrics = new stdclass;

        if (isset($fontmetrics->linespacing))
        {
          $paragraph->fontmetrics->linespacing = $this->mean($fontmetrics->linespacing);
        }
        else
        {
          $paragraph->fontmetrics->linespacing = -1;
        }
        if (isset($fontmetrics->ascender))
        {
          $paragraph->fontmetrics->ascender = $this->mean($fontmetrics->ascender);
        }
        if (isset($fontmetrics->capheight))
        {
          $paragraph->fontmetrics->capheight = $this->mean($fontmetrics->capheight);
        }
        if (isset($fontmetrics->descender))
        {
          $paragraph->fontmetrics->descender = $this->mean($fontmetrics->descender);
        }

      }
    }
  }

  public function add_lines(){
    $scale = $this->image_width/$this->page_structure->bbox[2];

    $line_counter = 0;

    foreach ($this->page_structure->regions as $region){

      foreach ($region->paragraphs as $paragraph){
        // font height
        $fontsize = 0;

        // Compute font height based on capheight of font
        // e.g for Times New Roman we divide by 0.662
        if (isset($paragraph->fontmetrics->capheight))
        {
          $fontsize = $paragraph->fontmetrics->capheight/0.662;
        }

        $linespacing = $paragraph->fontmetrics->linespacing;
        if ($linespacing != -1)
        {
          $linespacing = round($linespacing/$this->page_structure->dpi * 72);
        }

        $fontsize *= $scale;

        // text
        foreach ($paragraph->lines as $line){
          $ocr_line = new stdclass;
          $ocr_line->id = "line" . $line_counter++;
          $ocr_line->fontsize = $fontsize;
          $ocr_line->bbox = $line->bbox;
          $ocr_line->text = preg_replace('/\s+$/', '', $line->text);

          $this->page_structure->lines[] = $ocr_line;
        }
      }
    }

  }
  

    public function createHTML() {
      $doc = new DOMDocument('1.0');

      // Nice output
      $doc->preserveWhiteSpace = false;
      $doc->formatOutput = true;  

      $scale = $this->image_width/$this->page_structure->bbox[2];

      $ocr_page = $doc->appendChild($doc->createElement('div'));
      $ocr_page->setAttribute('class', 'ocr_page');
      $ocr_page->setAttribute('title', 
        'bbox 0 0 ' . ($scale * $this->page_structure->bbox[2]) . ' ' . ($scale * $this->page_structure->bbox[1])
        . '; image ' . $this->image_url
        );

      foreach ($this->page_structure->lines as $line){
        $ocr_page->appendChild($doc->createComment('line'));  
        $ocr_line = $ocr_page->appendChild($doc->createElement('div'));

        $ocr_line->setAttribute('id', $line->id); 


        $ocr_line->setAttribute('class', 'ocr_line'); 

        $ocr_line->setAttribute('contenteditable', 'true'); 

        $ocr_line->setAttribute('class', 'ocr_line'); 
        $ocr_line->setAttribute('style', 'font-size:' . $line->fontsize . 'px;line-height:' . $line->fontsize . 'px;position:absolute;left:' . ($line->bbox[0] * $scale) . 'px;top:' . ($line->bbox[3] * $scale)  . 'px;min-width:' . ($scale *($line->bbox[2] - $line->bbox[0])) . 'px;height:' . ($scale *($line->bbox[1] - $line->bbox[3])) . 'px;');  

        // hOCR
        $ocr_line->setAttribute('title', 'bbox ' . ($line->bbox[0] * $scale) . ' ' . ($line->bbox[3] * $scale)  . ' ' . ($scale *$line->bbox[2])  . ' ' . ($scale *$line->bbox[1]) );

        // original OCR
        $ocr_line->setAttribute('data-ocr', $line->text);         

        $ocr_line->appendChild($doc->createTextNode($line->text));
      }

      return $doc->saveHTML();
    }
}

?>