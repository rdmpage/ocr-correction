<?php
require_once(dirname(__FILE__) . '/config/config.inc.php');
require_once(dirname(__FILE__) . '/lib/djvu.view.class.php');

$PageID = 16002437;
$PageWidth = 800;
$CouchDB = "http://" . DB_HOST . ":" . DB_PORT . "/" . DB_NAME;

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
<script src="assets/js/jquery.cookie.js"></script>
<script src="assets/js/underscore-min.js"></script>
<script src="assets/js/pouchdb-2.0.0.min.js"></script>
<script src="assets/js/oauth.js"></script>
<script src="assets/js/application.js"></script>
<script>
$(function() {
  OCRCorrection.initialize({
    pouch_db : "ocr",
    couch_db : "<?php echo $CouchDB; ?>",
    page_id : <?php echo $PageID; ?>,
    page_width : <?php echo $PageWidth ?>,
    show_replacements : false,
    show_word_replacements : true
  });
  OAuth.initialize('<?php echo OAUTH_KEY; ?>');
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
        <a class="brand" href=".">OCR</a>
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

<script type="text/template" id="ocr_history_item">
<div class="ocr_edit_item media">
  <a href="#" class="pull-left" href="<%=userUrl %>"><img src = "<%=userAvatar %>" class="media-object" width="48" alt="<%=userName %>" /></a>
  <div class="media-body">
    <h4 class="media-heading"><%=userName %></h4>
    <%=text %>
  </div>
</div>
</script>

</body>
</html>
