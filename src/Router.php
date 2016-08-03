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

        $router->get('/{id:\d+}?', function ($id = 34570741) {
          return $this->_main($id);
        });

        $router->get('/edits/{id:\d+}', function ($id = 34570741) {
          return $this->_edits($id);
        });

        $router->post('/edit', function () {
          return $this->_edit($_POST);
        });

        $router->get('/textreplacement', function () {
          return $this->_textReplacements();
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

    private function _main($id)
    {
      $page_width = 800;
      $xml_filename = "public/examples/{$id}.xml";
      $image_filename = "public/examples/{$id}.png";

      $djvu = new DjVuView($xml_filename);
      $djvu->setImageWidth(800)
           ->setImageURL($image_filename)
           ->addFontmetrics()
           ->addLines();

      $config = array(
        'id' => $id,
        'image_filename' => $image_filename,
        'content' => $djvu->createHTML(PERMIT_ANON)
      );
      return $this->_twig()->render("main.html", $config);
    }

    private function _edits($id)
    {
      header('Content-Type: application/json');
      $db = Database::getInstance();
      $docs = $db->getPageDocuments((int)$id);
      echo json_encode($docs);
    }

    private function _edit($params)
    {
      header('Content-Type: application/json');
      $db = Database::getInstance();
      $response = $db->postPageDocument($params);
      echo json_encode($response);
    }

    private function _textReplacements()
    {
      header('Content-Type: application/json');
      $db = Database::getInstance();
      $docs = $db->getTextReplacements();
      echo json_encode($docs);
    }
    
    /**
     * Load twig templating engine
     *
     * @return twig object
     */
    private function _twig()
    {
        $loader = new \Twig_Loader_Filesystem(ROOT . "/views");
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