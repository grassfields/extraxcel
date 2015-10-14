<?php

namespace App\Reader;

use App\Schema\Schema;
use App\Schema\Schemata;
use ExcelBook;
use ExcelSheet;
use DateTime;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LibXLReader extends ExcelReader {

    /**
    *  定数定義
    */
    
    /**
    *  変数定義
    */
    
    /**
    *  Excelファイルからスキーマを取得して返す
    */
    public function getSchemataFromExcel() {
        
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
              $scope = ExcelBook::SCOPE_WORKBOOK;
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
        $flg = ($this->ext != self::EXT_EXCEL_BIFF);  //xlsxモード or xlsモード
        $objBook = new ExcelBook(null, null, $flg);
        $objBook->setLocale('UTF-8');
        $objBook->loadFile($this->file->getPathname());
        
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
                $arrAddr = $objSheet->addrToRowCol($schema->xlrange);
                $arrDim  = PHPExcel_Cell::rangeDimension($schema->xlrange);
                $arrInfo = array( "row_first" => $arrAddr["row"],
                                  "col_first" => $arrAddr["column"],
                                  "row_last"  => $arrAddr["row"]    + $arrDim[1] -1,
                                  "col_last"  => $arrAddr["column"] + $arrDim[0] -1 );
            } else {
                $arrInfo = $objSheet->getNamedRange($name, $schema->xlscope);
            }
            
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
        $flg = ($this->ext != self::EXT_EXCEL_BIFF);  //xlsxモード or xlsモード
        $objBook = new ExcelBook(null, null, $flg);
        $objBook->setLocale('UTF-8');
        $objBook->loadFile($this->file->getPathname());
        
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
                $arrAddr = $objSheet->addrToRowCol($schema->xlrange);
                $arrDim  = PHPExcel_Cell::rangeDimension($schema->xlrange);
                $arrInfo = array( "row_first" => $arrAddr["row"],
                                  "col_first" => $arrAddr["column"],
                                  "row_last"  => $arrAddr["row"]    + $arrDim[1] -1,
                                  "col_last"  => $arrAddr["column"] + $arrDim[0] -1 );
            } else {
                $arrInfo = $objSheet->getNamedRange($name, $schema->xlscope);
            }
            
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
        
        unset($objSheet);
        unset($objBook);
        
        return $arrRtn;
        
    }
    
    /**
    *  セルの値と型を取得する
    */
    protected function getOneCell($objSheet, &$cnt, $row, $col){
        
        $value    = $objSheet->read($row, $col, $objFmt, false);
        $celltype = $objSheet->cellType($row, $col);
        switch ($celltype) {
            case ExcelSheet::CELLTYPE_NUMBER:
                //数値の場合は日付かどうかを確認する
                $type = ($objSheet->isDate($row, $col)) ? "d" : "n";
                break;
            case ExcelSheet::CELLTYPE_STRING:
                $type = "t";
                break;
            case ExcelSheet::CELLTYPE_BOOLEAN:
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