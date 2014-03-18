ocr-correction
==============

OCR correction in browser for Leiden Hackathon

## Setting up CouchDB

### CORS for CouchDB

<pre>curl -X PUT http://127.0.0.1:5984/_config/httpd/enable_cors -d '"true"'
curl -X PUT http://127.0.0.1:5984/_config/cors/origins -d '"*"'
curl -X PUT http://127.0.0.1:5984/_config/cors/credentials -d '"true"'
curl -X PUT http://127.0.0.1:5984/_config/cors/methods -d '"GET, PUT, POST, HEAD, DELETE"'
curl -X PUT http://127.0.0.1:5984/_config/cors/headers -d '"accept, authorization, content-type, origin"'</pre>

Then restart CouchDB.

### Create database and view

Create database called "ocr", then create the view page/edits:

```javascript
function(doc) {
  emit([doc.pageId, doc.time, doc.lineId], doc.text);
}
```


## URLs for page image and XML

http://biostor.org/bhl_page_xml.php?PageID=34570741
http://biostor.org/bhl_page_bw_image.php?PageID=34570741
