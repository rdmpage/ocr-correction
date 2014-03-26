<?php
function switchConf($restore = false) {
  $config_dir = dirname(dirname(__FILE__)) . '/config/';

  $conf = array(
    'prod' => $config_dir . 'config.inc.php',
    'test' => $config_dir . 'config.inc.test.php'
  );

  if(!$restore) {
    if(!file_exists($conf['prod'] . ".old")) {
      if(file_exists($conf['prod'])) { copy($conf['prod'], $conf['prod'] . ".old"); }
      copy($conf['test'], $conf['prod']);
    }
  } else {
    if(file_exists($conf['prod'] . ".old")) { rename($conf['prod'] . ".old", $conf['prod']); }
  }

}

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
  switchConf();
  requireFiles();
}

function unloader() {
  switchConf('restore');
}

spl_autoload_register('loader');
register_shutdown_function('unloader');