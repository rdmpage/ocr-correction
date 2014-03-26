<?php

require_once(dirname(__FILE__) . '/config/config.inc.php');
require_once(dirname(__FILE__) . '/lib/SimplePHPCouch/CouchSimple.class.php');

if(empty($_REQUEST['pageId'])) {
  exit();
}

$PageID = $_REQUEST['pageId'];
$startkey = array((int)$PageID);
$endkey = array((int)$PageID,time());

$couch = new CouchSimple(DB_PROTOCOL, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
$edits = $couch->getView('page', 'edits?startkey=' . urlencode(json_encode($startkey)) . '&endkey=' .  urlencode(json_encode($endkey)));

header('Content-Type: application/json');
echo $edits;

?>