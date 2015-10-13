<?php

namespace App\Http\Middleware;

use Log;
use Closure;

class CleanTempFile
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
        if (!session()->has('outputfiles')) return;
        
        $filepath = session()->pull('outputfiles', "");
        if (file_exists($filepath)) {
            @unlink($filepath);
            Log::info('tempfile remove [PATH='.$filepath.']');
        }
        return;
    }
}
