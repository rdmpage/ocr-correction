<?php

require_once(dirname(__FILE__) . '/config/config.inc.php');
require_once(dirname(__FILE__) . '/lib/SimplePHPCouch/CouchSimple.class.php');

$couch = new CouchSimple(DB_PROTOCOL, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
$diffs = $couch->getView('textDiff', 'textDiff');

header('Content-Type: application/json');
echo $diffs;

?>