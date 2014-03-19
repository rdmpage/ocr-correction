<?php
require_once(dirname(__FILE__) . '/lib/DjVu.view.class.php');

//Set some canned data just to get started
$PageID = 16002437;
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
<link type="text/css" href="assets/css/styles.css" rel="stylesheet" media="screen" />
<script src="assets/js/jquery-1.11.0.min.js"></script>
<script src="assets/js/pouchdb-1.1.0.min.js"></script>
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