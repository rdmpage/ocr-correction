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
$parser->addFlag("pageid", array("alias" => "-p", "has_value" => true));
$parser->addFlag("directory", array("alias" => "-d", "default" => ROOT . "/public/examples"));

try {
  $parser->parse();
} catch (\Optparse\Exception $e) {
  usage_and_exit();
}

if ($parser["pageid"]) {
  $db = \OCRCorrection\Database::getInstance();
  $rows = $db->getPageDocuments((int)$parser["pageid"]);

  //sort by pageId & time
  foreach($rows as $key => $row) {
    $sort['pageId'][$key] = $row["key"][1];
    $sort['time'][$key] = $row["key"][2];
  }
  array_multisort($sort['pageId'], SORT_ASC, $sort['time'], SORT_ASC, $rows);

  $output = array();
  foreach($rows as $row) {
    $output[$row["key"][1]] = $row["value"]["text"];
  }

  $fp = fopen("{$parser["directory"]}/{$parser["pageid"]}.txt","wb");
  fwrite($fp, implode(" \n", $output));
  fclose($fp);

  echo "Exported cleaned document for pageId {$parser["pageid"]}" . "\n";
}