<?php
$no = $fileidx + 1;
$str = "";
$str.= "<tbody id='file-".$fileidx."'>\n";
$str.= "<tr>\n";
$str.= "<td>".e($no)."</td>\n";
foreach($header as $name) {
    if (!isset($data[$name])) {
        $str.= "<td></td>\n";
        continue;
    }
    $val = $data[$name]['data']->formatted_value;
    $str.= "<td>".e($val)."</td>\n";
}
$str.= "</tr>\n";
$str.= "</tbody>\n";
echo $str;
?>
