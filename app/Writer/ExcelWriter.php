<?php

namespace App\Writer;


class ExcelWriter {

    /**
    *  定数定義
    */
    const SHEETNAME_CELL   = "単一セル";
    const SHEETNAME_ERR    = "ErrorList";
    const DOCINFO_COLNUM   = 11;    //文書情報の項目数
    
    /**
    *  変数定義
    */
    public  $filepath;
    public  $with_errlist;
    
    private $_schemata;
    private $_dataset;
    
    private $_errlist;
    private $_book;     //ExcelBook
    private $_posRow;   //シート別行番号
    private $_idxCol;   //列番号
    private $_format;   //セルフォーマット
    
    /**
    *  コンストラクタ
    */
    public function __construct(Schemata $schemata, $dataset) {
        $this->initialize();
        $this->_schemata = $schemata;
        $this->_dataset  = $dataset;
    }
    
    /**
    *  項目初期化
    */
    public function initialize() {
        $this->filepath  = "";
        $this->with_errlist = false;
        $this->_errlist  = array();
        $this->_book     = null;
        $this->_posRow   = array();
        $this->_idxCol   = array();
        $this->_format   = array();
    }
    
    
    /**
    *  データシートExcelファイル生成
    */
    public function createDataSheet() {
        
        //ExcelBook生成
        $this->createBook();
        
        //データの書き込み
        $this->writeData();
        
        //エラーリストの追加
        if ($this->with_errlist) {
            $this->addErrList();
        }
        
        //各シートのヘッダ表示
        $this->writeHeader();
        
        //Excelファイルをテンポラリに保存
        $this->filepath = tempnam(storage_path()."/cache", "datasheet_");
        $this->_book->save($this->filepath);
        
        //正常終了
        return;
    }
    
    
    /**
    *  データの書き込み
    */
    public function writeData() {
        
        $arrSts = [ $this->docstatus ];
        
        $phnow = "(SELECT phid, pid, emptype, gid, rid, main ";
        $phnow.= "   FROM expo_person_his ";
        $phnow.= "  WHERE (main=1) ";
        $phnow.= "    AND (sday<='".date('Y-m-d')."') ";
        $phnow.= "    AND (eday>='".date('Y-m-d')."' OR eday IS NULL)) AS ph";
        $recsetDoc = DocumentTable::join(    'expo_form AS f',   'expo_document.uid','=','f.uid')
                                  ->leftjoin('expo_person AS po','expo_document.pid','=','po.pid')
                                  ->leftjoin(DB::raw($phnow),    'expo_document.pid','=','ph.pid')
                                  ->join(    'expo_group AS g',  'ph.gid','=','g.gid')
                                  ->join(    'expo_role AS r',   'ph.rid','=','r.rid')
                                  ->leftjoin('expo_person AS pi','expo_document.input_pid','=','pi.pid')
                                  ->select([ 'expo_document.*',
                                             'f.name_ja',
                                             'f.name_en',
                                             'po.name_ja AS owner_name_ja',
                                             'po.name_en AS owner_name_en',
                                             'po.account',
                                             'g.longname_ja AS group_name_ja',
                                             'r.name_ja AS role_name_ja',
                                             'pi.name_ja AS input_name_ja',
                                             'pi.name_ja AS input_name_en',
                                            ])
                                  ->whereIn('f.uid', $this->uid)
                                  ->whereIn('expo_document.status', $arrSts)
                                  ->get();
        
        //文書データでループ
        foreach($recsetDoc as $recDoc) {
            
            $writen = false;
            $uid = $recDoc->uid;
            
            //データ取得
            $arrDocData = json_decode($recDoc->data_json, true);
            
            //フォーム定義のスキーマでループ
            foreach($this->_schemata[$uid] as $name => $schema) {
                
                if ($schema->type == ExpoSchema::TYPE_NON) continue;    //非対応=スキップ
                if ($schema->require == ExpoSchema::REQUIRE_IGNORE) continue;   //処理対象外=スキップ
                
                if (!isset($arrDocData[$name])) {
                    //データなし
                    $lv = ($schema->require != ExpoSchema::REQUIRE_NON) ? "ERROR" : "INFOMATION";
                    $err = [ "docid" => $recDoc->docid,
                             "name"  => $name,
                             "lv"    => $lv,
                             "msg"   => "項目「".$name."」のデータがありません。" ];
                    $this->_errlist[] = $err;
                    continue;
                }
                
                //////////////////////////////
                // データをExcelに書き込む
                switch ($schema->type) {
                    case Schema::TYPE_TABLE:
                        //表形式（2次元配列）
                        $objSht = $this->_book->getSheetByName($name);
                        foreach($arrDocData[$name]["data"] as $arrRow) {
                            $this->writeRow($objSht, $name, $recDoc, $arrRow);
                        }
                        break;
                        
                    case Schema::TYPE_ROW:
                    case Schema::TYPE_COLUMN:
                        //列形式・行形式（1次元配列）
                        $objSht = $this->_book->getSheetByName($name);
                        $this->writeRow($objSht, $name, $recDoc, $arrDocData[$name]["data"]);
                        break;
                        
                    case Schema::TYPE_CELL:
                    case Schema::TYPE_PID:
                    case Schema::TYPE_ACNT:
                        //単一セル
                        $objSht = $this->_book->getSheetByName(self::SHEETNAME_CELL);
                        $row = $this->_posRow[self::SHEETNAME_CELL];
                        $col = $this->_idxCol[$name];
                        if (!$writen) {
                            //文書データを書く
                            $this->writeDocInfo($objSht, $row, $recDoc);
                            $writen = true;
                        }
                        $this->writeCell($objSht, $row, $col, $arrDocData[$name]["data"]);
                        break;
                        
                    default:
                    
                }
                
            }
            
            //単一セルシートの書き込みを次の行へ移す
            if (isset($this->_posRow[self::SHEETNAME_CELL])) $this->_posRow[self::SHEETNAME_CELL]++;
            
        }
        
        return;
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
        
        $idx = self::DOCINFO_COLNUM;
        $this->_posRow = array();
        $this->_idxCol = array();
        
        //フォーム定義でループ
        foreach($this->_schemata as $uid => $arrSchema) {
            
            //フォーム定義のスキーマでループ
            foreach($arrSchema as $name => $schema) {
                
                if ($schema->type == Schema::TYPE_NON) continue;    //非対応=スキップ
                if ($schema->require == Schema::REQUIRE_IGNORE) continue;   //処理対象外=スキップ
                
                if ($schema->type == Schema::TYPE_CELL) {
                    if (isset($this->_idxCol[$name])) continue; //既に他で定義済み
                    
                    //単一セルの場合、共通シート
                    $sht = self::SHEETNAME_CELL;
                    if (!isset($this->_posRow[$sht])) {
                        $objSheet = $this->_book->addSheet($sht);
                    }
                    $this->_posRow[$sht]  = 1;
                    $this->_idxCol[$name] = $idx++;
                } else {
                    if (isset($this->_posRow[$name])) continue; //既に他で定義済み
                    
                    //単一セル以外はそれぞれ別シート
                    $objSheet = $this->_book->addSheet($name);
                    $this->_posRow[$name]  = 1;
                }
                
            }
        }
        
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
    *  各シートのヘッダ表示と非表示列設定
    */
    public function writeHeader() {
        
        //Excel全シートに出力
        foreach($this->_posRow as $sht => $pos) {
            
            $objSheet = $this->_book->getSheetByName($sht);
            $header_cmn = ["文書ID", "フォーム名", "フォームID", "送信者", "送信者PID", "送信者アカウント",
                           "送信者所属", "送信者役職", "代理入力者", "代理入力者PID", "最終更新日"];
            $header_data = array();
            switch($sht) {
                case self::SHEETNAME_CELL:
                    foreach(array_keys($this->_idxCol) as $colname) {
                        $header_data[] = $colname;
                    }
                    //共通ヘッダ＋個別ヘッダのみ
                    $objSheet->writeRow(0, $header_cmn, 0, $this->_format["header_cmn"]);
                    $objSheet->writeRow(0, $header_data, count($header_cmn), $this->_format["header_data"]);
                    //列を非表示
                    $objSheet = $this->_book->getSheetByName($sht);
                    $objSheet->setColWidth(2, 2, 0);
                    $objSheet->setColWidth(4, 5, 0);
                    $objSheet->setColWidth(8, 9, 0);
                    break;
                    
                case self::SHEETNAME_ERR:
                    $header_data = ["文書ID","項目","区分","内容"];
                    //個別ヘッダのみ
                    $objSheet->writeRow(0, $header_data, 0, $this->_format["header_data"]);
                    //非表示列なし
                    break;
                    
                default:
                    $cnt = $objSheet->lastCol() - count($header_cmn);
                    $arrFill = array_fill(0, $cnt, "");
                    $header_data = ["データ"] + $arrFill;
                    //共通ヘッダ＋個別ヘッダのみ
                    $objSheet->writeRow(0, $header_cmn, 0, $this->_format["header_cmn"]);
                    $objSheet->writeRow(0, $header_data, count($header_cmn), $this->_format["header_data"]);
                    //列を非表示
                    $objSheet = $this->_book->getSheetByName($sht);
                    $objSheet->setColWidth(2, 2, 0);
                    $objSheet->setColWidth(4, 5, 0);
                    $objSheet->setColWidth(8, 9, 0);
            }
            
        }
        
        return;
    }
    
    /**
    *  データ1行をExcelシートに書き込む
    */
    public function writeRow(ExcelSheet $objSht, $name, $recDoc, $arrRow) {
        
        //セルインデックスの初期化
        $row = $this->_posRow[$name]++;
        
        //行単位ヘッダセル
        if (is_null($recDoc)) {
            $col = 0;
        } else {
            //文書情報を書き込む
            $col = self::DOCINFO_COLNUM;
            $this->writeDocInfo($objSht, $row, $recDoc);
        }
        
        foreach($arrRow as $data) {
            $this->writeCell($objSht, $row, $col++, $data);
        }
        
        return;
    }
    
    /**
    *  文書情報をExcelシートに書き込む
    */
    public function writeDocInfo(ExcelSheet $objSht, $row, $recDoc) {
        
        //初期値
        $col = 0;
        
        //文書ID
        $objSht->write($row, $col++, $recDoc->docid, $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        
        //フォーム名
        $objSht->write($row, $col++, $recDoc->name_ja, $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        
        //フォームID
        $objSht->write($row, $col++, $recDoc->uid, $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        
        //送信者
        $objSht->write($row, $col++, $recDoc->owner_name_ja, $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        
        //送信者PID
        $objSht->write($row, $col++, $recDoc->pid, $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        
        //送信者アカウント
        $objSht->write($row, $col++, $recDoc->account, $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        
        //送信者所属
        $objSht->write($row, $col++, $recDoc->group_name_ja, $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        
        //送信者役職
        $objSht->write($row, $col++, $recDoc->role_name_ja, $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        
        //代理入力者
        $objSht->write($row, $col++, $recDoc->input_pid, $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        
        //代理入力者
        $objSht->write($row, $col++, $recDoc->input_name_ja, $this->_format["val"], ExcelFormat::AS_NUMERIC_STRING);
        
        //最終更新日
        $dt = $this->_book->packDate($recDoc->updated_at->timestamp);
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
    
    
    /**
    *  エラーリストExcelSheetの追加
    */
    public function addErrList() {
        
        $objSheet = $this->_book->addSheet(self::SHEETNAME_ERR);
        $this->_posRow[self::SHEETNAME_ERR] = 1;
        
        foreach($this->_errlist as $err) {
            
            $this->writeRow($objSht, self::SHEETNAME_ERR, null, $err);
            
        }
        
    }
    
}
