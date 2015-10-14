<?php

namespace App\Http\Middleware;

use Log;
use Closure;

class ExtraxcelTerminate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
    
    
    /**
     * Terminate an response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function terminate($request, $response)
    {
        ////////////////////////////////
        // 終了ログ出力
        $log = session()->getId()."\t";
        $log.= $request->path()."\t";
        $log.= number_format(memory_get_peak_usage())."\t";
        Log::info($log);
        
        ////////////////////////////////
        // 一時ファイルの削除
        if (session()->has('outputfiles')) {
            $filepath = session()->pull('outputfiles', "");
            if (file_exists($filepath)) {
                @unlink($filepath);
                Log::info('tempfile remove [PATH='.$filepath.']');
            }
        }
        return;
    }
}
