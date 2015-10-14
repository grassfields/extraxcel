<div id='schema-controller'>
    <h3>セル範囲</h3>
    <div id='schema-controller-button' class="btn-group" role="group">
        <a href='schema/export' class='btn btn-default btn-xs'>
            <span class='glyphicon glyphicon-floppy-disk'></span>&nbsp;保存
        </a>
        <button id='sort-mode-toggle' class='btn btn-default btn-xs'>
            <span class='glyphicon glyphicon-sort'></span>&nbsp;ソート
        </button>
        <button id='sort-mode-toggle' class='btn btn-default btn-xs'>
            <span class='glyphicon glyphicon-tags'></span>&nbsp;
        </button>
    </div>
    <div id='sort-mode-button' class="btn-group" role="group">
        <p class='description'>項目をマウスでドラッグして並び替えてください</p>
        <button id='sort-ok' class='btn btn-default btn-xs'>OK</button>
        <button id='sort-cancel' class='btn btn-default btn-xs'>Cancel</button>
    </div>
</div>

<div id='schemata'>
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

