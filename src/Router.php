<?php
namespace OCRCorrection;

use \Phroute\Phroute\Autoloader;
use \Phroute\Phroute\RouteCollector;
use \Phroute\Phroute\Dispatcher;

class Router
{

    /**
     * Class constructor
     */
    public function __construct()
    {
        mb_internal_encoding("UTF-8");
        mb_http_output("UTF-8");

        //set the default timezone
        date_default_timezone_set("America/New_York");
        
        $this->_setRoutes();
    }

    /**
     * Set the controller for each route
     *
     * @return views
     */
    private function _setRoutes()
    {
        $router = new RouteCollector();

        $router->get('/', function () {
          return $this->_main();
        });

        $router->post('/edit/{id:i}', function ($id) {
          return $this->_edit($id);
        });

        try {
            $dispatcher = new Dispatcher($router->getData());
            $parsed_url = parse_url(str_replace(":", "%3A", $_SERVER['REQUEST_URI']), PHP_URL_PATH);
            $response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $parsed_url);
            echo $response;
        } catch(\Exception $e) {
            echo $this->_render404();
        }

    }

    private function _main()
    {
      return $this->_twig()->render("main.html");
    }

    private function _edit($id)
    {
/*
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
*/
    }

    /**
     * Load twig templating engine
     *
     * @return twig object
     */
    private function _twig()
    {
        $loader = new \Twig_Loader_Filesystem(ROOT. "/views");
        $cache = ROOT . "/public/tmp";
        $twig = new \Twig_Environment($loader, array('cache' => $cache, 'auto_reload' => true));

        $twig->addGlobal('language', 'en');

        return $twig;
    }

    /**
     * Render a 404 document
     *
     * @return void
     */
    private function _render404()
    {
        http_response_code(404);
        return $this->_twig()->render("404.html");
    }
}