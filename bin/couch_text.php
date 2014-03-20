<?php

require_once(dirname(dirname(__FILE__)) . '/config/config.inc.php');
require_once(dirname(dirname(__FILE__)) . '/lib/SimplePHPCouch/CouchSimple.class.php');
require_once(dirname(dirname(__FILE__)) . '/lib/djvu.view.class.php');

if(!isset($argv[1])) { exit(); }

$PageID = $argv;
$startkey = array((int)$PageID);
$endkey = array((int)$PageID,time());

$couch = new CouchSimple(DB_NAME, DB_HOST, DB_PORT, DB_USER, DB_PASS, true);