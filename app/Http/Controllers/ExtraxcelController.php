<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ExtraxcelController extends Controller
{
    
    /*************************************
    * 定数定義
    **************************************/
    const DOWNLOAD_FILENAME = "extraxcel";
    
    /**
     * Excelファイルのアップロード
     */
    public function postFile(Request $request)
    {
        
        $this->validate($request, [
            'upfile' => 'required',
        ]);
        
        $objFile = app('App\Reader\ExcelReader', [ $request->file('upfile') ]);
        $objDataSet = app('Dataset');
        $arrLoaded = $objDataSet->load($objFile);
        
        session(['Dataset' => $objDataSet]);
        return response()->json($arrLoaded);
        
    }
    
    
    
    /**
     * データをExcelファイルでダウンロード
     */
    public function download()
    {
        $objDataSet     = app('Dataset');
        $makeFilePath   = $objDataSet->output();
        $outputFileName = self::DOWNLOAD_FILENAME."_".date("YmdHi").".xlsx";
        return response()->download($filepath, $outputFileName);
    }
    
    
    
}
