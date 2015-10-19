<?php

namespace App\Reader;

use App\Schema\Schema;
use App\Schema\Schemata;
use DateTime;
use PHPExcel_Worksheet;
use PHPExcel_Cell;
use PHPExcel_Cell_DataType;
use PHPExcel_IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PHPExcelReader extends AbstractExcelReader {

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
        $objPHPExcel = PHPExcel_IOFactory::load($filepath);
        
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
            
            $name   = $nr->getName();
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
        $objBook = PHPExcel_IOFactory::load($filepath);
        
        //スキーマの名称でループし、
        //名前付きセルを順次取得する
        $schemanames = $schemata->getSchemaNames(Schemata::CELL_SINGLE);
        foreach($schemanames as $name) {
            
            $schema = $schemata->getSchema($name, Schemata::CELL_SINGLE);
            
            //対象外は処理スキップ
            if ($schema->require==Schema::REQUIRE_IGNORE) continue;
            
            $objSheet = $objBook->getSheetByName($schema->xlsheet);
            if (!$objSheet) continue;   //シート取得不可ならスキップ
            
            if ($schemata->read_by == 'range') {
                $arrBdry = PHPExcel_Cell::rangeBoundaries($schema->xlrange);
                /*
                $arrAddr = $objSheet->addrToRowCol($schema->xlrange);
                $arrDim  = PHPExcel_Cell::rangeDimension($schema->xlrange);
                $arrInfo = array( "row_first" => $arrAddr["row"],
                                  "col_first" => $arrAddr["column"],
                                  "row_last"  => $arrAddr["row"]    + $arrDim[1] -1,
                                  "col_last"  => $arrAddr["column"] + $arrDim[0] -1 );
                */
            } else {
                $objNR = $objBook->getNamedRange($name, $objSheet);
                $arrBdry = PHPExcel_Cell::rangeBoundaries($objNR->getRange());
            }
            $arrInfo = array( "row_first" => intVal($arrBdry[0][1]) -1,
                              "col_first" => $arrBdry[0][0] -1,
                              "row_last"  => intVal($arrBdry[1][1]) -1,
                              "col_last"  => $arrBdry[1][0] -1     );
            
            $arrValid = $schema->validate($arrInfo);
            if ($arrValid["valid"] == false) {
                //エラーメッセージの取得
                $msg = "[".$name."]".$arrValid["msg"];
                throw new \Exception($msg);
                continue;
            }
            
            $arr = array(); $cnt = 0;
            switch($schema->type) {
                case Schema::TYPE_CELL:
                case Schema::TYPE_PID:
                case Schema::TYPE_ACT:
                    // 単一セル /////////////////////
                    $data = $this->getOneCell($objSheet, $cnt, $arrInfo["row_first"], $arrInfo["col_first"] );
                    $arr = [ "type"  => Schema::TYPE_CELL,
                             "data"  => $data,
                             "rows"  => 1,
                             "cols"  => 1                 ];
                    break;
                /*
                case Schema::TYPE_ROW:
                    // １行 /////////////////////////
                    $data = $this->getOneRow($objSheet, $cnt, $arrInfo["row_first"], $arrInfo["col_first"],
                                                                                     $arrInfo["col_last"]  );
                    $arr = [ "type"  => Schema::TYPE_ROW,
                             "data"  => $data,
                             "rows"  => 1,
                             "cols"  => $arrValid["cols"] ];
                    break;
                    
                case Schema::TYPE_COLUMN:
                    // １列 /////////////////////////
                    $data = $this->getOneCol($objSheet, $cnt, $arrInfo["col_first"], $arrInfo["row_first"],
                                                                                     $arrInfo["row_last"]  );
                    $arr = [ "type"  => Schema::TYPE_COLUMN,
                             "data"  => $data,
                             "rows"  => $arrValid["rows"],
                             "cols"  => 1                 ];
                    break;
                    
                case Schema::TYPE_TABLE:
                    // 表形式 /////////////////////////
                    $data = array(); $idx=0;
                    for($rowIdx=$arrInfo["row_first"]; $rowIdx<=$arrInfo["row_last"]; $rowIdx++){
                        $data[$idx] = $this->getOneRow($objSheet, $cnt, $rowIdx, $arrInfo["col_first"],
                                                                                 $arrInfo["col_last"]   );
                        $idx++;
                    }
                    $arr = [ "type"  => Schema::TYPE_TABLE,
                             "data"  => $data,
                             "rows"  => $arrValid["rows"],
                             "cols"  => $arrValid["cols"] ];
                    break;
                */
                
                case Schema::TYPE_NON://非対応
                default:
                    //何もしない
            }
            
            // 入力要求チェック[全て] ////////////////////////////
            if ($schema->require==Schema::REQUIRE_ALL) {
                if (($arrValid["rows"] * $arrValid["cols"]) !== $cnt) {
                    $msg = "［".$name."］に空欄があります";
                    throw new \Exception($msg);
                }
            }
            // 入力要求チェック[１つ以上] ////////////////////////////
            if ($schema->require==Schema::REQUIRE_NOTALL) {
                if ($cnt < 1) {
                    $msg = "［".$name."］が空欄です";
                    throw new \Exception($msg);
                }
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
        $objBook = PHPExcel_IOFactory::load($filepath);
        
        //スキーマの名称でループし、
        //名前付きセルを順次取得する
        $schemanames = $schemata->getSchemaNames(Schemata::CELL_MULTI);
        foreach($schemanames as $name) {
            
            $schema = $schemata->getSchema($name, Schemata::CELL_MULTI);
            
            //対象外は処理スキップ
            if ($schema->require==Schema::REQUIRE_IGNORE) continue;
            
            $objSheet = $objBook->getSheetByName($schema->xlsheet);
            if (!$objSheet) continue;   //シート取得不可ならスキップ
            
            if ($schemata->read_by == 'range') {
                $arrBdry = PHPExcel_Cell::rangeBoundaries($schema->xlrange);
                /*
                $arrAddr = $objSheet->addrToRowCol($schema->xlrange);
                $arrDim  = PHPExcel_Cell::rangeDimension($schema->xlrange);
                $arrInfo = array( "row_first" => $arrAddr["row"],
                                  "col_first" => $arrAddr["column"],
                                  "row_last"  => $arrAddr["row"]    + $arrDim[1] -1,
                                  "col_last"  => $arrAddr["column"] + $arrDim[0] -1 );
                */
            } else {
                $objNR = $objBook->getNamedRange($name, $objSheet);
                $arrBdry = PHPExcel_Cell::rangeBoundaries($objNR->getRange());
            }
//var_dump(PHPExcel_Cell::rangeBoundaries('B4:F10')); exit();
            $arrInfo = array( "row_first" => intVal($arrBdry[0][1]) -1,
                              "col_first" => $arrBdry[0][0] -1,
                              "row_last"  => intVal($arrBdry[1][1]) -1,
                              "col_last"  => $arrBdry[1][0] -1     );
            
            $arrValid = $schema->validate($arrInfo);
            if ($arrValid["valid"] == false) {
                //エラーメッセージの取得
                $msg = "[".$name."]".$arrValid["msg"];
                throw new \Exception($msg);
                continue;
            }
            
            $arr = array(); $cnt = 0;
            switch($schema->type) {
                /*
                case Schema::TYPE_CELL:
                case Schema::TYPE_PID:
                case Schema::TYPE_ACT:
                    // 単一セル /////////////////////
                    $data = $this->getOneCell($objSheet, $cnt, $arrInfo["row_first"], $arrInfo["col_first"] );
                    $arr = [ "type"  => Schema::TYPE_CELL,
                             "data"  => $data,
                             "rows"  => 1,
                             "cols"  => 1                 ];
                    break;
                    
                */
                case Schema::TYPE_ROW:
                    // １行 /////////////////////////
                    $data = $this->getOneRow($objSheet, $cnt, $arrInfo["row_first"], $arrInfo["col_first"],
                                                                                     $arrInfo["col_last"]  );
                    $arr = [ "type"  => Schema::TYPE_ROW,
                             "data"  => $data,
                             "rows"  => 1,
                             "cols"  => $arrValid["cols"] ];
                    break;
                    
                case Schema::TYPE_COLUMN:
                    // １列 /////////////////////////
                    $data = $this->getOneCol($objSheet, $cnt, $arrInfo["col_first"], $arrInfo["row_first"],
                                                                                     $arrInfo["row_last"]  );
                    $arr = [ "type"  => Schema::TYPE_COLUMN,
                             "data"  => $data,
                             "rows"  => $arrValid["rows"],
                             "cols"  => 1                 ];
                    break;
                    
                case Schema::TYPE_TABLE:
                    // 表形式 /////////////////////////
                    $data = array(); $idx=0;
                    for($rowIdx=$arrInfo["row_first"]; $rowIdx<=$arrInfo["row_last"]; $rowIdx++){
                        $data[$idx] = $this->getOneRow($objSheet, $cnt, $rowIdx, $arrInfo["col_first"],
                                                                                 $arrInfo["col_last"]   );
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
            
            // 入力要求チェック[全て] ////////////////////////////
            if ($schema->require==Schema::REQUIRE_ALL) {
                if (($arrValid["rows"] * $arrValid["cols"]) !== $cnt) {
                    $msg = "［".$name."］に空欄があります";
                    throw new \Exception($msg);
                }
            }
            // 入力要求チェック[１つ以上] ////////////////////////////
            if ($schema->require==Schema::REQUIRE_NOTALL) {
                if ($cnt < 1) {
                    $msg = "［".$name."］が空欄です";
                    throw new \Exception($msg);
                }
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
    protected function getOneCell($objSheet, &$cnt, $row, $col){
        $objCell = $objSheet->getCellByColumnAndRow($col, $row+1);
        $value   = $objCell->getFormattedValue();
        switch ($objCell->getDataType()) {
            case PHPExcel_Cell_DataType::TYPE_NUMERIC:
                //数値の場合は日付かどうかを確認する
                try {
                    $dt = new DateTime($value);
                    $type = "d";
                } catch(\Exception $e) {
                    $type = "n";
                }
                break;
            case PHPExcel_Cell_DataType::TYPE_STRING:
            case PHPExcel_Cell_DataType::TYPE_STRING2:
                $type = "t";
                break;
            case PHPExcel_Cell_DataType::TYPE_BOOL:
                $type = "b";
                break;
            default:    //それ以外（NULLやN/A等）は空文字に
                $type = "t";
                $value = "";
        }
        
        if (!empty($value)) $cnt++; //値アリ
        return array("t" => $type, "v" => $value);
        
    }
    
}
