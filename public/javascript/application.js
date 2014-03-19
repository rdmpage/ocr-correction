/*global jQuery, window, document, self, alert, PouchDB */
var OCRCorrection = (function($) {

  "use strict";

  var _private = {

    settings: {
      edit_url : './edit.php?pageId=',
      page_id : 0,
      page_width : 800,
      db: ""
    },

    vars: {
      img_container : {},
      edit_history: {},
      before_text : "",
      pouch: {}
    },

    init: function() {
      this.setVariables();
      this.setFontSize();
      this.bindActions();
      this.getEdits();
    },

    setVariables: function() {
      this.vars.edit_history = $("#ocr_edit_history");
      this.vars.img_container = $('#ocr_image_container');
      this.vars.pouch = new PouchDB(this.settings.db);
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
                      self.postEdit(this); })
                    .on('keypress', function(e) {
                      var code = e.keyCode || e.which;
                      if(code === 13) {
                        e.preventDefault();
                        $(this).next().focus();
                      }
                    });
    },

    showPopUp: function(ele) {
      var bbox = $(ele).data("bbox"),
          parts = bbox.split(" "),
          clip = "rect(" + parts[2] + "px," + parts[3] + "px," + parts[4] + "px," + parts[1] + "px)",
          zoom = this.settings.page_width/(parts[3] - parts[1]),
          bottom =  $(ele).offset().top + $(ele).outerHeight(true);

      this.vars.img_container.find("img").css({"clip" : clip, "top": -parts[2] + "px", "left" : -parts[1] + "px", "zoom" : zoom});
      this.vars.img_container.css({"top" : bottom + "px", "height" :  zoom * (parts[4] - parts[2]) + 2 + "px"}).show();
    },

    postEdit: function(ele) {
      var after_text = $(ele).html(),
          timestamp = parseInt(String(new Date().getTime()).substring(0,10), 10), //10 digit timestamp for PHP
          history = this.vars.edit_history.find("div"),
          html = history.html();

      if (after_text !== this.vars.before_text){
        html += after_text + "<hr />";
        history.html(html);

        this.vars.pouch.post({
          type: "edit",
          time: timestamp,
          pageId: this.settings.page_id,
          lineId: $(ele).attr("id"),  
          ocr: $(ele).attr("data-ocr"),
          text: after_text
        });
        
        this.replicate();
      }
    },
    
    replicate: function() {
      if(this.settings.db.indexOf("http://") !== -1) {
        this.vars.pouch.replicate.to(this.settings.db);
      }
    },

    getEdits: function() {
      $.ajax({
        type: "GET",
        url: this.settings.edit_url + this.settings.page_id,
        dataType: 'json',
        success: function(response) {
          $.each(response.rows, function() {
            $("#" + this.value.lineId).html(this.value.text);
          });
        }
      });
    }

  };
  
  return {
    init: function(args) {
      $.extend(_private.settings, args);
      _private.init();
    }
  };

}(jQuery));