<?php
require_once 'CouchRequest.class.php';
require_once 'CouchResponse.class.php';

/**
 * CouchSimple
 *
 * @author Alexander Thiemann
 */
class CouchSimple
{
    private $db;
    private $host;
    private $port;
    private $username;
    private $password;

    private static $okStatus = array(412);

    public function __construct($db, $host, $port = 5984, $username = null, $password = null, $autoCreate=false)
    {
        $this->db = $db;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;

        if ($autoCreate) {
            $this->talkToDB("", COUCH_PUT);
        }
    }

    private function talkToDB($url, $method = COUCH_GET, $data = null)
    {
        $fullURL = "http://".$this->host.":".$this->port."/".$this->db.$url;

        $request = new CouchRequest($fullURL, $method, $data, $this->username, $this->password);
        $resp = $request->send();

        if ($resp->getStatusCode() >= 400 && !in_array($resp->getStatusCode(), self::$okStatus)) {
            throw new Exception("CouchDB-HTTP Error: ".$resp->getBody(), $resp->getStatusCode());
        }

        $response = $resp->getBody();

        if ('application/json' == $resp->getContentType()) {
            $response = json_decode($response);
        }

        return $response;
    }

    public function storeDocWithId($doc)
    {
        return $this->talkToDB("/".$doc->_id, COUCH_PUT, json_encode($doc));
    }

    public function storeDoc($doc)
    {
        return $this->talkToDB("", COUCH_POST, json_encode($doc));
    }

    public function deleteDoc($docId, $rev)
    {
        return $this->talkToDB("/".$docId."?rev=".$rev, COUCH_DELETE);
    }

    public function getView($designDoc, $viewName)
    {
        return $this->talkToDB("/_design/".$designDoc."/_view/".$viewName, COUCH_GET);
    }

    public function getAll()
    {
        return $this->talkToDB('/_all_docs');
    }

    public function getDocById($id)
    {
        return $this->talkToDB('/'.$id);
    }
}