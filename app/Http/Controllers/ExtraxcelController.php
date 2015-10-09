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
            'st'     => 'in:s,m',
            'idx'    => 'integer',
            'upfile' => 'required',
        ]);
        
        $objFile = app('App\Reader\ExcelReader', [ $request->file('upfile') ]);
        $objDataSet = app('Dataset');
        $arrLoaded = $objDataSet->load($objFile);
        
        //セッションに保存
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
        
        //データ抹消
        $rtn = $objDataSet->removeFile($request->get('idx'));
        
        //セッションに保存
        session(['Dataset' => $objDataSet]);
        
        return response()->json($rtn);
    }
    
    
    
    /**
     * データをExcelファイルでダウンロード
     */
    public function download()
    {
        $objDataSet = app('Dataset');
        $objWriter  = app('Writer', [ $objDataSet ]);
        $filepath   = $objWriter->save();
        $responseFileName = self::DOWNLOAD_FILENAME."_".date("YmdHi").".xlsx";
        return response()->download($filepath, $responseFileName);
    }
    
    /**
     * スキーマ情報のエクスポート
     */
    public function exportSchema()
    {
        $objDataSet = app('Dataset');
        $filepath   = $objDataSet->schemata->save();
        $responseFileName = self::DOWNLOAD_FILENAME."-schema_".date("YmdHi").".json";
        return response()->download($filepath, $responseFileName);
    }
    /**
     * スキーマ情報のエクスポート
     */
    public function importSchema(Request $request)
    {
        $this->validate($request, [
            'upschema' => 'required',
        ]);
        
        $objFile = $request->file('upschema');
        $objDataSet = app('Dataset');
        
        $str = $objFile->getRealPath();
        $strData = file_get_contents($objFile->getPathname());
        $rtn = $objDataSet->schemata->load($strData);
        
        //セッションに保存
        session(['Dataset' => $objDataSet]);
        return response()->json($rtn);
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
    
    /**
     * データをダンプする
     */
    public function dump(Request $request)
    {
        $dataset = app('Dataset');
        dd($dataset);
    }
    
    
    
}
