OCR correction in browser for Leiden Hackathon
==============================================

Web interface for correcting OCR text from BHL. Goal is to provide a simple interface for interactive editing of text, as well as tools to make inferences from the edits (e.g., frequency of certain kinds of OCR errors).

## Requirements

This code requires [CouchDB](http://couchdb.apache.org), PHP, and a local web server. It also uses [PouchDB](http://pouchdb.com).

## Setting up CouchDB

Once you have installed CouchDB you need to enable CORS support so that PouchDB can talk to CouchDB.

### CORS for CouchDB

You can either configure CouchDB from the command line (if you have curl), or edit the configuration in CouchDB http://127.0.0.1:5984/_utils/config.html

<pre>curl -X PUT http://127.0.0.1:5984/_config/httpd/enable_cors -d '"true"'
curl -X PUT http://127.0.0.1:5984/_config/cors/origins -d '"*"'
curl -X PUT http://127.0.0.1:5984/_config/cors/credentials -d '"true"'
curl -X PUT http://127.0.0.1:5984/_config/cors/methods -d '"GET, PUT, POST, HEAD, DELETE"'
curl -X PUT http://127.0.0.1:5984/_config/cors/headers -d '"accept, authorization, content-type, origin"'</pre>

Then restart CouchDB.

### Create database and view

Create a CouchDB database called "ocr", then create the view page/edits:

```javascript
function(doc) {
  emit([doc.pageId, doc.time], doc);
}
```

## URLs for page image and XML

You can fetch page images and XML from BioStor.

http://biostor.org/bhl_page_xml.php?PageID=34570741
http://biostor.org/bhl_page_bw_image.php?PageID=34570741
