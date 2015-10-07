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
     * Excelファイルの抹消
     */
    public function removeFile(Request $request)
    {
        
        $this->validate($request, [
            'idx' => 'required|integer',
        ]);
        $objDataSet = app('Dataset');
        $rtn = $objDataSet->removeFile($request->get('idx'));
        return response()->json($rtn);
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
    
    
    /**
     * データをExcelファイルでダウンロード
     */
    public function main(Request $request)
    {
        $this->validate($request, [
            'st'  => 'in:s,m',
            'idx' => 'integer',
        ]);
        
        $dataset = app('Dataset');
        $view = view('main')->with('objDataset', $dataset)
                            ->with('sheettype',  $request->get('st', 's'))
                            ->with('sheetidx',   $request->get('idx', 0));
        return $view;
    }
    
    
    
    /**
     * データをクリアする
     */
    public function clear(Request $request)
    {
        
        $dataset = app('Dataset');
        $dataset->initialize();
        $request->session()->regenerate();  //セッション再生成
        return redirect('/');
    }
    
    
    
}
