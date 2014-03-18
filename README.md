ocr-correction
==============

OCR correction in browser for Leiden Hackathon


## CORS for CouchDB

```curl -X PUT http://127.0.0.1:5984/_config/httpd/enable_cors -d '"true"'
curl -X PUT http://127.0.0.1:5984/_config/cors/origins -d '"*"'
curl -X PUT http://127.0.0.1:5984/_config/cors/credentials -d '"true"'
curl -X PUT http://127.0.0.1:5984/_config/cors/methods -d '"GET, PUT, POST, HEAD, DELETE"'
curl -X PUT http://127.0.0.1:5984/_config/cors/headers -d '"accept, authorization, content-type, origin"'```

Then restart CouchDB.

## URLs for page image and XML

http://biostor.org/bhl_page_xml.php?PageID=34570741
http://biostor.org/bhl_page_bw_image.php?PageID=34570741
