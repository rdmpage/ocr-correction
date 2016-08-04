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
$parser->addFlag("destroy", array("alias" => "-d"));

try {
  $parser->parse();
} catch (\Optparse\Exception $e) {
  usage_and_exit();
}

if ($parser["create"]) {
  $db = \OCRCorrection\Database::getInstance();
  $db->initialize();
  echo sprintf("Database %s and its views created.", DB_NAME) . "\n";
}

if ($parser["destroy"]) {
  $db = \OCRCorrection\Database::getInstance();
  $db->deleteDatabase();
  echo sprintf("Database %s destroyed.", DB_NAME) . "\n";
}