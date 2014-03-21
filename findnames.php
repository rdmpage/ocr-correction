<?php

	require_once(dirname(__FILE__) . '/lib/namefinder.class.php');

	$finder = new NameFinder();
	$results = $finder->LookupNames($_GET["text"]);
	
	header('Content-Type: application/json');
	
	echo $results;

?>