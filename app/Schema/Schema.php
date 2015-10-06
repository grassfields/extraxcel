<?php

namespace App\Schema;

use PHPExcel_NamedRange;
use PHPExcel_Cell;
use PHPExcel_Exception;
use ExcelBook;

class Schema
{
    
    /**
    *  定数定義
    */
    const TYPE_TABLE       = "table";     //表形式
    const TYPE_ROW         = "row";       //行形式
    const TYPE_COLUMN      = "col";       //列形式
    const TYPE_CELL        = "cell";      //単一セル
    const TYPE_PID         = "pid";       //PID指定形式
    const TYPE_ACT         = "account";   //アカウント指定形式
    const TYPE_NON         = "non";       //非対応
    const ADD_OK           = "ok";        //セル範囲可変
    const ADD_NG           = "ng";        //セル範囲固定
    const REQUIRE_ALL      = "all";       //全て必須
    const REQUIRE_NOTALL   = "notall";    //一つ以上の入力必須
    const REQUIRE_NON      = "non";       //任意（空を許可）
    const REQUIRE_IGNORE   = "ignore";    //処理対象外
    
    /**
    *  変数定義
    */
    public $name;
    public $xlrange;
    public $xlscope;
    public $xlsheet;
    public $type;
    public $rows;
    public $cols;
    public $allowadd;
    public $require;
    
    
    /**
    *  コンストラクタ
    */
    public function __construct() {
        $this->initialize();
    }
    
    /**
    *  項目初期化
    */
    public function initialize() {
        $this->name     = "";
        $this->xlrange  = "";
        $this->xlscope  = null;
        $this->xlsheet  = "";
        $this->type     = null;
        $this->rows     = 0;
        $this->cols     = 0;
        $this->allowadd = self::ADD_NG;
        $this->require  = self::REQUIRE_NON;
    }
    
    /**
     *  新規作成(ExcelのNamedRange配列から)
     */
    public function setNamedRange(PHPExcel_NamedRange $objNR, $scope ) {
        
        $this->initialize();
        
        $name = $objNR->getName();
        $rng  = $objNR->getRange();
        $sht  = $objNR->getWorksheet()->getTitle();
        try {
            $arrDim = PHPExcel_Cell::rangeDimension($rng);
            if (($arrDim[0]==1) && ($arrDim[1]==1)) { $type = self::TYPE_CELL; }
                           else if ($arrDim[0]==1)  { $type = self::TYPE_COLUMN; }
                           else if ($arrDim[1]==1)  { $type = self::TYPE_ROW; }
                           else                     { $type = self::TYPE_TABLE; }
        } catch(PHPExcel_Exception $e) {
            $type = self::TYPE_NON; //対象外
        }
        if ($scope != ExcelBook::SCOPE_WORKBOOK) {
            //スコープ範囲が「ブック」以外は非対応
            $type = self::TYPE_NON; //対象外
        }
        
        $this->name     = $name;
        $this->xlrange  = $rng;
        $this->xlscope  = $scope;
        $this->xlsheet  = $sht;
        $this->type     = $type;
        $this->rows     = $arrDim[1];
        $this->cols     = $arrDim[0];
        $this->allowadd = self::ADD_NG;
        $this->require  = self::REQUIRE_NON;
        
    }
    
    /**
     *  スキーマセット
     */
    public function set( $obj ) {
        
        $this->name     = $obj->name;
        $this->xlrange  = $obj->xlrange;
        $this->xlscope  = $obj->xlscope;
        $this->xlsheet  = $obj->xlsheet;
        $this->type     = $obj->type;
        $this->rows     = $obj->rows;
        $this->cols     = $obj->cols;
        $this->allowadd = $obj->allowadd;
        $this->require  = $obj->require;
        
        return;
    }
    
    /**
     *  スキーマ情報オブジェクトを取得する。
     */
    public function get() {
        $obj = new \stdClass;
        $obj->name      = $this->name;
        $obj->xlrange   = $this->xlrange;
        $obj->xlscope   = $this->xlscope;
        $obj->xlsheet   = $this->xlsheet;
        $obj->type      = $this->type;
        $obj->rows      = $this->rows;
        $obj->cols      = $this->cols;
        $obj->allowadd  = $this->allowadd;
        $obj->require   = $this->require;
        return $obj;
    }
    
    /**
     *  スキーマ情報配列（名称付き）を取得する。
     */
    public function getInfo() {
        
        $arrData = array(
                "name"        => $this->name,
                "xlrange"     => $this->xlrange,
                "xlscope"     => $this->xlscope,
                "xlsheet"     => $this->xlsheet,
                "type"        => $this->type,
                "type_nm"     => "",
                "rows"        => $this->rows,
                "cols"        => $this->cols,
                "allowadd"    => $this->allowadd,
                "allowadd_nm" => "",
                "require"     => $this->require,
                "require_nm"  => "",
                "msg"         => ""
            );
        
        //形式
        switch($this->type) {
            case self::TYPE_TABLE:      $val = "表形式";        break;
            case self::TYPE_ROW:        $val = "行形式";        break;
            case self::TYPE_COLUMN:     $val = "列形式";        break;
            case self::TYPE_CELL:       $val = "単一セル";      break;
            case self::TYPE_PID:        $val = "職員ID";        break;
            case self::TYPE_ACT:        $val = "アカウント";    break;
            case self::TYPE_NON:
            default:
                $val = "非対応";
                if (    ($this->name == ExpoDataSheet::SHEETNAME_CELL)
                     || ($this->name == ExpoDataSheet::SHEETNAME_ERR)) {
                    $arrData["msg"] = "「".$this->name."」はシステムで予約された用語のため、フォームの項目としては使用できません）";
                } else if ($this->xlscope != ExcelBook::SCOPE_WORKBOOK) {
                    $arrData["msg"] = "「".$this->name."」は範囲が[Book]ではないため、フォームの項目としては使用できません）";
                } else {
                    $arrData["msg"] = "セル参照範囲「".$this->xlrange."」は非対応です。";
                }
                break;
        }
        $arrData["type_nm"] = $val;
        
        //セル範囲拡張の許可
        switch($this->allowadd) {
            case self::ADD_OK:  $val = "拡張可";    break;
            case self::ADD_NG:
            default:            $val = "範囲固定";  break;
        }
        $arrData["allowadd_nm"] = $val;
        
        //必須
        switch($this->require) {
            case self::REQUIRE_ALL:     $val = "全て必須";          break;
            case self::REQUIRE_NOTALL:  $val = "一つ以上の入力必須";break;
            case self::REQUIRE_NON:     $val = "任意（空欄可）";    break;
            case self::REQUIRE_IGNORE:
            default:                    $val = "処理対象外";        break;
        }
        $arrData["require_nm"] = $val;
        
        return $arrData;
    }
    
    /**
     *  セル範囲情報がスキーマと一致するか検証する
     */
    public function validate($arrInfo) {
        
        //戻り配列初期化
        $arrRtn = [ "valid" => false,
                    "cols"  => null,
                    "rows"  => null,
                    "msg"   => ""       ];
        
        // 無視（処理対象外）チェック/////////////////////
        if ($this->require==self::REQUIRE_IGNORE) {
            //正常
            $arrRtn["valid"] = true;
            return $arrRtn;
        }
        
        // セル範囲取得チェック ////////////////////////
        if (!is_array($arrInfo)) {
            if ($this->require==self::REQUIRE_ALL ||
                $this->require==self::REQUIRE_NOTALL) {
                $arrRtn["msg"] = "入力必須のセル範囲が取得できません";
                return $arrRtn;
            } else {
                //任意なのでvalid=OKでチェックを抜ける
                $arrRtn["valid"] = true;
                $arrRtn["rows"] = 0;
                $arrRtn["cols"] = 0;
                return $arrRtn;
            }
        }
        
        // セル範囲 対応チェック ////////////////////////
        // 行数や列数が無制限（マイナス値)は非対応
        if ( ($arrInfo["row_first"]<0) || ($arrInfo["row_last"]<0) ||
             ($arrInfo["col_first"]<0) || ($arrInfo["col_last"]<0) ) {
            $arrRtn["msg"] = "処理できないセル範囲です";
            return $arrRtn;
        }
        
        //行数と列数を算出
        $arrRtn["rows"] = $arrInfo["row_last"] - $arrInfo["row_first"] + 1;
        $arrRtn["cols"] = $arrInfo["col_last"] - $arrInfo["col_first"] + 1;
        
        //セル範囲変更チェック/////////////////////
        if ($this->allowadd==self::ADD_OK) {
            if ($this->type==self::TYPE_ROW && $arrRtn["rows"] > 1) {
                //1行形式データに2行以上の配列データがあればエラー
                $arrRtn["msg"] = "1行形式データに対して複数行のセル範囲が指定されています。[ROWS=".$arrRtn["rows"]."]";
                return $arrRtn;
            }
            if ($this->type==self::TYPE_COLUMN && $arrRtn["cols"] > 1) {
                //1列形式データに2列以上の配列データがあればエラー
                $arrRtn["msg"] = "1列形式データに対して複数列のセル範囲が指定されています。[COLS=".$arrRtn["cols"]."]";
                return $arrRtn;
            }
        } else {
            //変更不許可で変更されていたらエラー
            if ( ($this->rows != $arrRtn["rows"]) || ($this->cols != $arrRtn["cols"]) ) {
                $arrRtn["msg"] = "セル範囲の変更は許可されていません。";
                return $arrRtn;
            }
        }
        
        //正常
        $arrRtn["valid"] = true;
        return $arrRtn;
    }
}

