<?php

namespace App\Reader;

use App\Schema;
use ExcelBook;
use ExcelSheet;
use DateTime;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ExcelReader {

    /**
    *  定数定義
    */
    const GET_BY_NAME    = 1;
    const GET_BY_RANGE   = 2;
    const EXT_EXCEL_BIFF      = "xls";
    const EXT_EXCEL_OOXML     = "xlsx";
    //const EXT_WORD_OOXML      = "docx";
    
    /**
    *  変数定義
    */
    protected $obj;
    protected $ext;
    protected $dt;
    
    /**
    *  コンストラクタ
    */
    public function __construct(UploadedFile $objFile) {
        $this->initialize();
        
        if ($objFile->isValid() == false) {
            throw new \Exception('File upload is failed.');
        }
        if (    $objFile->getClientOriginalExtension() != self::EXT_EXCEL_BIFF
             && $objFile->getClientOriginalExtension() != self::EXT_EXCEL_OOXML ) {
            $msg = 'Upload file is not Excel format. [EXT='.$objFile->getClientOriginalExtension().']';
            throw new \Exception($msg);
        }
        
        $this->file  = $objFile;
        $this->ext   = $objFile->getClientOriginalExtension();
        $this->dt    = new DateTime();
    }
    
    /**
    *  項目初期化
    */
    protected function initialize() {
        $this->file  = null;
        $this->ext   = "";
        $this->dt    = null;
    }
    
    /**
    *  項目初期化
    */
    public function getFileInfo() {
        
        $arr = [ "name"   => $this->file->getClientOriginalName(),
                 "size"   => $this->getSize(),
                 "dt"     => $this->dt->format("H:i") ];
        return $arr;
    }
    
    /**
     *  ファイルサイズを直感的な表記で返す
     *
     * @param integer
     * @return boolean
     */
    public function getSize() {
        
        $size = $this->file->getSize();
        
        if ($size < 800) {
            $str = $size."Byte";
        } else if ($size < (1024 * 1024)) {
            $str = round(($size / 1024), 1)."KB";
        } else {
            $str = round(($size / (1024*1024)), 1)."MB";
        }
        
        return $str;
    }
    
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
        $arrSchema = array();
        foreach($arrNR as $nr) {
            
            $name   = $nr->getName();
            $objScp = $nr->getScope();
            if (is_null($objScp)) {
              $scope = ExcelBook::SCOPE_WORKBOOK;
            } else {
              $scope = array_search($objScp->getTitle(), $arrShtNm);
            }
            
            $objSchema = app('App\Schema');
            $objSchema->setNamedRange($nr, $scope );
            
            $arrSchema[$name] = $objSchema;
        }
        
        return $arrSchema;
    }
    
    /**
    *  文書のデータを配列に取得する
    */
    public function readData($arrSchema, $by = false) {
        
        $arrRtn = array();
        
        //Excelファイルを開く
        $flg = ($this->ext != self::EXT_EXCEL_BIFF);  //xlsxモード or xlsモード
        $objBook = new ExcelBook(null, null, $flg);
        $objBook->setLocale('UTF-8');
        $objBook->loadFile($this->file->getPathname());
        
        //スキーマの名称でループし、
        //名前付きセルを順次取得する
        foreach($arrSchema as $name => $schema) {
            
            //対象外は処理スキップ
            if ($schema->require==Schema::REQUIRE_IGNORE) continue;
            
            $objSheet = $objBook->getSheetByName($schema->xlsheet);
            if ($by == self::GET_BY_RANGE) {
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
                    // 単一セル /////////////////////
                    $data = $this->getOneCell($objSheet, $cnt, $arrInfo["row_first"], $arrInfo["col_first"] );
                    $arr = [ "type"  => Schema::TYPE_CELL,
                             "data"  => $data,
                             "rows"  => 1,
                             "cols"  => 1                 ];
                    break;
                    
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
    private function getOneCell(ExcelSheet $objSheet, &$cnt, $row, $col){
        
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
    
    /**
    *  １行の指定セル列範囲の値と型を取得する
    */
    private function getOneRow(ExcelSheet $objSheet, &$cnt, $row, $col_first, $col_last){
        
        $arrData = array();
        for($idx=$col_first; $idx<=$col_last; $idx++){
            $arrData[] = $this->getOneCell($objSheet, $cnt, $row, $idx);
        }
        
        return $arrData;
    }
    
    /**
    *  １列の指定セル列範囲の値と型を取得する
    */
    private function getOneCol(ExcelSheet $objSheet, &$cnt, $col, $row_first, $row_last){
        
        $arrData = array();
        for($idx=$row_first; $idx<=$row_last; $idx++){
            $arrData[] = $this->getOneCell($objSheet, $cnt, $idx, $col);
        }
        
        return $arrData;
    }
    
}
