<?php

require_once(dirname(dirname(__FILE__)) . '/config/config.inc.php');
require_once(dirname(dirname(__FILE__)) . '/lib/SimplePHPCouch/CouchSimple.class.php');
require_once(dirname(dirname(__FILE__)) . '/lib/djvu.view.class.php');

if(!isset($argv[1]) && !isset($arg[2])) { exit(); }

$PageID = $argv[1];
$startkey = array((int)$PageID);
$endkey = array((int)$PageID,"{}");

$directory = $argv[2];

$couch = new CouchSimple(DB_NAME, DB_HOST, DB_PORT, DB_USER, DB_PASS, true);
$url = 'all?startkey=' . urlencode(json_encode($startkey)) . '&endkey=' . str_replace("%22","",urlencode(json_encode($endkey)));
$all = $couch->getView('page', $url);

$obj = json_decode($all);
$rows = $obj->rows;

//sort by pageId & time
foreach($rows as $key => $row) {
  $sort['pageId'][$key] = $row->key[1];
  $sort['time'][$key] = $row->key[2];
}

array_multisort($sort['pageId'], SORT_ASC, $sort['time'], SORT_ASC, $rows);

foreach($rows as $row) {
  $output[$row->key[1]] = $row->value->text;
}

$fp = fopen($directory . "/" . $PageID . ".txt","wb");
fwrite($fp,implode(" \n", $output));
fclose($fp);
