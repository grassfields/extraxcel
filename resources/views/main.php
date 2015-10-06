<!doctype html>
<html lang="ja">
<head>
<meta charset='UTF-8' />
<meta http-equiv='X-UA-Compatible' content='IE=edge' />
<meta http-equiv='Content-Style-Type' content='text/css' />
<meta http-equiv='Content-Script-Type' content='text/javascript' />
<link href='packages/components/bootstrap/css/bootstrap.min.css' rel='stylesheet' type='text/css'>
<link href='css/base.css' rel='stylesheet' type='text/css'>
<?php
/*
if (is_array($addStyleFiles)) {
    foreach($addStyleFiles as $file) {
        echo "<link href='".url($file)."' rel='stylesheet' type='text/css'>\n";
    }
}
*/
?>
<title>Extraxcel</title>
<script type='text/javascript' src='packages/components/jquery/jquery.min.js'></script>
<script type='text/javascript' src='packages/components/jqueryui/jquery-ui.min.js'></script>
<script type='text/javascript' src='packages/components/bootstrap/js/bootstrap.min.js'></script>
<script type='text/javascript' src='packages/blueimp/jquery-file-upload/js/jquery.fileupload.js'></script>
<?php
/*
if (is_array($addScriptFiles)) {
    foreach($addScriptFiles as $file) {
        echo "<script type='text/javascript' src='".url($file)."'></script>\n";
    }
}
*/
?>
<style>
  article, aside, dialog, figure, footer, header,
  hgroup, menu, nav, section { display: block; }
</style>
</head>

<body>
<div class="navbar navbar-default navbar-fixed-top" role="navigation">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href=".">Extraxcel</a>
    </div>
    <div class="navbar-collapse collapse">
      <ul class="nav navbar-nav navbar-right">
      </ul>
    </div>
  </div>
</div>

<div class="container-fluid">
    <?php echo csrf_field(); ?>
    <div class="row">
        <div id='file-controller'>
            <a id='filedroparea' class='btn btn-default btn-lg btn-block'>
                <input id='fileupload' class='hidden' type='file' name='upfile' data-url='file' multiple>
                Drop here !!
            </a>
            <div id='uploadprogress' class='progress' style='display:none;'>
            <div class='progress-bar' role='progressbar' aria-valuenow='0' aria-valuemin='0' aria-valuemax='100' style='width: 0%;'></div>
            </div>
            <hr>
<?php
$str = ""; $no = 0;
foreach($data->files as $file) {
    $no++;
    $str.= "<li data-fileno='".$no."' >\n";
    $str.= "<span>".$no."</span>\n";
    $str.= "<p>".$file['name']."</p>\n";
    $str.= "<date>".$file['dt']."</date>\n";
    $str.= "</li>\n";
}
//HTML出力
echo "<ul class='filelist list-unstyled' data-cnt='".$no."'>\n";
echo $str;
echo "</ul>\n";
?>
        </div>
        <div id='schemata'>
        </div>
        
        
<?php
//メインコンテンツ開始
$str = "";
$str.= "<div class='main'>\n";
$str.= "<div class='page-header'>\n";
$str.= "  <h1>Hello world !!</h1>\n";
$str.= "</div>\n";
$str.= "<div class='row'>\n";
$str.= "<div class='col-md-6'>\n";
$str.= "</div>\n";
$str.= "</div>\n";

//HTML出力
echo $str;
?>
        </div>
    </div><!--class="row"-->
</div><!--class="container-fluid"-->

<footer>
</footer>

<script type='text/javascript' src='js/common.js'></script>
</body>
</html>
