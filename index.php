<?php
require_once(dirname(__FILE__) . '/lib/djvu.view.class.php');


$PageID = 16002437;
$PageWidth = 800;
$CouchDB = "http://127.0.0.1:5984/ocr";

$xml_filename = 'examples/' . $PageID . '.xml';
$image_filename = 'examples/' . $PageID . '.png';

$djvu = new DjVuView($xml_filename);
$djvu->setImageWidth($PageWidth)
     ->setImageURL($image_filename)
     ->addFontmetrics()
     ->addLines();
$html = $djvu->createHTML();
?>

<html>
<head>
<meta charset="utf-8">
<meta name="ocr-capabilities" content="ocr_carea ocr_line ocr_page ocr_par">
<link type="text/css" href="assets/css/bootstrap.css" rel="stylesheet" />
<link type="text/css" href="assets/css/bootstrap-responsive.css" rel="stylesheet" />
<link type="text/css" href="assets/css/styles.css" rel="stylesheet" media="screen" />
<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
  <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script src="assets/js/jquery-1.11.0.min.js"></script>
<script src="assets/js/pouchdb-2.0.0.min.js"></script>
<script src="assets/js/application.js"></script>
<script>$(function() {
  OCRCorrection.init({
    pouch_db : "ocr",
    couch_db : "<?php echo $CouchDB; ?>",
    page_id : <?php echo $PageID; ?>,
    page_width : <?php echo $PageWidth ?>,
	show_replacements : false,
	show_word_replacements : true });
  });
</script>
</head>
<body>
  <div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
      <div class="container">
        <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </a>
        <a class="brand" href=".">OCR</a>
<!--
        <div class="nav-collapse">
          <ul class="nav">
            <li class="active"><a href=".">Home</a></li>
           <li><a href="?page=about">About</a></li>
          </ul>
        </div>
-->
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
      <div id="ocr_edit_history" class="media"></div>
    </div>
  </div>
</div>

</body>
</html>
