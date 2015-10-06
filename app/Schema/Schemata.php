<?php

namespace App\Schema;

class Schemata
{
  
    /*************************************
    * 定数定義
    **************************************/
    
    /**
    *  変数定義
    */
    public  $locked;    //ロック（これ以上拡張しない）
    private $_data;
    private $_sheets;
    
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
        $this->_data    = array();
        $this->_sheets  = array();
        
    }
    
    /**
    * 現在の要素を返す
    */
    public function current() {
        return current($this->_data);
    }
    /**
    * 現在の要素のキーを返す
    */
    public function key() {
        return key($this->_data);
    }
    /**
    * 次の要素に進む
    */
    public function next() {
        return next($this->_data);
    }
    /**
    * イテレータの最初の要素に巻き戻す
    */
    public function rewind() {
        return reset($this->_data);
    }
    /**
    * 現在位置が有効かどうかを調べる
    * @return 成功した場合に TRUE を、失敗した場合に FALSE を返します。
    */
    public function valid() {
        return !($this->current() === false);
    }
    
    
    /**
     *  スキーマ情報配列からセットする
     */
    public function setData($arrData) {
        $this->initialize();
        $this->_data = $arrData;
        return;
    }
    
    /**
     *  スキーマ情報配列を取得する
     */
    public function getData() {
        return $this->_data;
    }
    
    /**
     *  スキーマ情報配列を追加する
     */
    public function add(Schema $schema) {
        
        if (isset($this->_data[$schema->$name])) return;    //重複は無視
        $this->_data[$schema->$name] = $schema->$name;
        return;
    }
    
    
}
