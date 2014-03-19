<?php
require_once(dirname(__FILE__) . '/lib/djvu.view.class.php');


$PageID = 34570741;
$PageWidth = 800;

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
<!--
<link type="text/css" href="assets/css/bootstrap.css" rel="stylesheet" />
<link type="text/css" href="assets/css/bootstrap-responsive.css" rel="stylesheet" />
-->
<link type="text/css" href="assets/css/styles.css" rel="stylesheet" media="screen" />
<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
  <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script src="assets/js/jquery-1.11.0.min.js"></script>
<script src="assets/js/pouchdb-2.0.0.min.js"></script>
<script src="assets/js/application.js"></script>
<script>$(function() {
  OCRCorrection.init({ db : "http://127.0.0.1:5984/ocr", page_id : <?php echo $PageID; ?>, page_width : <?php echo $PageWidth ?> }); });
</script>
</head>
<body>

<?php echo $html; ?>

<div id="ocr_edit_history">
  <div></div>
</div>

<div id="ocr_image_container">
  <img src="<?php echo $image_filename; ?>" />
</div>

</body>
</html>
