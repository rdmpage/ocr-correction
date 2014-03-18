<?php

// Create editable HTML for DjVu

require_once (dirname(__FILE__) . '/djvu/djvu_structure.php');




//--------------------------------------------------------------------------------------------------
/*
	Create HTML with hOCR for one page
*/
function export_html_fragement_dom($page, $image_url='')
{
	$doc = new DOMDocument('1.0');
	
	// Nice output
	$doc->preserveWhiteSpace = false;
    $doc->formatOutput = true;	

	$scale = $page->image->width/$page->page->bbox[2];
	
	$ocr_page = $doc->appendChild($doc->createElement('div'));
	$ocr_page->setAttribute('class', 'ocr_page');
	$ocr_page->setAttribute('title', 
		'bbox 0 0 ' . ($scale * $page->page->bbox[2]) . ' ' . ($scale * $page->page->bbox[1])
		. '; image ' . $image_url
		);

	foreach ($page->lines as $line)
	{
		$ocr_page->appendChild($doc->createComment('line'));	
		$ocr_line = $ocr_page->appendChild($doc->createElement('div'));
		
		$ocr_line->setAttribute('id', $line->id);	

		
		$ocr_line->setAttribute('class', 'ocr_line');	

		$ocr_line->setAttribute('contenteditable', 'true');	
		
		$ocr_line->setAttribute('class', 'ocr_line');	
		$ocr_line->setAttribute('style', 'font-size:' . $line->fontsize . 'px;line-height:' . $line->fontsize . 'px;position:absolute;left:' . ($line->bbox[0] * $scale) . 'px;top:' . ($line->bbox[3] * $scale)  . 'px;min-width:' . ($scale *($line->bbox[2] - $line->bbox[0])) . 'px;height:' . ($scale *($line->bbox[1] - $line->bbox[3])) . 'px;');	
		
		// hOCR
		$ocr_line->setAttribute('title', 'bbox ' . ($line->bbox[0] * $scale) . ' ' . ($line->bbox[3] * $scale)  . ' ' . ($scale *$line->bbox[2])  . ' ' . ($scale *$line->bbox[1]) );					

		// handle edits
		$ocr_line->setAttribute('onfocus', 'entering(this)');					
		$ocr_line->setAttribute('onblur', 'leaving(this)');					
	
		$ocr_line->appendChild($doc->createTextNode($line->text));
	}
	
	// OCR image
	$div = $ocr_page->appendChild($doc->createElement('div'));
	$div->setAttribute('id', 'ocr_image_container');
	
	$div->setAttribute('style', 'position:absolute;display:none;border:1px solid black;width:100%;');
	
	$img = $div->appendChild($doc->createElement('img'));	
	$img->setAttribute('id', 'ocr_image');
	$img->setAttribute('src', $image_url);
	$img->setAttribute('style', 'position:absolute;');
	
	$ocr_page->appendChild($doc->createComment('Adjust font sizes so that text fits within bounding boxes'));	

	// https://coderwall.com/p/_8jxgw
	$script = $ocr_page->appendChild($doc->createElement('script'));
	$src = 
'$(".ocr_line").each(function(i, obj) {
    
    while ($(this).prop("scrollHeight") > $(this).prop("offsetHeight"))
    {
    	var elNewFontSize;
        elNewFontSize = (parseInt($(this).css("font-size").slice(0, -2)) - 1) + "px";
        return $(this).css("font-size", elNewFontSize);
    }
 });';
 	$script->appendChild($doc->createTextNode($src));
	

	return $doc->saveHTML();
}


//--------------------------------------------------------------------------------------------------

$PageID = 34570741;
//$PageID = 34565801;

// LXV.—On a new Banded Mungoose from Somaliland
$PageID = 16002437;

$xml_filename 	= 'examples/' . $PageID . '.xml';
$image_filename = 'examples/' . $PageID . '.png';

$page_data = structure($xml_filename);
extract_font_sizes($page_data);

//print_r($page_data);

$obj = new stdclass;
$obj->image = new stdclass;
$obj->image->width = 800;
$obj->page = new stdclass;
$obj->page->bbox = $page_data->bbox;
$obj->lines = array();

lines($page_data, $obj);

//print_r($obj);

//echo export_html_dom($obj, '');


// make a page

echo '<html>
<head>
<meta charset="utf-8">
<meta name="ocr-capabilities" content="ocr_carea ocr_line ocr_page ocr_par">
<script src="js/jquery.js"></script><style type="text/css">
	body {
	   margin:0px;
	   padding:0px;
	   background-color: #E9E9E9;
	}
	
	.ocr_page {
		background-color: white;
		/* box-shadow:2px 2px 10px #aaaaaa; */
		/* border:1px solid black; */
		position:relative;
		/* left: 20px;
		top: 20px; */
		width: 800px;
		height: 1316.9313957418px;
	}
	
	/* http://blog.vjeux.com/2011/css/css-one-line-justify.html */
	/* This ensures single line of text is justified */
	.ocr_line {
	  text-align: justify;
	}
	.ocr_line:after {
	  content: "";
	  display: inline-block;
	  width: 100%;
	}</style>
	
	<script src="js/pouchdb-1.1.0.min.js"></script>
	
	<script>
		var pageId = ' . $PageID . ';
	
		var remote = false;
		
	   	if (remote) {
	   		// Write direct to CouchDB
			var db = new PouchDB("http://127.0.0.1:5984/ocr");
		} else {
			// Write to PouchDB, then replicate
			var db = new PouchDB("ocr");
			var remoteCouch = "http://127.0.0.1:5984/ocr";
		}
	
		var before_text = "";
		
		function entering(element) {
			before_text = $(element).html();
				
			// Display image
			var title = $(element).prop("title");
			var parts = title.split(" ");
	
			// Clip to just this part of the image
			var clip = "rect(" + parts[2] + "px," + parts[3] + "px," + parts[4] + "px," + parts[1] + "px)";
			$("#ocr_image").css("clip", clip);
	
			// Move to image
			$("#ocr_image").css("top", -parts[2] + "px");
			$("#ocr_image").css("left", -parts[1] + "px");
	
			var zoom = 800/(parts[3] - parts[1]);
			$("#ocr_image").css("zoom", zoom);
	
			// bottom of element
			var bottom =  $(element).offset().top + $(element).outerHeight(true);
	
			$("#ocr_image_container").css("top", bottom + "px");
			$("#ocr_image_container").css("height",  zoom * (parts[4] - parts[2]) + 2 + "px");
			
			$("#ocr_image_container").show();
	
						
		}

		function leaving(element) {
			console.log($(element).html());
			
			var after_text = $(element).html();
			
			if (after_text != before_text)
			{
			
				var html = $("#edits").html();
			
				html += after_text + "<hr />";
			
				$("#edits").html(html);
				
				// Ten digit timestamp (Javascript is 13, PHP is 10, sigh)
				var timestamp = new Date().getTime();
				
				var timestamp10 = String(timestamp);
				timestamp10 = timestamp10.substring(0,10);
				
				
				// store
				db.post({
					type: "edit",
					time: parseInt(timestamp10),
  					pageId: pageId,
  					lineId: $(element).attr("id"),  					
  					text: after_text
				}, function(err, response) { });
				
				
				if (!remote) {
					// replicate
				 	db.replicate.to(remoteCouch);
				}
				

			}
		}
	</script>
	
</head>
<body>';

echo '
<div style="position:absolute;top:0px;left:800px;width:200px;height:100%;border:1px solid red;">
	<div id="edits"></div>
</div>';

echo export_html_fragement_dom($obj, $image_filename);

echo '<!--Adjust font sizes so that text fits within bounding boxes-->
    <script>$(".ocr_line").each(function(i, obj) {
    
    while ($(this).prop("scrollHeight") > $(this).prop("offsetHeight"))
    {
    	var elNewFontSize;
        elNewFontSize = (parseInt($(this).css("font-size").slice(0, -2)) - 1) + "px";
        return $(this).css("font-size", elNewFontSize);
    }
 });</script>
 
	<script>
		/* grab edits */
		var url = "http://localhost/~rpage/ocr-correction-o/edits.php?pageId=" + pageId;
		$.getJSON(url,
			function(data){
				if (data.status == 200) {
					if (data.results.length != 0) {
						// load edits
						for (var i in data.results) {
							$("#" + data.results[i].lineId).html(data.results[i].text);
						}
					}
				}
			});
	</script>
	
</body>
</html>';

?>