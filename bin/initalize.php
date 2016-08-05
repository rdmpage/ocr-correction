#!/usr/bin/env php
<?php

require_once(__DIR__ . "/../config/config.php");
require_once(__DIR__ . "/../vendor/autoload.php");

use \CHH\Optparse;

$parser = new Optparse\Parser();

function usage_and_exit()
{
    global $parser;
    fwrite(STDERR, "{$parser->usage()}\n");
    exit(1);
}

$parser->addFlag("help", array("alias" => "-h"), "usage_and_exit");
$parser->addFlag("create", array("alias" => "-c"));
$parser->addFlag("seed", array("alias" => "-s"));
$parser->addFlag("destroy", array("alias" => "-x"));
$parser->addFlag("directory", array("alias" => "-d", "default" => ROOT . "/public/examples"));

try {
  $parser->parse();
} catch (\Optparse\Exception $e) {
  usage_and_exit();
}

if ($parser["create"]) {
  $db = \OCRCorrection\Database::getInstance();
  $db->initialize();
  echo sprintf("Database %s and its views created.", DB_NAME) . "\n";
} else if ($parser["destroy"]) {
  $db = \OCRCorrection\Database::getInstance();
  $db->deleteDatabase();
  echo sprintf("Database %s destroyed.", DB_NAME) . "\n";
} else if ($parser["seed"]) {
  $db = \OCRCorrection\Database::getInstance();
  $dir_iterator = new \RecursiveDirectoryIterator($parser["directory"]); 
  foreach(new \RecursiveIteratorIterator($dir_iterator) as $file => $key) {
    $filetypes = array("xml"); 
    $filetype = pathinfo($file, PATHINFO_EXTENSION);
    if (in_array(strtolower($filetype), $filetypes)) {
      $page_id = (int)rtrim(basename($file), ".xml");
      echo "Making CouchDB entries for {$page_id}" . "\n";
      $djvu = new \OCRCorrection\DjVuView($file);
      $djvu->addFontmetrics();
      $djvu->addLines();
      foreach($djvu->page_structure->lines as $line) {
        $params = array(
          "pageId" => $page_id,
          "lineId" => $line->id,
          "ocr" => $line->text,
          "text" => $line->text,
          "time" => time()
        );
        $db->postPageDocument($params, "original");
      }
    }
  }
}