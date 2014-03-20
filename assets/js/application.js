/*global jQuery, window, document, self, alert, PouchDB, _ */
var OCRCorrection = (function($) {

  "use strict";

  var _private = {

    settings: {
      edits_url : './edit.php?pageId=',
      diffs_url : './textreplacement.php',
      page_id : 0,
      page_width : 800,
      couch_db: "",
      pouch_db: "ocr",
      show_replacements: false,
      show_word_replacements: false
    },

    vars: {
      ocr_img_container : {},
      ocr_img : {},
      edit_history: {},
      before_text : "",
      pouch: {},
      user: {
        userAvatar : "",
        userName : "",
        userUrl : ""
      }
    },

    initialize: function() {
      $.cookie.json = true;
      this.setVariables();
      this.setFontSize();
      this.bindActions();
      this.loadUser();
      this.getEdits();
      if (this.settings.show_replacements) { this.getTextReplacements(); }
      if (this.settings.show_word_replacements) { this.getWordReplacements(); }
      this.bindAuthentication();
    },

    setVariables: function() {
      this.vars.edit_history = $("#ocr_edit_history");
      this.vars.edit_history_template = $('#ocr_history_item');
      this.vars.ocr_img_container = $('#ocr_image_container');
      this.vars.ocr_img = $("#ocr_image");
      this.vars.pouch = new PouchDB(this.settings.couch_db);
      //WIP this.vars.pouch = new PouchDB(this.settings.pouch_db);
    },

    setFontSize: function() {
      var elNewFontSize;

      $.each($(".ocr_line"), function() {
        if($(this).prop("scrollHeight") > $(this).prop("offsetHeight")) {
          elNewFontSize = (parseInt($(this).css("font-size").slice(0, -2), 10) - 1) + "px";
          $(this).css("font-size", elNewFontSize);
        }
      });
    },

    bindActions: function() {
      var self = this;

      $('.ocr_page').find('.ocr_line')
                    .on('focus', function() {
                      self.vars.before_text = $(this).html();
                      self.showPopUp(this); })
                    .on('blur', function() {
                      self.closePopUp();
                      self.postEdit(this); })
                    .on('keypress', function(e) {
                      var code = e.keyCode || e.which;
                      if(code === 13) {
                        e.preventDefault();
                        $(this).next().focus();
                      }
                    });
    },

    loadUser: function() {
      var user = $.cookie("ocr_correction");
      if(user){ this.vars.user = user; }
    },
    
    setUserDefaults: function(obj) {
      if(!obj.userName) { obj.userName = "Anonymous"; }
      if(!obj.userUrl) { obj.userUrl = "#"; }
      if(!obj.userAvatar) { obj.userAvatar = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2NCIgaGVpZ2h0PSI2NCI+PHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjZWVlIj48L3JlY3Q+PHRleHQgdGV4dC1hbmNob3I9Im1pZGRsZSIgeD0iMzIiIHk9IjMyIiBzdHlsZT0iZmlsbDojYWFhO2ZvbnQtd2VpZ2h0OmJvbGQ7Zm9udC1zaXplOjEycHg7Zm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7ZG9taW5hbnQtYmFzZWxpbmU6Y2VudHJhbCI+NjR4NjQ8L3RleHQ+PC9zdmc+"; }
    },

    showPopUp: function(ele) {
      var bbox = $(ele).data("bbox"),
          parts = bbox.split(" "),
          clip = "rect(" + parts[2] + "px, " + parts[3] + "px, " + parts[4] + "px, " + parts[1] + "px)",
          top =  $(ele).offset().top + $(ele).outerHeight(true) - 35,
          left = $(ele).offset().left - 25;

      this.vars.ocr_img.css({"clip" : clip}).show();
      this.vars.ocr_img_container.css({
        "top" : top + "px",
        "left" : left + "px",
        "height" : (parts[4] - parts[2]) + 10 + "px",
        "width" : $(ele).width() + 10 + "px"}).show();
    },

    closePopUp: function() {
      this.vars.ocr_img_container.hide();
      this.vars.ocr_img.hide();
    },

    postEdit: function(ele) {
      var after_text = $(ele).html(),
          timestamp = this.getTime(), //10 digit timestamp for PHP
          history_item = {};

      if (after_text !== this.vars.before_text){
        this.setUserDefaults(this.vars.user);
        history_item = $.extend({},this.vars.user,{ text : after_text });
        $(_.template(this.vars.edit_history_template.html(), history_item)).prependTo(this.vars.edit_history).hide().slideDown("slow");
        this.vars.pouch.post({
          type: "edit",
          time: timestamp,
          pageId: this.settings.page_id,
          lineId: $(ele).attr("id"),  
          ocr: $(ele).attr("data-ocr"),
          text: after_text,
          userName : this.vars.user.userName,
          userAvatar: this.vars.user.userAvatar,
          userUrl: this.vars.user.userUrl
        });
        
        $(ele).addClass("ocr_edited");
        this.synchronize();
      }
    },

    getTime: function() {
      return parseInt(String(new Date().getTime()).substring(0,10), 10);
    },

    synchronize: function() {
      if(this.settings.couch_db.indexOf("http://") !== -1) {
        //WIP: do we really want to synchronize the entire db?
        this.vars.pouch.replicate.sync(this.settings.couch_db, { continuous : true });
      }
    },

    getEdits: function() {
      var self = this;
/*
WIP: offline retrieval from PouchDB
      var fun = { map : function map(doc) { emit([doc.pageId, doc.time], doc); }, reduce:false },
          options = { startkey : [this.settings.page_id], endkey : [this.settings.page_id, this.getTime()] };

      this.vars.pouch.query(fun, options, function(err, response) {
        $.each(response.rows, function() {
          $("#" + this.value.lineId).html(this.value.text).addClass("ocr_edited");
        });
      });
*/
      if(this.settings.couch_db) {
        $.ajax({
          type: "GET",
          url: this.settings.edits_url + this.settings.page_id,
          dataType: 'json',
          success: function(response) {
            $.each(response.rows, function() {
              $("#" + this.value.lineId).html(this.value.text).addClass("ocr_edited");
              self.setUserDefaults(this.value);
              self.vars.edit_history.prepend(_.template(self.vars.edit_history_template.html(), this.value));
            });
          }
        });
      }
    },
  
    getTextReplacements: function() {
      var lines = $('.ocr_line');

      if(this.settings.couch_db) {
        $.ajax({
          type: "GET",
          url: this.settings.diffs_url,
          dataType: 'json',
          success: function(response) {
            $.each(lines, function(i) {
              var line = $("#line" + i),
                  newText = line.html();
              $.each(response.rows, function() {
                if (line.html().indexOf(this.key) !== -1) {
                  newText = newText.replace(this.key,
                    "<span style=\"background-color:orange\">" + this.value + "</span>");
                }
              });
              line.html(newText);
            });
          }
        });
      }
    },

    getWordReplacements: function() {
      function getWordAt(str, pos) {
        var left = str.substr(0, pos);
        var right = str.substr(pos);
        left = left.replace(/^.+ /g, "");
        right = right.replace(/ .+$/g, "");
        return left + right;
      }
  
        if(this.settings.couch_db) {
    
      $.ajax({
            type: "GET",
            url: this.settings.diffs_url,
            dataType: 'json',
            success: function(response) { 
        for (var lineNum=0; lineNum >=0; lineNum++) {     
          var line = $("#line" + lineNum);                    
        
          if (typeof line != "object")
          { 
            break; 
          }
          
          var newText = line.html();        
        
          $.each(response.rows, function() {      
            var pos = newText.indexOf(this.key);      
            while (pos != -1) {         
              var word = getWordAt(newText, pos);
              newText = newText.replace(word, 
                "<span title=\"Replace " + this.key + " with " + this.value + "\" style=\"background-color:lavender\">" + word + "</span>");
              
              line.html(newText);
            
              //move to last replacement
              pos = newText.lastIndexOf("</span>") + 7;
            
              pos = newText.indexOf(this.key, pos)
            }
          });
        }
        }
      });
      }
    },
  
    bindAuthentication: function() {
      $('#ocr_signin').on("click", function(e) {
        e.preventDefault();
        OAuth.popup("github", function(error, result) {
          if (error) { return; }
          result.get("user").done(function(res) {
            $.cookie('ocr_correction', { userAvatar : res.avatar_url, userName : res.name, userUrl : res.url }, { expires: 7 });
            window.location.reload(true);
          });
        });
      });
      $('#ocr_signout').on("click", function(e) {
        e.preventDefault();
        $.removeCookie('ocr_correction');
        window.location.reload(true);
      });
    }

  };

  return {
    initialize: function(args) {
      $.extend(_private.settings, args);
      _private.initialize();
    }
  };

}(jQuery));