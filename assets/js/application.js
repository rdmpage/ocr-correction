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

/*global jQuery, window, document, self, alert, PouchDB, _, OAuth, console */
var OCRCorrection = (function($) {

  "use strict";

  var _private = {

    settings: {
      edits_url : './edit.php?pageId=',
      diffs_url : './textreplacement.php',
      page_id : 0,
      db: "ocr",
      remote_db: "",
      show_replacements: false,
      show_word_replacements: false,
      oauth_provider: "google_plus"
    },

    vars: {
      pouch_db : {},
      ocr_img_container : {},
      ocr_img : {},
      edit_history: {},
      edit_history_template: {},
      name_tooltip_template: {},
      word_replacement_template: {},
      before_text : "",
      user: { userAvatar : "", userName : "", userUrl : "" },
      gnrd_resource : "http://gnrd.globalnames.org/name_finder.json",
      oauth_profile_url : {
        google_plus : "/plus/v1/people/me",
        github : "user"
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
      this.vars.edit_history_template = $('#ocr_history_template');
      this.vars.name_tooltip_template = $('#name_tooltip_template');
      this.vars.word_replacement_template = $('#word_replacement_template');
      this.vars.ocr_img_container = $('#ocr_image_container');
      this.vars.ocr_img = $("#ocr_image");
      this.vars.pouch_db = new PouchDB(this.settings.db);
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
                      self.vars.before_text = $(this).text();
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
      if(user) { this.vars.user = user; }
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
      var self = this,
          after_text = $(ele).text(),
          timestamp = this.getTime(), //10 digit timestamp for PHP
          history_item = {},
          url = "";

      if (after_text !== this.vars.before_text){
        this.vars.pouch_db.post({
          type: "edit",
          time: timestamp,
          pageId: this.settings.page_id,
          lineId: parseInt($(ele).attr("id").replace("line", ""), 10),
          ocr: $(ele).attr("data-ocr"),
          text: after_text,
          userName : this.vars.user.userName,
          userAvatar: this.vars.user.userAvatar,
          userUrl: this.vars.user.userUrl
        }, function(err, response) {
          if(err) { console.log(err); }
          self.unusedVariables(response);
        });

        url = this.vars.gnrd_resource + "?text=" + encodeURIComponent(after_text);
        this.findNames($(ele), url, 0);

        this.setUserDefaults(this.vars.user);
        history_item = $.extend({},this.vars.user,{ text : after_text });
        $(_.template(this.vars.edit_history_template.html(), history_item)).prependTo(this.vars.edit_history).hide().slideDown("slow");
        $(ele).addClass("ocr_edited");
        this.synchronize();
      }
    },

    getTime: function() {
      return parseInt(String(new Date().getTime()).substring(0,10), 10);
    },

    findNames: function(ele, url, counter) {
      var self = this, names = "";

      ele.data("name-counter", counter);

      $.ajax({
        type: "GET",
        url: url,
        dataType: 'json',
        success: function(response) {
          if (response.status === 303 && ele.data("name-counter") < 10) {
            window.setTimeout(function() {
              counter += 1;
              ele.data("name-counter", counter);
              self.findNames(ele, response.token_url, counter);
            }, 2000);
          } else if(response.status === 200) {
            if(response.names.length > 0) {
              names = $.map(response.names, function(i) { return i.identifiedName; });
              ele.tooltipster({
                content: $(_.template(self.vars.name_tooltip_template.html(), { names : names.join(", ") })),
                interactive: true
              });
              ele.tooltipster('show');
            }
          }
        }
      });
    },

    synchronize: function() {
      if(this.settings.remote_db) {
        this.vars.pouch_db.replicate.to(this.settings.remote_db);
      }
    },

    getEdits: function() {
      var self = this;
/*
WIP: offline retrieval from PouchDB
      var fun = { map : function map(doc) { emit([doc.pageId, doc.time], doc); }, reduce:false },
          options = { startkey : [this.settings.page_id], endkey : [this.settings.page_id, this.getTime()] };

      this.vars.pouch_db.query(fun, options, function(err, response) {
        $.each(response.rows, function() {
          $("#line" + this.value.lineId).html(this.value.text).addClass("ocr_edited");
        });
      });
*/
      if(this.settings.remote_db) {
        $.ajax({
          type: "GET",
          url: this.settings.edits_url + this.settings.page_id,
          dataType: 'json',
          success: function(response) {
            $.each(response.rows, function() {
              $("#line" + this.value.lineId).html(this.value.text).addClass("ocr_edited");
              self.setUserDefaults(this.value);
              self.vars.edit_history.prepend(_.template(self.vars.edit_history_template.html(), this.value));
            });
          }
        });
      }
    },
  
    getTextReplacements: function() {
      var lines = $('.ocr_line');

      if(this.settings.remote_db) {
        $.ajax({
          type: "GET",
          url: this.settings.diffs_url,
          dataType: 'json',
          success: function(response) {
            $.each(lines, function(i) {
              var line = $("#line" + i);
              $.each(response.rows, function() {
                line.highlight(this.value, { className: 'highlight-orange' });
              });
            });
          }
        });
      }
    },

    getWordAt: function(str, pos) {
      var left = str.substr(0, pos),
          right = str.substr(pos),
          letters = /^[0-9a-zA-Z]+$/,
          leftPos = 0, rightPos = 0;

      if (left.length > 0) {
        leftPos = left.length - 1;
        while (left.substr(leftPos,1).match(letters) && leftPos > 0) {
          leftPos -= 1;
        }
        if (!left.substr(leftPos,1).match(letters)) { leftPos += 1; }
      }

      if (right.length > 0) {
        rightPos = 0;
        while (right.substr(rightPos,1).match(letters) && rightPos < right.length - 1) {
          rightPos += 1;
        }
        if (right.substr(rightPos,1).match(letters)) { rightPos += 1; }
      }

      return left.substr(leftPos) + right.substr(0, rightPos);
    },

    findNextNonHtmlText: function(str, text, pos) {
      var htmlPos = str.indexOf("<", pos),
          nextPos = str.indexOf(text, pos),
          inHtml = true, endPos;

      if (htmlPos !== -1 && nextPos > htmlPos) {
        while(inHtml) {
          endPos = str.indexOf(">", htmlPos);
          htmlPos = str.indexOf("<", endPos);
          nextPos = str.indexOf(text, endPos);

          if (htmlPos === -1 || nextPos < htmlPos || nextPos === -1) {
            inHtml = false;
          }
        }
      }

      return nextPos;
    },

    getWordReplacements: function() {
      var self = this,
          lines = $('.ocr_line');

      if(this.settings.remote_db) {

        $.ajax({
          type: "GET",
          url: this.settings.diffs_url,
          dataType: 'json',
          success: function(response) {
            $.each(lines, function(i) {
              var line = $("#line" + i),
                  newText = line.html();

              $.each(response.rows, function() {
                if (this.key.length > 1) { //not sure we care about single char changes
                  var pos = self.findNextNonHtmlText(newText, this.key, 0),
                      word = "", startPos;

                  while (pos !== -1) {
                    word = self.getWordAt(newText, pos);

                    //work out word start pos :-/
                    startPos = pos - word.indexOf(this.key);
                    newText = newText.slice(0, startPos) + 
                    _.template(self.vars.word_replacement_template.html(), { key : this.key, value : this.value, word : word })
                    + newText.slice(startPos + word.length);

                    //move to last replacement
                    pos = newText.lastIndexOf("</span>") + 7;
                    pos = self.findNextNonHtmlText(newText, this.key, pos);
                  }
                }
              });

              line.html(newText);
            });
          }
        });
      }
    },

    setUser: function(res) {
      switch(this.settings.oauth_provider) {
        case "github":
          this.vars.user = {
            userName : res.name,
            userAvatar : res.avatar_url,
            userUrl : res.html_url
          };
        break;

        case "google_plus":
          this.vars.user = {
            userName : res.displayName,
            userAvatar : res.image.url,
            userUrl : res.url
          };
        break;
      }
    },

    bindAuthentication: function() {
      var self = this;

      $('#ocr_signin').on("click", function(e) {
        e.preventDefault();
        OAuth.popup(self.settings.oauth_provider, function(error, result) {
          if (error) { return; }
          result.get(self.vars.oauth_profile_url[self.settings.oauth_provider]).done(function(res) {
            self.setUser(res);
            $.cookie('ocr_correction', self.vars.user, { expires: 7 });
            window.location.reload(true);
          });
        });
      });
      $('#ocr_signout').on("click", function(e) {
        e.preventDefault();
        $.removeCookie('ocr_correction');
        window.location.reload(true);
      });
    },

    unusedVariables: function() {
      return;
    }

  };

  return {
    initialize: function(args) {
      $.extend(_private.settings, args);
      _private.initialize();
    }
  };

}(jQuery));