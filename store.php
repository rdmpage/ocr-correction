<?php

// Import page into CouchDB, store lines as edits (user 0)


require_once (dirname(__FILE__) . '/couchsimple.php');
require_once (dirname(__FILE__) . '/djvu/djvu_structure.php');


//--------------------------------------------------------------------------------------------------

$PageID = 34570741;
//$PageID = 34565801;

//$PageID = 16002437;
//$PageID = 16002438;

$xml_filename = 'examples/' . $PageID . '.xml';

$page_data = structure($xml_filename);
extract_font_sizes($page_data);

$obj = new stdclass;
$obj->image = new stdclass;
$obj->image->width = 800;
$obj->page = new stdclass;
$obj->page->bbox = $page_data->bbox;
$obj->lines = array();

lines($page_data, $obj);

// store content as edits

$now = time();

foreach ($obj->lines as $line)
{
	//print_r($line);
	
	$edit = new stdclass;
	
	$edit->user = 0;
	$edit->time = $now;
	$edit->text = $line->text;
	$edit->pageId = $PageID;
	$edit->lineId = $line->id;
	
	//print_r($edit);
	
	// Store
	$resp = $couch->send("POST", "/" . $config['couchdb_options']['database'], json_encode($edit));

	echo $resp;
	
}


?>