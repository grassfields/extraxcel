<?php

namespace App\Reader;

use App\Schema\Schema;
use App\Schema\Schemata;
use DateTime;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class AbstractExcelReader {

    /**
    *  定数定義
    */
    const EXT_EXCEL_BIFF      = "xls";
    const EXT_EXCEL_OOXML     = "xlsx";
    //const EXT_WORD_OOXML      = "docx";
    
    /**
    *  変数定義
    */
    protected $obj;
    protected $ext;
    protected $dt;
    protected $err;
    
    /**
    *  コンストラクタ
    */
    public function __construct(UploadedFile $objFile) {
        $this->initialize();
        
        if ($objFile->isValid() == false) {
            throw new \Exception('File upload is failed.');
        }
        $this->file  = $objFile;
        $this->ext   = $objFile->getClientOriginalExtension();
        $this->dt    = new DateTime();
        $this->err   = "";
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
        
        $arr = [ "name"    => $this->file->getClientOriginalName(),
                 "size"    => $this->file->getSize(),
                 "size_si" => $this->getSize(),
                 "dt"      => $this->dt,
                 "time"    => $this->dt->format("H:i") ];
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
    *  実行時エラーのメッセージを返す
    */
    public function getErrorMessage() {
        return $this->err;
    }
    
    /**
    *  Excelファイルからスキーマを取得して返す
    */
    abstract public function getSchemataFromExcel();
    
    /**
    *  文書のデータを配列に取得する
    */
    abstract public function readData_single(Schemata $schemata);
    
    /**
    *  文書のデータを配列に取得する
    */
    abstract public function readData_multi(Schemata $schemata);
    
    /**
    *  セルの値と型を取得する
    */
    abstract protected function getOneCell($objSheet, &$cnt, $row, $col);
    
    /**
    *  １行の指定セル列範囲の値と型を取得する
    */
    protected function getOneRow($objSheet, &$cnt, $row, $col_first, $col_last){
        
        $arrData = array();
        for($idx=$col_first; $idx<=$col_last; $idx++){
            $arrData[] = $this->getOneCell($objSheet, $cnt, $row, $idx);
        }
        
        return $arrData;
    }
    
    /**
    *  １列の指定セル列範囲の値と型を取得する
    */
    protected function getOneCol($objSheet, &$cnt, $col, $row_first, $row_last){
        
        $arrData = array();
        for($idx=$row_first; $idx<=$row_last; $idx++){
            $arrData[] = $this->getOneCell($objSheet, $cnt, $idx, $col);
        }
        
        return $arrData;
    }
    
}
