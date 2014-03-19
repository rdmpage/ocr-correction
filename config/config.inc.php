<?php

// CouchDB database name
defined("DB_NAME") || define("DB_NAME", "ocr");

// CouchDB host
defined("DB_HOST") || define("DB_HOST", "localhost");

// CouchDB port
defined("DB_PORT") || define("DB_PORT", 5984);

// CouchDB login name
defined("DB_USER") || define("DB_USER", null);

// CouchDB password
defined("DB_PASS") || define("DB_PASS", null);

date_default_timezone_set('UTC');

?>