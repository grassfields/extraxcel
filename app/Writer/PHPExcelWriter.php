<?php

namespace App\Writer;

use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Worksheet;
use PHPExcel_Style;
use PHPExcel_Cell;
use PHPExcel_Cell_DataType;
use App\Dataset;
use App\Schema\Schema;

class PHPExcelWriter {

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
        
        //後処理
        $this->_book->removeSheetByIndex(0);    //デフォルト作成されるシートは不要
        $this->_book->setActiveSheetIndex(0);   //最初のシートをActiveに
        
        //Excelファイルをテンポラリに保存
        $this->filepath = tempnam(storage_path()."/cache", "datasheet_");
        $objWriter = PHPExcel_IOFactory::createWriter($this->_book, "Excel2007");
        $objWriter->save($this->filepath);
        
        //正常終了
        return $this->filepath;
    }
    
    
    /**
    *  ExcelBookの生成
    */
    public function createBook() {
        
        //新規ExcelBook生成（xlsx形式）
        $this->_book = new PHPExcel();
        
        //デフォルトフォント設定
        $this->_book->getDefaultStyle()->getFont()->setName('Meiryo UI')->setSize(9);
        
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
        $objSheet = $this->_book->createSheet();
        $objSheet->setTitle(self::SHEETNAME_CELL);
        
        
        ///////////////////////////////////
        // ヘッダ項目出力
        $row = 1; $col = 0;
        foreach($this->_fileinfo_names as $header_name) {
            $this->writeCell($objSheet, $row, $col++, $header_name, 'header_cmn');
        }
        foreach($arrNames as $header_name) {
            $this->writeCell($objSheet, $row, $col++, $header_name, 'header_data');
        }
        
        foreach($this->_dataset->getDataset('single') as $fileidx => $arrData) {
            $row++;
            $this->writeFileInfo($objSheet, $row, $fileidx, $files[$fileidx]);
            $col = count($this->_fileinfo_names);
            foreach($arrNames as $name) {
                if (!isset($arrData[$name])) {
                    $this->writeCell($objSheet, $row, $col++, '', 't'] );
                    continue;
                }
                $field = $arrData[$name];
                if ($field['type'] != Schema::TYPE_CELL) continue;
                $this->writeCell($objSheet, $row, $col++, $field['data']['v'], $field['data']['t']);
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
        $objSheet = $this->_book->createSheet();
        $objSheet->setTitle($name);
        
        ///////////////////////////////////
        // ヘッダ項目出力
        $row = 1; $col = 0;
        foreach($this->_fileinfo_names as $header_name) {
            $this->writeCell($objSheet, $row, $col++, $header_name, 'header_cmn');
        }
        $this->writeCell($objSheet, $row, $col++, $name, 'header_data');
        
        
        
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
                    $this->writeCell($objSheet, $row, ++$col, $value['v'], $value['t']);
                }
                $maxCol = max($maxCol, $col);
            }
            
        }
        
        //ヘッダの項目名を最大項目数分で結合する
        if ($maxCol > count($this->_fileinfo_names)) {
            $objSheet->mergeCellsByColumnAndRow(count($this->_fileinfo_names),1, $maxCol,1);
        }
        
        //処理終了
        return;
        
    }
    
    
    /**
    *  文書情報をExcelシートに書き込む
    */
    private function writeFileInfo(PHPExcel_Worksheet $objSht, $row, $idx, $info) {
        
        //初期値
        $col = 0;
        
        //FileNo
        $this->writeCell($objSht, $row, $col++, $idx+1, 'n');
        
        //ファイル名
        $this->writeCell($objSht, $row, $col++, $info['name'], 't');
        
        //処理時刻
        $this->writeCell($objSht, $row, $col++, $info['dt']->getTimestamp(), 'd');
        
        return;
    }
    
    /**
    *  データをExcelシートに書き込む
    */
    public function writeCell(PHPExcel_Worksheet $objSht, $row, $col, $val, $type) {
        
        $cellformat = null;
        $cellfill   = null;
        $cellcolor  = null;
        
        switch($type) {
            case "n":   //数値
                $val  = floatVal($val);
                $celltype = PHPExcel_Cell_DataType::TYPE_NUMERIC;
                break;
            case "d":   //日付
                $val  = intVal($val);
                $val  = ($val / (60*60*24)) + 25569.0;
                $celltype = PHPExcel_Cell_DataType::TYPE_NUMERIC;
                $cellformat = 'yyyy-mm-dd hh:mm';
                break;
            case "b":   //真偽値
                $val  = boolVal($val);
                $celltype = PHPExcel_Cell_DataType::TYPE_BOOL;
                break;
            case "t":   //文字列
                $val  = strVal($val);
                $celltype = PHPExcel_Cell_DataType::TYPE_STRING;
                break;
            case "header_cmn":   //ヘッダ共通部
                $val  = strVal($val);
                $celltype = PHPExcel_Cell_DataType::TYPE_STRING;
                $cellfill = '339966';
                $cellcolor= \PHPExcel_Style_Color::COLOR_WHITE;
                break;
            case "header_data":   //ヘッダデータ部
                $val  = strVal($val);
                $celltype = PHPExcel_Cell_DataType::TYPE_STRING;
                $cellfill = '003300';
                $cellcolor= \PHPExcel_Style_Color::COLOR_WHITE;
                break;
            default:
        }
        
        //書き込む
        $objStyle = $objSht->setCellValueExplicitByColumnAndRow($col, $row, $val, $celltype, true)->getStyle();
        $objStyle->getBorders()->getAllBorders()->setBorderStyle( \PHPExcel_Style_Border::BORDER_THIN );
        if ($cellformat) {
            $objStyle->getNumberFormat()->setFormatCode($cellformat);
        }
        if ($cellfill) {
            $objStyle->getFill()->setFillType( \PHPExcel_Style_Fill::FILL_SOLID )->getStartColor()->setARGB($cellfill);
        }
        if ($cellcolor) {
            $objStyle->getFont()->getColor()->setARGB($cellcolor);
            $objStyle->getFont()->setBold(true);
        }
        return;
    }
    
    
}
