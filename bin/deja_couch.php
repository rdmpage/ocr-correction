<?php

require_once(dirname(dirname(__FILE__)) . '/config/config.inc.php');
require_once(dirname(dirname(__FILE__)) . '/lib/SimplePHPCouch/CouchSimple.class.php');
require_once(dirname(dirname(__FILE__)) . '/lib/djvu.view.class.php');

if(!isset($argv[1])) { exit(); }

$directory = $argv[1];

$couch = new CouchSimple(DB_NAME, DB_HOST, DB_PORT, DB_USER, DB_PASS, true);

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