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

require_once(dirname(__FILE__) . '/config/config.inc.php');
require_once(dirname(__FILE__) . '/lib/djvu.view.class.php');

//TODO: $remote_db is used as either remote CouchDB (works as localhost) or Cloudant (no worky!) to sync from PouchDB
$remote_db = DB_PROTOCOL . "://" . DB_HOST . ":" . DB_PORT . "/" . DB_NAME;

$page_width = 800;

/**************************************************
  CANNED DATA THAT COULD BE PULLED FROM ELSEWHWERE
**************************************************/
$page_id = 16002437;
$xml_filename = 'examples/' . $page_id . '.xml';
$image_filename = 'examples/' . $page_id . '.png';
/*************************************************/

$djvu = new DjVuView($xml_filename);
$djvu->setImageWidth($page_width)
     ->setImageURL($image_filename)
     ->addFontmetrics()
     ->addLines();

$html = $djvu->createHTML();
?>

<html>
<head>
<meta charset="utf-8">
<meta name="ocr-capabilities" content="ocr_carea ocr_line ocr_page ocr_par">
<title>OCR Correction</title>
<link type="text/css" href="assets/css/bootstrap.css" rel="stylesheet" />
<link type="text/css" href="assets/css/bootstrap-responsive.css" rel="stylesheet" />
<link type="text/css" href="assets/css/styles.css" rel="stylesheet" media="screen" />
<link type="text/css" href="assets/css/tooltipster.css" rel="stylesheet" media="screen" />
<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
  <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script src="assets/js/jquery-1.11.0.min.js"></script>
<script src="assets/js/jquery.cookie.js"></script>
<script src="assets/js/underscore-min.js"></script>
<script src="assets/js/pouchdb-2.0.0.min.js"></script>
<script src="assets/js/oauth.js"></script>
<script src="assets/js/jquery.highlight.min.js"></script>
<script src="assets/js/application.js"></script>
<script src="assets/js/jquery.tooltipster.js"></script>
<script>
$(function() {
  OCRCorrection.initialize({
    db : "<?php echo DB_NAME; ?>",
    remote_db : "<?php echo $remote_db; ?>",
    page_id : <?php echo $page_id; ?>,
    show_replacements : false,
    show_word_replacements : true,
    oauth_provider : "github"
  });
  OAuth.initialize("<?php echo OAUTH_KEY; ?>");
});
</script>
</head>
<body>
  <div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
      <div class="container">
        <a class="btn btn-navbar" data-toggle="collapse" data-target="nav-collapse">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </a>
        <a class="brand" href=".">OCR Correction of BHL Documents (DEMO)</a>
        <div class="nav-collapse pull-right">
          <?php if(!isset($_COOKIE["ocr_correction"])): ?>
            <button id="ocr_signin" class="btn btn-primary">Sign In</button>
          <?php else: ?>
            <button id="ocr_signout" class="btn btn-danger">Sign Out</button>
          <?php endif; ?>
        </div>
     </div>
    </div>
  </div>

<div style="margin-top:40px;" class="container-fluid">
  <div class="row-fluid">
    <div class="span8">
      <div id="ocr_content">
        <?php echo $html; ?>
        <div id="ocr_image_container"></div>
        <img id="ocr_image" src="<?php echo $image_filename; ?>" />
      </div>
    </div>
    <div class="span4">
      <div id="ocr_edit_history"></div>
    </div>
  </div>
</div>

<script type="text/template" id="ocr_history_template">
<div class="ocr_edit_item media">
  <a href="#" class="pull-left" href="<%=userUrl%>"><img src = "<%=userAvatar%>" class="media-object" width="48" alt="<%=userName%>" /></a>
  <div class="media-body">
    <h4 class="media-heading"><%=userName%></h4>
    <%=text%>
  </div>
</div>
</script>

<script type="text/template" id="name_tooltip_template">
  <span>Name found in edited text: <%=names%></span>
</script>

<script type="text/template" id="word_replacement_template">
  <span title="Replace <%= key %> with <%=value%>" style="background-color:lavender"><%=word%></span>
</script>

</body>
</html>
