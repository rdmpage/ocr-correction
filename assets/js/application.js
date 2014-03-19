/*global jQuery, window, document, self, alert, PouchDB */
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
	  show_replacements: false
    },

    vars: {
      ocr_img_container : {},
      ocr_img : {},
      edit_history: {},
      before_text : "",
      pouch: {}
    },

    init: function() {
      this.setVariables();
      this.setFontSize();
      this.bindActions();
      this.getEdits();
	  if (this.settings.show_replacements) this.getTextReplcements();
    },

    setVariables: function() {
      this.vars.edit_history = $("#ocr_edit_history");
      this.vars.ocr_img_container = $('#ocr_image_container');
      this.vars.ocr_img = $("#ocr_image");
      this.vars.pouch = new PouchDB(this.settings.couch_db);
      //WIP
      //this.vars.pouch = new PouchDB(this.settings.pouch_db);
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
          history = this.vars.edit_history;

      if (after_text !== this.vars.before_text){
        history.append(this.formatHistoryItem(after_text));
        this.vars.pouch.post({
          type: "edit",
          time: timestamp,
          pageId: this.settings.page_id,
          lineId: $(ele).attr("id"),  
          ocr: $(ele).attr("data-ocr"),
          text: after_text
        });
        
        $(ele).addClass("ocr_edited");

        this.synchronize();
      }
    },

    getTime: function() {
      return parseInt(String(new Date().getTime()).substring(0,10), 10);
    },

    formatHistoryItem: function(after_text) {
      return '<div class="ocr_edit_item">' + after_text + '</div>';
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
              self.vars.edit_history.append(self.formatHistoryItem(this.value.text));
            });
          }
        });		
      }
    },
	
	getTextReplcements: function()
	{	
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
					
				$.each(response.rows, function() {
					var newText = line.html();
					if (newText.indexOf(this.key) != -1) {								
						newText = newText.replace(this.key, 
							"<span style=\"background-color:orange\">" + this.value + "</span>");
							
						line.html(newText);
					}
				});
			}
		  }
        });
	  }
	}

  };

  return {
    init: function(args) {
      $.extend(_private.settings, args);
      _private.init();
    }
  };

}(jQuery));