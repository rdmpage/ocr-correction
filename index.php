<?php
require_once (dirname(__FILE__) . '/lib/djvu.class.php');

//Set some canned data just to get started
$PageID = 16002437;
$xml_filename = 'examples/' . $PageID . '.xml';
$image_filename = 'examples/' . $PageID . '.png';

$djvu = new DjVu($xml_filename);
$djvu->setImageWidth(800);
$djvu->setImageURL($image_filename);
$html_snippet = $djvu->createHTML();
?>

<html>
<head>
<meta charset="utf-8">
<meta name="ocr-capabilities" content="ocr_carea ocr_line ocr_page ocr_par">
<link type="text/css" href="public/stylesheets/styles.css" rel="stylesheet" media="screen" />
<script src="public/javascript/jquery-1.11.0.min.js"></script>
<script src="public/javascript/pouchdb-1.1.0.min.js"></script>
<script src="public/javascript/ocr_correction.js"></script>
<script>$(function() { OCR.init({ db : "http://127.0.0.1:5984/ocr", page_id : <?php echo $PageID; ?> }); });</script>
</head>
<body>

<div id="ocr_edit_history">
	<div></div>
</div>

<div id="ocr_image_container">
  <img src="<?php echo $image_filename; ?>" />
</div>

<?php echo $html_snippet; ?>

</body>
</html>