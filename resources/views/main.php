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
        <li><a href='clear'>クリア</a></li>
      </ul>
    </div>
  </div>
</div>

<div class="container-fluid">
    <?php echo csrf_field()."\n"; ?>
    <div class="row">
        <div id='file-controller'>
            <a id='filedroparea' class='btn btn-default btn-lg btn-block'>
                <input id='fileupload' class='hidden' type='file' name='upfile' data-url='file' multiple>
                Drop here !!
            </a>
            <hr>
        </div>
<?php
//////////////////////////////
// ファイルリスト
$no  = 0;
$str = "";
foreach($objDataset->files as $fileidx => $file) {
    $no = $fileidx + 1;
    $str.= "<li data-fileidx='".e($fileidx)."' >\n";
    $str.= "<span>".e($no)."</span>\n";
    $str.= "<date>".e($file['time'])."</date>\n";
    $str.= "<button type='button' class='btn btn-xs close'>&times;</button>\n";
    $str.= "<p>".e($file['name'])."</p>\n";
    $str.= "</li>\n";
}
//HTML出力
echo "<ul class='filelist list-unstyled' data-cnt='".$no."'>\n";
echo $str;
echo "</ul>\n";


//////////////////////////////
// スキーマリスト
$viewSchemata = view('schemalist')->with('schemata', $objDataset->schemata);
echo $viewSchemata->render();


//////////////////////////////
//プレビュー画面ヘッダ
$names_single = $objDataset->schemata->getSchemaNames('single');
$names_multi  = $objDataset->schemata->getSchemaNames('multi');
$str = "";
$str.= "<div class='main'>\n";
$str.= "<div class='page-header'>\n";
$str.= "  <h1>Preview</h1>\n";
$str.= "</div>\n";
$str.= "<div class='row'>\n";
$str.= "  <div class='col-md-4'>\n";
$str.= "    <select class='form-control' id='sheetidx'>\n";
$sel = ($sheettype == 's') ? ' selected' : '';
$str.= "    <option value='single' data-idx='0'".$sel.">セルデータ一覧</option>\n";
foreach($names_multi as $idx => $name) {
    $sel = ($sheettype == 'm' && $sheetidx == $idx) ? ' selected' : '';
    $str.= "    <option value='multi' data-idx='".e($idx)."'".$sel.">".e($name)."</option>\n";
}
$str.= "    </select>\n";
$str.= "  </div>\n";
$str.= "  <div class='col-md-3 col-md-offset-5'>\n";
$str.= "    <a href='download' class='btn btn-primary btn-lg btn-block'>";
$str.= "        <span class='glyphicon glyphicon-download-alt'></span>";
$str.= "        ダウンロード";
$str.= "    </a>\n";
$str.= "  </div>\n";
$str.= "</div>\n";

if (empty($objDataset->files)) {
    //データ無し
    $str.= view("welcome")->render();
    var_dump($objDataset->schemata);
    
} else {
    //////////////////////////////
    //プレビューテーブルヘッダ
    if ($sheettype == 's') {
        $header     = $names_single;
        $view_name  = 'preview_single';
        $dataset    = $objDataset->getDataset('single');
    } else {
        $header     = [ $names_multi[$sheetidx] ];
        $view_name  = 'preview_multi';
        $dataset    = $objDataset->getDataset('multi');
    }
    $str.= "<div class='table-responsive'>\n";
    $str.= "<table class='table preview'>\n";
    $str.= "<thead>\n";
    $str.= "<tr>\n";
    $str.= "<th>No</th>\n";
    foreach($header as $name) {
        $str.= "<th>".e($name)."</th>\n";
    }
    $str.= "</tr>\n";
    $str.= "</thead>\n";

    //////////////////////////////
    //プレビューテーブル描画
    foreach($dataset as $idx => $data) {
        $view = view($view_name)->with('fileidx', $idx)
                                ->with('header',  $header)
                                ->with('data',    $data);
        $str.= $view->render();
    }

    $str.= "</table>\n";
    $str.= "</div>\n";  //class='table-responsive'
}

//HTML出力
echo $str;

//var_dump($objDataset->schemata->getSchemaNames('multi'));
//var_dump($objDataset->getDataset('single'));
?>
        </div>
    </div><!--class="row"-->
</div><!--class="container-fluid"-->

<footer>
</footer>

<script type='text/javascript' src='js/common.js'></script>
</body>
</html>

