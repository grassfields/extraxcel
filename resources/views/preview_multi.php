<?php
$no = $fileidx + 1;
$name = $header[0];
$str = "";
$str.= "<tbody id='file-".$fileidx."'>\n";
if (!isset($data[$name])) {
    //データ無し
    $str.= "<tr>\n";
    $str.= "<td>".e($no)."</td>\n";
    $str.= "<td></td>\n";
    $str.= "</tr>\n";

} else if (    $data[$name]['rows']==1
            || $data[$name]['cols']==1 ) {
    //１レコード出力
    $arrRow = $data[$name]['data'];
    $str.= "<tr>\n";
    $str.= "<td>".e($no)."</td>\n";
    foreach($arrRow as $val) {
        $str.= "<td>".e($val->formatted_value)."</td>\n";
    }
    $str.= "</tr>\n";

} else  {
    //複数レコード出力
    $arrVal = $data[$name]['data'];
    foreach($arrVal as $arrRow) {
        $str.= "<tr>\n";
        $str.= "<td>".e($no)."</td>\n";
        foreach($arrRow as $val) {
            $str.= "<td>".e($val->formatted_value)."</td>\n";
        }
        $str.= "</tr>\n";
    }

}
$str.= "</tbody>\n";
echo $str;
?>
