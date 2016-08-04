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
use \Doctrine\CouchDB\CouchDBClient as CouchDB;

class Database
{
  static $_client;
  private $_link;

  /**
   * Constructor
   *
   * @return void
   */
  public function __construct()
  {
    $config = array(
      'host' => DB_HOST,
      'port' => DB_PORT,
      'user' => DB_USER,
      'password' => DB_PASS,
      'dbname' => DB_NAME,
      'ssl' => (DB_PROTOCOL == 'https') ? true : false
    );
    $this->_link = CouchDB::create($config);
  }

  public function initialize()
  {
    $this->createDatabase();
    $this->createPagesDesignDocument();
    $this->createTextDiffDesignDocument();
  }

  /**
   * Get the database instance
   *
   * @return instance
   */
  public static function getInstance()
  {
    if (!(self::$_client instanceof self)) {
      self::$_client = new self();
    }
    return self::$_client;
  }

  public function getDatabaseInfo()
  {
    return $this->_link->getDatabaseInfo(DB_NAME);
  }

  public function createDatabase()
  {
    $this->_link->createDatabase(DB_NAME);
  }

  public function deleteDatabase()
  {
    $this->_link->deleteDatabase(DB_NAME);
  }

  public function createPagesDesignDocument()
  {
    $this->_link->createDesignDocument('page', new PagesDesignDocument());
  }

  public function createTextDiffDesignDocument()
  {
    $this->_link->createDesignDocument('textDiff', new TextDiffDesignDocument());
  }

  public function getPageDocuments($id)
  {
    $query = $this->_link->createViewQuery('page', 'edits');
    $query->setIncludeDocs(true);
    $query->setStartKey(array($id));
    $query->setEndKey(array($id, time()));
    $result = $query->execute();
    return $result->toArray();
  }

  public function getTextReplacements()
  {
    $query = $this->_link->createViewQuery('textDiff', 'textDiff');
    $query->setIncludeDocs(true);
    $result = $query->execute();
    return $result->toArray();
  }

  public function postPageDocument($content)
  {
    return $this->_link->postDocument(array(
      'type' => 'edit',
      'time' => (int)$content['time'],
      'pageId' => (int)$content['pageId'],
      'lineId' => (int)$content['lineId'],
      'ocr' => $content['ocr'],
      'text' => $content['text']
    ));
  }

}