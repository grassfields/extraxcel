<?php

namespace App;

class Dataset
{
    
    /*************************************
    * 定数定義
    **************************************/
    const SHEETNAME_CELL   = "単一セル";
    const SHEETNAME_ERR    = "ErrorList";
    const DOCINFO_COLNUM   = 11;    //文書情報の項目数
    
    /**
    *  プロパティ定義
    */
    public  $files;
    public  $schemata;
    
    private $_dataset;
    private $_errlist;
    
    private $_posRow;   //シート別行番号
    private $_idxCol;   //列番号
    private $_format;   //セルフォーマット
    
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
        
        $this->files     = array();
        $this->schemata  = null;
        
        $this->_dataset  = array();
        $this->_errlist  = array();
        $this->_posRow   = array();
        $this->_idxCol   = array();
        $this->_format   = array();
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}
