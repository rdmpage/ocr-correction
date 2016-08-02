<?php

date_default_timezone_set("America/New_York");

function switchConf($restore = false) {
  $config_dir = dirname(__DIR__) . '/config/';

  $conf = array(
      'prod' => $config_dir . 'config.php',
      'test' => $config_dir . 'config.test.php'
  );

  if (!$restore) {
      if (!file_exists($conf['prod'] . ".old")) {
          if (file_exists($conf['prod'])) {
              copy($conf['prod'], $conf['prod'] . ".old");
          }
          copy($conf['test'], $conf['prod']);
      }
  } else {
      if (file_exists($conf['prod'] . ".old")) {
          rename($conf['prod'] . ".old", $conf['prod']);
      }
  }
}

function requireFiles() {
  $root = dirname(__DIR__);
  require_once $root . '/vendor/autoload.php';
}


function warningOff()
{
  error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
}

function warningOn()
{
  error_reporting(-1);
}

function loader() {
  switchConf();
  requireFiles();
  warningOff();
}

function unloader() {
  switchConf('restore');
  warningOn();
}

spl_autoload_register(__NAMESPACE__.'\loader');
register_shutdown_function(__NAMESPACE__.'\unloader');