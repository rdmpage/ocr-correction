<?php

// fetch page made of latest edits and generate HTML

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/lib.php');

$callback = '';


// If no query parameters 
if (count($_GET) == 0)
{
	//default_display();
	echo 'hi';
	exit(0);
}

if (isset($_GET['callback']))
{	
	$callback = $_GET['callback'];
}

$PageID = $_GET['pageId'];

$startkey 	= array((Integer)$PageID);
$endkey 	= array((Integer)$PageID,time());

$url = '_design/page/_view/edits?startkey=' . urlencode(json_encode($startkey))
. '&endkey=' .  urlencode(json_encode($endkey));
	
$resp = $couch->send("GET", "/" . $config['couchdb_options']['database'] . "/" . $url);

$response_obj = json_decode($resp);

$obj = new stdclass;
$obj->status = 200;
$obj->results = array();

foreach ($response_obj->rows as $row)
{
	$edit = new stdclass;
	$edit->lineId = $row->key[2];
	$edit->text = $row->value;
	$obj->results[] = $edit;
}

api_output($obj, $callback);



?>