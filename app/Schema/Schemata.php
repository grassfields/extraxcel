<?php

namespace App\Schema;

use App\Schema\Schema;
use PHPExcel_NamedRange;

class Schemata
{
  
    /*************************************
    * 定数定義
    **************************************/
    const CELL_SINGLE = 'single';
    const CELL_MULTI  = 'multi';
    
    /**
    *  変数定義
    */
    public  $locked;    //ロック（これ以上拡張しない）
    public  $read_by;   //データ読込キー（名前orセル範囲）
    
    private $_single;
    private $_single_odr;
    private $_multi;
    private $_multi_odr;
    
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
        
        $this->locked   = false;
        $this->read_by  = 'name';
        
        $this->_single      = array();
        $this->_single_odr  = array();
        $this->_multi       = array();
        $this->_multi_odr   = array();
        
    }
    
    /**
     *  スキーマ情報配列を追加する
     */
    public function addSchema($schema, $scope = null) {
        
        if ($schema instanceof PHPExcel_NamedRange) {
            //PHPExcelのNamedRangeオブジェクト
            $name = $schema->getName();
            $objSchema = app('App\Schema\Schema');
            $objSchema->setNamedRange($schema, $scope );
            
        } else if ($schema instanceof Schema) {
            //Schemaオブジェクト
            $name = $schema->name;
            $objSchema = $schema;
            
        } else if (is_object($schema)) {
            //オブジェクト変数
            $name = $schema->name;
            $objSchema = app('App\Schema\Schema');
            $objSchema->set($schema);
        }
        
        switch($objSchema->type) {
            case Schema::TYPE_TABLE:
            case Schema::TYPE_ROW:
            case Schema::TYPE_COLUMN:
                //////////////////////////////////////
                // 複数セル
                if (isset($this->_multi[$name])) return;
                $this->_multi[$name] = $objSchema;
                $this->_multi_odr[]  = $name;
                break;
                
            case Schema::TYPE_CELL:
            case Schema::TYPE_PID:
            case Schema::TYPE_ACT:
                //////////////////////////////////////
                // 単一セル
                if (isset($this->_single[$name])) return;
                $this->_single[$name] = $objSchema;
                $this->_single_odr[]  = $name;
                break;
        }
        
        return;
    }
    
    /**
     *  スキーマ出力順序を更新する（単一セル）
     */
    public function resetOrder_single(array $names) {
        $this->_single_odr[] = array();
        foreach($names as $name) {
            if (!isset($this->_single[$name])) continue;
            $this->_single_odr[] = $name;
        }
    }
    
    /**
     *  スキーマ出力順序を更新する（複数セル）
     */
    public function resetOrder_multi(array $names) {
        $this->_multi_odr[] = array();
        foreach($names as $name) {
            if (!isset($this->_multi[$name])) continue;
            $this->_multi_odr[] = $name;
        }
    }
    
    /**
     *  スキーマ情報配列を統合する
     */
    public function merge(Schemata $schemata) {
        
        if ($this->locked) return;  //ロック済のため処理なし
        
        //スキーマのマージ（単一セル）
        $names_single = $schemata->getSchemaNames(self::CELL_SINGLE);
        foreach($names_single as $name) {
            if (!isset($this->_single[$name])) {
                $this->addSchema($schemata->getSchema($name, self::CELL_SINGLE));
            }
        }
        
        //スキーマのマージ（単一セル）
        $names_multi = $schemata->getSchemaNames(self::CELL_MULTI);
        foreach($names_multi as $name) {
            if (!isset($this->_multi[$name])) {
                $this->addSchema($schemata->getSchema($name, self::CELL_MULTI));
            }
        }
        
    }
    
    /**
     *  スキーマ情報名称配列を取得する
     */
    public function getSchemaNames( $type = Schemata::CELL_SINGLE) {
        $arr = ($type == self::CELL_SINGLE) ? $this->_single_odr
                                           : $this->_multi_odr;
        return $arr;
    }
    
    /**
     *  スキーマ情報を取得する
     */
    public function getSchema( $name, $type = Schemata::CELL_SINGLE) {
        if ($type == self::CELL_SINGLE) {
            $obj = (isset($this->_single[$name])) ? $this->_single[$name]
                                                  : app('App\Schema\Schema');
        } else {
            $obj = (isset($this->_multi[$name]))  ? $this->_multi[$name]
                                                  : app('App\Schema\Schema');
        }
        return $obj;
    }
}
