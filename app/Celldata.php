<?php

namespace App;

use App\Schema\Schemata;
use App\Reader\AbstractExcelReader;


class Celldata
{

    /**
    *  定数定義
    */
    const TYPE_NUMERIC = 'num';
    const TYPE_DATE = 'date';
    const TYPE_STRING = 'str';
    const TYPE_BOOL = 'bool';
    const TYPE_NULL = 'null';

    /**
    *  プロパティ定義
    */
    public  $value;
    public  $formatted_value;
    public  $type;
    public  $format;

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

        $this->value            = null;
        $this->formatted_value  = null;
        $this->type             = self::TYPE_NULL;
        $this->format           = "";

    }


}
