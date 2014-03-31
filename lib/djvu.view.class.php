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

require_once 'djvu.class.php';

class DjVuView extends DjVu {

  private $image_width = 800;
  private $image_url = '';

  public function setImageWidth($width) {
    $this->image_width = $width;
    return $this;
  }

  public function setImageURL($url) {
    $this->image_url = $url;
    return $this;
  }

  public function addFontmetrics() {
    // Compute font sizes
    foreach ($this->page_structure->regions as $region)
    {
      foreach ($region->paragraphs as $paragraph)
      {

        $fontmetrics = new stdClass;

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

        $paragraph->fontmetrics = new stdClass;

        if (isset($fontmetrics->linespacing))
        {
          $paragraph->fontmetrics->linespacing = parent::mean($fontmetrics->linespacing);
        }
        else
        {
          $paragraph->fontmetrics->linespacing = -1;
        }
        if (isset($fontmetrics->ascender))
        {
          $paragraph->fontmetrics->ascender = parent::mean($fontmetrics->ascender);
        }
        if (isset($fontmetrics->capheight))
        {
          $paragraph->fontmetrics->capheight = parent::mean($fontmetrics->capheight);
        }
        if (isset($fontmetrics->descender))
        {
          $paragraph->fontmetrics->descender = parent::mean($fontmetrics->descender);
        }

      }
    }
    return $this;
  }

  public function addLines(){
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
          $ocr_line->id = $line_counter++;
          $ocr_line->fontsize = $fontsize;
          $ocr_line->bbox = $line->bbox;
          $ocr_line->text = preg_replace('/\s+$/', '', $line->text);

          $this->page_structure->lines[] = $ocr_line;
        }
      }
    }
    return $this;
  }

  public function createHTML() {
    $doc = new DOMDocument('1.0');

    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = true;

    $scale = $this->image_width/$this->page_structure->bbox[2];

    $ocr_page = $doc->appendChild($doc->createElement('div'));
    $ocr_page->setAttribute('class', 'ocr_page');

    foreach ($this->page_structure->lines as $line){
      $ocr_line = $ocr_page->appendChild($doc->createElement('div'));

      $ocr_line->setAttribute('id', "line" . $line->id);
      $ocr_line->setAttribute('class', 'ocr_line');
      $ocr_line->setAttribute('contenteditable', 'true');
      $ocr_line->setAttribute('class', 'ocr_line');
      $ocr_line->setAttribute('style', 'font-size:' . $line->fontsize . 'px;line-height:' . $line->fontsize . 'px;position:absolute;left:' . ($line->bbox[0] * $scale) . 'px;top:' . ($line->bbox[3] * $scale)  . 'px;min-width:' . ($scale * ($line->bbox[2] - $line->bbox[0])) . 'px;height:' . ($scale * ($line->bbox[1] - $line->bbox[3])) . 'px;');
      $ocr_line->setAttribute('data-bbox', 'bbox ' . ($line->bbox[0] * $scale) . ' ' . ($line->bbox[3] * $scale)  . ' ' . ($scale *$line->bbox[2])  . ' ' . ($scale *$line->bbox[1]) );

      // original OCR
      $ocr_line->setAttribute('data-ocr', $line->text);

      $ocr_line->appendChild($doc->createTextNode($line->text));
    }

    return $doc->saveHTML();
  }

}

?>
