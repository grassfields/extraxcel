<?php

namespace App\Reader;

use App\Schema\Schema;
use App\Schema\Schemata;
use App\Celldata;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PhpSpreadsheetReader extends AbstractExcelReader {

    /**
    *  定数定義
    */
    const SCOPE_WORKBOOK = -1;

    /**
    *  変数定義
    */

    /**
    *  Excelファイルからスキーマを取得して返す
    */
    public function getSchemataFromExcel() {

        //Excelファイルか確認する
        if (    $this->file->getClientOriginalExtension() != self::EXT_EXCEL_BIFF
             && $this->file->getClientOriginalExtension() != self::EXT_EXCEL_OOXML ) {
            throw new \Exception('Not Excel format.');
        }

        //Excelファイルを開く
        $filepath = $this->file->getPathname();
        $objPHPExcel = IOFactory::load($filepath);

        //シート名を配列取得
        $arrShtNm = $objPHPExcel->getSheetNames();

        //名前付きセル範囲の配列を取得
        $arrNR = $objPHPExcel->getNamedRanges();

        //Excelファイルを閉じる
        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);


        //スキーマオブジェクトを生成して返す
        $objSchemata = app('App\Schema\Schemata');
        foreach($arrNR as $nr) {

            $objScp = $nr->getScope();
            if (is_null($objScp)) {
              $scope = self::SCOPE_WORKBOOK;
            } else {
              $scope = array_search($objScp->getTitle(), $arrShtNm);
            }

            $objSchemata->addSchema($nr, $scope);
        }

        return $objSchemata;
    }

    /**
    *  文書のデータを配列に取得する
    */
    public function readData_single(Schemata $schemata) {

        $arrRtn = array();

        //Excelファイルを開く
        $filepath = $this->file->getPathname();
        $objBook = IOFactory::load($filepath);

        //スキーマの名称でループし、
        //名前付きセルを順次取得する
        $schemanames = $schemata->getSchemaNames(Schemata::CELL_SINGLE);
        foreach($schemanames as $name) {

            $schema = $schemata->getSchema($name, Schemata::CELL_SINGLE);

            $objSheet = $objBook->getSheetByName($schema->xlsheet);
            if (!$objSheet) continue;   //シート取得不可ならスキップ

            if ($schemata->read_by == 'range') {
                $arrBdry = Coordinate::rangeBoundaries($schema->xlrange);
            } else {
                $objNR = $objBook->getNamedRange($name, $objSheet);
                $arrBdry = Coordinate::rangeBoundaries($objNR->getRange());
            }
            $arrInfo = array( "row_first" => intVal($arrBdry[0][1]),
                              "col_first" => $arrBdry[0][0],
                              "row_last"  => intVal($arrBdry[1][1]),
                              "col_last"  => $arrBdry[1][0]
                            );

            $arrValid = $schema->validate($arrInfo);
            if ($arrValid["valid"] == false) {
                //エラーメッセージの取得
                $msg = "[".$name."]".$arrValid["msg"];
                throw new \Exception($msg);
                continue;
            }

            $arr = array();
            switch($schema->type) {
                case Schema::TYPE_CELL:
                case Schema::TYPE_PID:
                case Schema::TYPE_ACT:
                    // 単一セル /////////////////////
                    $data = $this->getOneCell($objSheet, $arrInfo["row_first"], $arrInfo["col_first"] );
                    $arr = [ "type"  => Schema::TYPE_CELL,
                             "data"  => $data,
                             "rows"  => 1,
                             "cols"  => 1                 ];
                    break;
                case Schema::TYPE_NON://非対応
                default:
                    //何もしない
            }

            //正常
            $arrRtn[$name] = $arr;
        }

        $objBook->disconnectWorksheets();
        unset($objSheet);
        unset($objBook);

        return $arrRtn;

    }

    /**
    *  文書のデータを配列に取得する
    */
    public function readData_multi(Schemata $schemata) {

        $arrRtn = array();

        //Excelファイルを開く
        $filepath = $this->file->getPathname();
        $objBook = IOFactory::load($filepath);

        //スキーマの名称でループし、
        //名前付きセルを順次取得する
        $schemanames = $schemata->getSchemaNames(Schemata::CELL_MULTI);
        foreach($schemanames as $name) {

            $schema = $schemata->getSchema($name, Schemata::CELL_MULTI);

            $objSheet = $objBook->getSheetByName($schema->xlsheet);
            if (!$objSheet) continue;   //シート取得不可ならスキップ

            if ($schemata->read_by == 'range') {
                $arrBdry = Coordinate::rangeBoundaries($schema->xlrange);
            } else {
                $objNR = $objBook->getNamedRange($name, $objSheet);
                $arrBdry = Coordinate::rangeBoundaries($objNR->getRange());
            }
            $arrInfo = array( "row_first" => intVal($arrBdry[0][1]),
                              "col_first" => $arrBdry[0][0],
                              "row_last"  => intVal($arrBdry[1][1]),
                              "col_last"  => $arrBdry[1][0]
                            );

            $arrValid = $schema->validate($arrInfo);
            if ($arrValid["valid"] == false) {
                //エラーメッセージの取得
                $msg = "[".$name."]".$arrValid["msg"];
                throw new \Exception($msg);
                continue;
            }

            $arr = array();
            switch($schema->type) {
                case Schema::TYPE_CELL:
                case Schema::TYPE_PID:
                case Schema::TYPE_ACT:
                    break;
                case Schema::TYPE_ROW:
                    // １行 /////////////////////////
                    $data = $this->getOneRow($objSheet, $arrInfo["row_first"], $arrInfo["col_first"], $arrInfo["col_last"]  );
                    $arr = [ "type"  => Schema::TYPE_ROW,
                             "data"  => $data,
                             "rows"  => 1,
                             "cols"  => $arrValid["cols"] ];
                    break;

                case Schema::TYPE_COLUMN:
                    // １列 /////////////////////////
                    $data = $this->getOneCol($objSheet, $arrInfo["col_first"], $arrInfo["row_first"], $arrInfo["row_last"]  );
                    $arr = [ "type"  => Schema::TYPE_COLUMN,
                             "data"  => $data,
                             "rows"  => $arrValid["rows"],
                             "cols"  => 1                 ];
                    break;

                case Schema::TYPE_TABLE:
                    // 表形式 /////////////////////////
                    $data = array(); $idx=0;
                    for($rowIdx=$arrInfo["row_first"]; $rowIdx<=$arrInfo["row_last"]; $rowIdx++){
                        $data[$idx] = $this->getOneRow($objSheet, $rowIdx, $arrInfo["col_first"], $arrInfo["col_last"] );
                        $idx++;
                    }
                    $arr = [ "type"  => Schema::TYPE_TABLE,
                             "data"  => $data,
                             "rows"  => $arrValid["rows"],
                             "cols"  => $arrValid["cols"] ];
                    break;

                case Schema::TYPE_NON://非対応
                default:
                    //何もしない
            }

            //正常
            $arrRtn[$name] = $arr;
        }

        $objBook->disconnectWorksheets();
        unset($objSheet);
        unset($objBook);

        return $arrRtn;

    }

    /**
    *  セルの値と型を取得する
    */
    protected function getOneCell($objSheet, $row, $col){

        //Celldataインスタンスで返す
        $celldata = app(Celldata::class);

        $objCell = $objSheet->getCellByColumnAndRow($col, $row);
        $celldata->value   = $objCell->getCalculatedValue();
        $celldata->formatted_value = $objCell->getFormattedValue();
        $celldata->number_format = $objCell->getStyle()->getNumberFormat()->getFormatCode();
        switch ($objCell->getDataType()) {
            case DataType::TYPE_FORMULA:
            case DataType::TYPE_NUMERIC:
                //数値の場合は日付かどうかを確認する
                if (Date::isDateTime($objCell)) {
                    $celldata->type = Celldata::TYPE_DATE;
                } else {
                    $celldata->type = Celldata::TYPE_NUMERIC;
                }
                break;
            case DataType::TYPE_STRING:
            case DataType::TYPE_STRING2:
            case DataType::TYPE_INLINE:
                $celldata->type = Celldata::TYPE_STRING;
                break;
            case DataType::TYPE_BOOL:
                $celldata->type = Celldata::TYPE_BOOL;
                break;
            default:    //それ以外（NULLやN/A等）は空文字に
                logger('NG DataType=['.$objCell->getDataType().']'.$celldata->value);
                $celldata->value = "";
                $celldata->formatted_value = "";
                $celldata->number_format = null;
                $celldata->type = Celldata::TYPE_NULL;
        }

        return $celldata;

    }

}
