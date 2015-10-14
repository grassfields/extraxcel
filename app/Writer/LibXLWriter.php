<?php

namespace App\Writer;

use ExcelBook;
use ExcelSheet;
use ExcelFormat;
use App\Dataset;
use App\Schema\Schema;

class LibXLWriter {

    /**
    *  定数定義
    */
    const SHEETNAME_CELL   = "単一セル";
    const SHEETNAME_ERR    = "ErrorList";
    
    /**
    *  変数定義
    */
    public  $filepath;
    private $_dataset;
    private $_book;     //ExcelBook
    private $_format;   //セルフォーマット
    
    private $_fileinfo_names = ["No", "ファイル名", "処理時刻"];
    
    /**
    *  コンストラクタ
    */
    public function __construct(Dataset $dataset) {
        $this->initialize();
        $this->_dataset  = $dataset;
    }
    
    /**
    *  項目初期化
    */
    public function initialize() {
        $this->filepath  = "";
        $this->_book     = null;
        $this->_posRow   = array();
        $this->_format   = array();
    }
    
    
    /**
    *  データシートExcelファイル生成
    */
    public function save() {
        
        $schemata = $this->_dataset->schemata;
        
        //ExcelBook生成
        $this->createBook();
        
        //単一セルのシート生成
        $arrNames = $schemata->getSchemaNames('single');
        $this->createSheetSingle($arrNames);
        
        //複数セルのシート生成
        $arrNames = $schemata->getSchemaNames('multi');
        foreach($arrNames as $idx => $name) {
            $this->createSheetMulti($idx, $name);
        }
        
        //Excelファイルをテンポラリに保存
        $this->filepath = tempnam(storage_path()."/cache", "datasheet_");
        $this->_book->save($this->filepath);
        
        //正常終了
        return $this->filepath;
    }
    
    
    /**
    *  ExcelBookの生成
    */
    public function createBook() {
        
        //新規ExcelBook生成（xlsx形式）
        $this->_book = new ExcelBook(null, null, true);
        $this->_book->setLocale('UTF-8');
        
        //デフォルトフォント設定
        $this->_book->setDefaultFont('Meiryo UI', 9);
        
        //フォントオブジェクトを生成
        $headerFont = $this->_book->addFont();
        $headerFont->color(ExcelFormat::COLOR_WHITE);
        $headerFont->bold(true);
        
        //通常データ
        $this->_format["val"] = $this->_book->addFormat();
        $this->_format["val"]->borderStyle(ExcelFormat::BORDERSTYLE_THIN);
        //日付
        $this->_format["date"] = $this->_book->addFormat();
        $this->_format["date"]->numberFormat(ExcelFormat::NUMFORMAT_DATE);
        $this->_format["date"]->borderStyle(ExcelFormat::BORDERSTYLE_THIN);
        //ヘッダ（共通）
        $this->_format["header_cmn"] = $this->_book->addFormat();
        $this->_format["header_cmn"]->fillPattern(ExcelFormat::FILLPATTERN_SOLID);
        $this->_format["header_cmn"]->patternForegroundColor(ExcelFormat::COLOR_SEAGREEN);
        $this->_format["header_cmn"]->borderStyle(ExcelFormat::BORDERSTYLE_THIN);
        $this->_format["header_cmn"]->setFont($headerFont);
        //ヘッダ（データ）
        $this->_format["header_data"] = $this->_book->addFormat();
        $this->_format["header_data"]->fillPattern(ExcelFormat::FILLPATTERN_SOLID);
        $this->_format["header_data"]->patternForegroundColor(ExcelFormat::COLOR_DARKGREEN);
        $this->_format["header_data"]->borderStyle(ExcelFormat::BORDERSTYLE_THIN);
        $this->_format["header_data"]->setFont($headerFont);
        
        return;
    }
    
    /**
    *  ワークシート[単一セル]の生成とデータ書込
    */
    public function createSheetSingle(array $arrNames) {
        
        //出力すべきものがなければ何もせず終了
        if (empty($arrNames)) return;
        
        $files = $this->_dataset->files;
        
        ///////////////////////////////////
        // WorkSheet生成
        $objSheet = $this->_book->addSheet(self::SHEETNAME_CELL);
        
        ///////////////////////////////////
        // ヘッダ項目出力
        $row = 0;
        $objSheet->writeRow($row, $this->_fileinfo_names, 0, $this->_format["header_cmn"]);
        $objSheet->writeRow($row, $arrNames, count($this->_fileinfo_names), $this->_format["header_data"]);
        
        foreach($this->_dataset->getDataset('single') as $fileidx => $arrData) {
            $row++;
            $this->writeFileInfo($objSheet, $row, $fileidx, $files[$fileidx]);
            $col = count($this->_fileinfo_names);
            foreach($arrNames as $name) {
                if (!isset($arrData[$name])) {
                    $this->writeCell($objSheet, $row, $col++, ['t'=>'t','v'=>''] );
                    continue;
                }
                $field = $arrData[$name];
                if ($field['type'] != Schema::TYPE_CELL) continue;
                $this->writeCell($objSheet, $row, $col++, $field['data']);
            }
        }
        
        //処理終了
        return;
        
    }
    
    
    /**
    *  ワークシート[複数セル]の生成とデータ書込
    */
    public function createSheetMulti($sheet_idx, $name) {
        
        $files = $this->_dataset->files;
        $maxCol = 0;
        
        ///////////////////////////////////
        // WorkSheet生成
        $objSheet = $this->_book->addSheet($name);
        
        ///////////////////////////////////
        // ヘッダ項目出力
        $row = 0;
        $objSheet->writeRow($row, $this->_fileinfo_names, 0, $this->_format["header_cmn"]);
        $objSheet->writeRow($row, [ $name ], count($this->_fileinfo_names), $this->_format["header_data"]);
        
        ///////////////////////////////////
        // ファイル数分ループ
        foreach($this->_dataset->getDataset('multi') as $fileidx => $arrData) {
            
            if (!isset($arrData[$name])) {
                //データ無しはファイル情報のみ1件出力して終了
                $row++;
                $this->writeFileInfo($objSheet, $row, $fileidx, $files[$fileidx]);
                continue;
            }
            
            switch($arrData[$name]['type']) {
                case Schema::TYPE_ROW:
                case Schema::TYPE_COLUMN:
                    $arrRows = [ $arrData[$name]['data'] ];
                    break;
                    
                case Schema::TYPE_TABLE:
                    $arrRows = $arrData[$name]['data'];
                    break;
                    
                default:
                    //データ無し
                    $arrRows = [ [ ] ]; //空の２次元配列を生成
            }
            
            foreach($arrRows as $arrCols) {
                
                $row++;
                $this->writeFileInfo($objSheet, $row, $fileidx, $files[$fileidx]);
                $col = count($this->_fileinfo_names) -1;
                foreach($arrCols as $value) {
                    $this->writeCell($objSheet, $row, ++$col, $value);
                }
                $maxCol = max($maxCol, $col);
            }
            
        }
        
        //ヘッダの項目名を最大項目数分で結合する
        if ($maxCol > count($this->_fileinfo_names)) {
            $objSheet->setMerge(0, 0, count($this->_fileinfo_names), $maxCol);
        }
        
        //処理終了
        return;
        
    }
    
    
    /**
    *  文書情報をExcelシートに書き込む
    */
    private function writeFileInfo(ExcelSheet $objSht, $row, $idx, $info) {
        
        //初期値
        $col = 0;
        
        //FileNo
        $objSht->write($row, $col, $idx+1, $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        $col++;
        
        //ファイル名
        $objSht->write($row, $col, $info['name'], $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        $col++;
        
        //処理時刻
        $dt = $this->_book->packDate($info['dt']->getTimestamp());
        $objSht->write($row, $col++, $dt, $this->_format["date"], ExcelFormat::AS_DATE);
        
        return;
    }
    
    /**
    *  データをExcelシートに書き込む
    */
    public function writeCell(ExcelSheet $objSht, $row, $col, $data) {
        
        switch($data["t"]) {
            case "n":   //数値
                $val  = floatVal($data["v"]);
                $fmt  = $this->_format["val"];
                $type = ExcelFormat::AS_NUMERIC_STRING;
                break;
            case "d":   //日付
                $val  = intVal($data["v"]);
                $fmt  = $this->_format["date"];
                $type = ExcelFormat::AS_DATE;
                break;
            case "b":   //真偽値
                $val  = boolVal($data["v"]);
                $fmt  = $this->_format["val"];
                $type = ExcelFormat::AS_NUMERIC_STRING;
                break;
            case "t":   //文字列
                $val  = strVal($data["v"]);
                $fmt  = $this->_format["val"];
                $type = ExcelFormat::AS_NUMERIC_STRING;
                break;
            default:
        }
        //書き込む
        $objSht->write($row, $col, $val, $fmt, $type);
        return;
    }
    
    
}
