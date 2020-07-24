<?php

namespace App;

use App\Schema\Schemata;
use App\Reader\AbstractExcelReader;


class Dataset
{

    /**
    *  定数定義
    */
    const SHEETNAME_CELL   = "単一セル";
    const SHEETNAME_ERR    = "ErrorList";
    const DOCINFO_COLNUM   = 11;    //文書情報の項目数

    /**
    *  プロパティ定義
    */
    public  $files;
    public  $schemata;

    private $_dataset_single;
    private $_dataset_multi;

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
        $this->schemata  = app('App\Schema\Schemata');

        $this->_dataset_single  = array();
        $this->_dataset_multi   = array();
        $this->_errlist  = array();
        $this->_format   = array();
    }

    /**
    *  Excelファイルのデータを読む
    */
    public function load( AbstractExcelReader $objReader ) {

        $err = "";

        //ファイル情報を読み込み
        $fileidx = count($this->files);
        $this->files[$fileidx] = $objReader->getFileInfo();

        try {
            //スキーマ情報を読み込み
            $schemata = $objReader->getSchemataFromExcel();
            $this->schemata->merge($schemata);

            //データの読み込み
            $dataset_single = $objReader->readData_single($this->schemata);
            $dataset_multi  = $objReader->readData_multi($this->schemata);
            $this->_dataset_single[$fileidx] = $dataset_single;
            $this->_dataset_multi[$fileidx]  = $dataset_multi;
        } catch(\Exception $e) {
            $schemata = null;
            $err = $e->getMessage();
            $dataset_single = [];
            $dataset_multi  = [];
        }

        $this->files[$fileidx]['error']  = $err;

        $arrRtn = [ 'file'          => $objReader->getFileInfo(),
                    'schema'        => $schemata,
                    'data_single'   => $dataset_single,
                    'data_multi'    => $dataset_multi,
                    'error'         => $err
                  ];
        return $arrRtn;
    }

    /**
    *  Excelファイルのデータを取得
    */
    public function getDataset($type = Schemata::CELL_SINGLE) {
        return ($type == Schemata::CELL_SINGLE) ? $this->_dataset_single : $this->_dataset_multi;
    }


    /**
    *  Excelファイルのデータを抹消
    */
    public function removeFile($idx) {

        if (!isset($this->files[$idx])) return false;

        unset($this->files[$idx]);
        unset($this->_dataset_single[$idx]);
        unset($this->_dataset_multi[$idx]);
        return true;
    }








}
