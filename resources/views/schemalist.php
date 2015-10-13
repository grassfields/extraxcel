<div id='schemata'>

<div id='schema-controller'>
    <div id='schema-controller-button' class="btn-group" role="group">
        <button id='schemaimport' class='btn btn-default btn-xs'>
            <span class='glyphicon glyphicon-open'></span>
            <input id='schemaupload' class='hidden' type='file' name='upschema' data-url='schema/import'>
        </button>
        <a href='schema/export' class='btn btn-default btn-xs'>
            <span class='glyphicon glyphicon-save'></span>
        </a>
    </div>
    <button id='sort-mode-toggle' class='btn btn-default btn-xs'>
        <span class='glyphicon glyphicon-sort'></span>
    </button>
    <div id='sort-mode-button' class="btn-group" role="group">
        <button id='sort-ok' class='btn btn-default btn-xs'>OK</button>
        <button id='sort-cancel' class='btn btn-default btn-xs'>Cancel</button>
    </div>
</div>

<?php
//////////////////////////////
// スキーマリスト（単一セル）
$no  = 0;
$str = "<h4>単一セル</h4>";
$str.= "<ul class='schemalist list-unstyled' id='schemalist_single'>";
$names_single = $schemata->getSchemaNames('single');
foreach($names_single as $name) {
    $no++;
    $schema = $schemata->getSchema($name, 'single');
    $str.= "<li data-no='".$no."' >\n";
    if ($schemata->read_by == 'name') {
        $str.= "<p class='name'>".e($name)."</p>\n";
        $str.= "<p class='xlrange'>".e($schema->xlsheet."&nbsp;!&nbsp;".$schema->xlrange)."</p>\n";
    } else {
        $str.= "<p class='xlrange'>".e($schema->xlsheet."&nbsp;!&nbsp;".$schema->xlrange)."</p>\n";
        $str.= "<p class='name'>".e($name)."</p>\n";
    }
    $str.= "</li>\n";
}
$str.= "</ul>\n";
echo $str;
//////////////////////////////
// スキーマリスト（複数セル）
$no  = 0;
$str = "<h4>複数セル</h4>";
$str.= "<ul class='schemalist list-unstyled' id='schemalist_multi'>";
$names_multi = $schemata->getSchemaNames('multi');
foreach($names_multi as $name) {
    $no++;
    $schema = $schemata->getSchema($name, 'multi');
    $str.= "<li data-no='".$no."' >\n";
    if ($schemata->read_by == 'name') {
        $str.= "<p class='name'>".e($name)."</p>\n";
        $str.= "<p class='xlrange'>".e($schema->xlsheet."&nbsp;!&nbsp;".$schema->xlrange)."</p>\n";
    } else {
        $str.= "<p class='xlrange'>".e($schema->xlsheet."&nbsp;!&nbsp;".$schema->xlrange)."</p>\n";
        $str.= "<p class='name'>".e($name)."</p>\n";
    }
    $str.= "</li>\n";
}
$str.= "</ul>\n";

//HTML出力
echo $str;
?>
</div>

