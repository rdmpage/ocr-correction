<?php

function requireFiles() {
  $root = dirname(dirname(__FILE__));

  require_once($root . '/config/config.inc.php');

  $files = glob($root . '/lib/*.php');
  foreach ($files as $file) {
    require_once($file);
  }

  require_once($root . '/Tests/php-webdriver/lib/__init__.php');
}

function loader() {
  date_default_timezone_set("America/New_York");
  requireFiles();
}

spl_autoload_register('loader');