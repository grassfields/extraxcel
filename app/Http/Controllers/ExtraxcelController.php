<?php

namespace App\Http\Controllers;

use Log;
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
        
        $objFile = app('Reader', [ 'objFile' => $request->file('upfile') ]);
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
        
        //出力ファイル配列にパスを追加
        Log::info('dataset-tempfile create [PATH='.$filepath.']');
        session()->put('outputfiles', $filepath);
        
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
        
        //出力ファイル配列にパスを追加
        Log::info('schema-tempfile create [PATH='.$filepath.']');
        session()->put('outputfiles', $filepath);
        
        $responseFileName = self::DOWNLOAD_FILENAME."-schema_".date("YmdHi").".json";
        return response()->download($filepath, $responseFileName);
    }
    
    
    /**
     * スキーマ情報の並び順を更新
     */
    public function sortSchema(Request $request)
    {
        $this->validate($request, [
            'single_odr' => 'array',
            'multi_odr' =>  'array',
        ]);
        
        $objDataSet = app('Dataset');
        if ($request->has('single_odr')) {
            $objDataSet->schemata->resetOrder_single($request->get('single_odr'));
        }
        if ($request->has('multi_odr')) {
            $objDataSet->schemata->resetOrder_multi($request->get('multi_odr'));
        }
        
        //セッションに保存
        session(['Dataset' => $objDataSet]);
        return response()->json(true);
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
        session()->flush();
        session()->regenerate();  //セッション再生成
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
