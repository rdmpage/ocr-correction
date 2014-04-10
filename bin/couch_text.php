<?php

/*******************************************************************************
The MIT License (MIT)

Copyright (c) 2014
Roderic Page, David P. Shorthouse, Kevin Richards, Marko Tähtinen
and the agents they represent

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*******************************************************************************/

/*
File: couch_text.php
Description: flattens all user edits on lines in a single page into a text file
*/

require_once(dirname(dirname(__FILE__)) . '/config/config.inc.php');
require_once(dirname(dirname(__FILE__)) . '/lib/SimplePHPCouch/CouchSimple.class.php');
require_once(dirname(dirname(__FILE__)) . '/lib/djvu.view.class.php');

if(!isset($argv[1]) && !isset($arg[2])) { exit(); }

$PageID = $argv[1];
$startkey = array((int)$PageID);
$endkey = array((int)$PageID,"{}");

$directory = $argv[2];

$couch = new CouchSimple(DB_PROTOCOL, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
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
