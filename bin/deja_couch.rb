#!/usr/bin/env ruby
# encoding: utf-8

require 'find'
require 'couchdb'

path = "/Users/dshorthouse/Sites/ocr-correction/examples"

server = CouchDB::Server.new "localhost", 5984
database = CouchDB::Database.new server, "ocr"
database.create_if_missing!

Dir.chdir(path)
Find.find(".").each do |f|
  next if f.include? "DS_Store"
  if File.file?(f) && File.extname(f) == ".xml"
    #WIP: parse the DjVu xml to load lines into CouchDB
  end
end

#EXAMPLE:
#document = CouchDB::Document.new database, "pageId" => 12345, "lineId" => 12345, "time" => Time.now.to_i, "ocr" => "This is the text", "text" => "This is the edited text"
#document.save