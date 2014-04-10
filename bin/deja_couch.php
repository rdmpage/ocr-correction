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

/*
File: deja_couch.php
Description: Extracts all lines in all DjVu files to seed CouchDB
*/

require_once(dirname(dirname(__FILE__)) . '/config/config.inc.php');
require_once(dirname(dirname(__FILE__)) . '/lib/SimplePHPCouch/CouchSimple.class.php');
require_once(dirname(dirname(__FILE__)) . '/lib/djvu.view.class.php');

if(!isset($argv[1])) { exit(); }

$directory = $argv[1];

$couch = new CouchSimple(DB_PROTOCOL, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);

$dir_iterator = new RecursiveDirectoryIterator($directory); 
foreach(new RecursiveIteratorIterator($dir_iterator) as $file =>$key) {
  $filetypes = array("xml"); 
  $filetype = pathinfo($file, PATHINFO_EXTENSION);
  if (in_array(strtolower($filetype), $filetypes)) {
    $page_id = (int)rtrim(basename($file), ".xml");
    echo "Making CouchDB entries for {$page_id}" . "\n";
    $djvu = new DjVuView($file);
    $djvu->addFontmetrics();
    $djvu->addLines();
    foreach($djvu->page_structure->lines as $line) {
      $doc = new stdClass();
      $doc->type = "original";
      $doc->pageId = $page_id;
      $doc->lineId = $line->id;
      $doc->ocr = $line->text;
      $doc->text = $line->text;
      $doc->time = time();
      $couch->storeDoc($doc);
    }
  }
}

?>