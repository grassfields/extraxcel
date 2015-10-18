<div id='schema-controller'>
    <h3>データ取得</h3>
<?php
/////////////////////
// データ取得方法
$change_link = "&nbsp;&nbsp;&nbsp;<a id='readby-change' class='clickable'>[変更]</a>";
$str = "<div id='schema-readby'>";
if ($schemata->read_by == 'name') {
    $str.= "<span class='glyphicon glyphicon-tags'></span>";
    $str.= "<p class='description'>セルの名前で検索して取得する".$change_link."</p>";
} else {
    $str.= "<span class='glyphicon glyphicon-th-list'></span>&nbsp;";
    $str.= "<p class='description'>シート名＆セル範囲で取得する".$change_link."</p>";
}
$str.= "</div>\n";
echo $str;
?>
    <div id='schema-controller-button' class="btn-group" role="group">
        <a href='schema/export' class='btn btn-default btn-xs'>
            <span class='glyphicon glyphicon-floppy-disk'></span>&nbsp;保存
        </a>
        <button id='sort-mode-toggle' class='btn btn-default btn-xs'>
            <span class='glyphicon glyphicon-sort'></span>&nbsp;並び替え
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

