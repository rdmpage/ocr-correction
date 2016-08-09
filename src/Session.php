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

use \OAuth\OAuth2\Service\Google;
use \OAuth\Common\Storage\Session as OAuthSession;
use \OAuth\Common\Consumer\Credentials as OAuthCredentials;
use \OAuth\Common\Http\Uri as OAuthUri;

class Session
{

  private $currentUri;
  private $storage;
  private $credentials;
  private $serviceFactory;

  public function __construct()
  {
    $uriFactory = new OAuthUri\UriFactory();
    $this->currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
    $this->currentUri->setQuery('');
    
    $this->serviceFactory = new \OAuth\ServiceFactory();

    $this->storage = new OAuthSession();
    $this->credentials = new OAuthCredentials(OAUTH_KEY, OAUTH_SECRET, $this->currentUri->getAbsoluteUri());
  }

  public function init()
  {
    $googleService = $this->serviceFactory->createService(
      'google', 
      $this->credentials, 
      $this->storage, 
      array('userinfo_email', 'userinfo_profile')
    );

    if (!empty($_GET['code'])) {
        // retrieve the CSRF state parameter
        $state = isset($_GET['state']) ? $_GET['state'] : null;

        // This was a callback request from google, get the token
        $googleService->requestAccessToken($_GET['code'], $state);

        // Send a request with it
        $result = json_decode($googleService->request('userinfo'), true);

        $session_data = array(
          'userName' => $result['name'],
          'userUrl' => $result['link'],
          'userAvatar' => $result['picture']
        );
        
        $_SESSION["ocr-correction"] = $session_data;
        $host = parse_url(HTTP_HOST)['host'];
        $cookie_timeout = time() + (2 * 7 * 24 * 60 * 60);
        setcookie("ocr-correction", json_encode($session_data, JSON_UNESCAPED_UNICODE), $cookie_timeout, "/", $host);
        header('Location: ' . HTTP_HOST);

    } elseif (!empty($_GET['go']) && $_GET['go'] === 'go') {
        $url = $googleService->getAuthorizationUri();
        header('Location: ' . $url);
    } else {
      session_unset();
      session_destroy();
      $host = parse_url(HTTP_HOST)['host'];
      setcookie("ocr-correction", "", time() - 3600, "/", $host);
      header('Location: ' . HTTP_HOST);
    }
  }
}