<?php
/**
 * Couch Response
 *
 * @author Alexander Thiemann
 */
class CouchResponse
{
    private $status_code = 200;
    private $content_type = '';
    private $body = '';

    public function __construct($status_code, $content_type, $body)
    {
        $this->status_code = $status_code;
        $this->content_type = $content_type;
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function getContentType()
    {
        return $this->content_type;
    }
}