<?php
/**
 * CouchRequest
 *
 * @author Alexander Thiemann
 */


class CouchRequest
{
    const COUCH_GET = 'GET';
    const COUCH_DELETE = 'DELETE';
    const COUCH_POST = 'POST';
    const COUCH_PUT = 'PUT';

    private $url = "";
    private $method = CouchRequest::COUCH_GET;
    private $data = null;
    private $headers = array();

    /**
     * Create a CouchRequest
     *
     * @throws Exception if invalid method is passed
     *
     * @param string $url
     * @param string $method
     * @param string $data
     * @param string $username
     * @param string $password
     */
    public function __construct($url, $method = CouchRequest::COUCH_GET, $data = null, $username = null, $password = null)
    {
        $this->url = $url;
        $allowed = array(CouchRequest::COUCH_GET, CouchRequest::COUCH_DELETE, CouchRequest::COUCH_POST, CouchRequest::COUCH_PUT);

        if (!in_array($method, $allowed)) {
            throw new Exception('Error, invalid HTTP-METHOD: '.$method);
        }
        $this->method = $method;
        $this->data = $data;
        if (null !== $this->data)
        {
            $this->headers['Content-Type'] = 'application/json';
            $this->headers['Content-Length'] = strlen($this->data);
        }

        if (null !== $username && null != $password)
        {
            $this->headers['Authorization'] = 'Basic '.base64_encode($username . ':' . $password);
        }

    }

    /**
     * Fire the request
     *
     * @return CouchResponse
     */
    public function send()
    {
        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key.': '.$value;
        }

        $curl = curl_init($this->url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
        curl_setopt($curl, CURLOPT_USERAGENT, 'SimplePHPCouch 1.0');

        $body = curl_exec($curl);
        $info = curl_getinfo($curl);

        $response = new CouchResponse($info['http_code'], $info['content_type'], $body);

        curl_close($curl);

        return $response;
    }
}