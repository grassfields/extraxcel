<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Dataset;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /*
        |--------------------------------------------------------------------------
        | サービスコンテナ
        |--------------------------------------------------------------------------
        |
        */
        //////////////////////////////
        // Dataset
        $this->app->singleton('Dataset', function($app) {
            if (session()->has('Dataset')) {
                return session('Dataset');
            } else {
                return new Dataset();
            }
        });
        //////////////////////////////
        // Reader
        $this->app->bind('Reader', function($app, $param) {
            switch(strtolower(config('extraxcel.excel_lib'))){
                case 'phpspreadsheet':  $obj=app('App\Reader\PhpSpreadsheetReader', $param); break;
                /*
                case 'phpexcel':        $obj=app('App\Reader\PHPExcelReader', $param); break;
                case 'libxl':           $obj=app('App\Reader\LibXLReader',    $param); break;
                */
                case 'auto':
                default:
                    $obj= app('App\Reader\PhpSpreadsheetReader', $param);
                    /*
                    $obj= (extension_loaded('excel')) ? app('App\Reader\LibXLReader',    $param)
                                                      : app('App\Reader\PHPExcelReader', $param);
                    */
                    break;
            }
            return $obj;
        });
        //////////////////////////////
        // Writer
        $this->app->bind('Writer', function($app, $param) {
            switch(strtolower(config('extraxcel.excel_lib'))){
                case 'phpspreadsheet':  $obj=app('App\Writer\PhpSpreadsheetWriter', $param); break;
                /*
                case 'phpexcel':        $obj=app('App\Writer\PHPExcelWriter', $param); break;
                case 'libxl':           $obj=app('App\Writer\LibXLWriter',    $param); break;
                */
                case 'auto':
                default:
                    $obj=app('App\Writer\PhpSpreadsheetWriter', $param);
                    /*
                    $obj= (extension_loaded('excel')) ? app('App\Writer\LibXLWriter',    $param)
                                                      : app('App\Writer\PHPExcelWriter', $param);
                    */
                    break;
            }
            return $obj;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /*
        |--------------------------------------------------------------------------
        | 公開設定
        |--------------------------------------------------------------------------
        |
        */
        $this->publishes([
                //components
                __DIR__.'/../../vendor/components/jquery/jquery.min.js'
                            => public_path('packages/components/jquery/jquery.min.js'),
                __DIR__.'/../../vendor/components/jqueryui/jquery-ui.min.js'
                            => public_path('packages/components/jqueryui/jquery-ui.min.js'),
                __DIR__.'/../../vendor/components/bootstrap/css/bootstrap.min.css'
                            => public_path('packages/components/bootstrap/css/bootstrap.min.css'),
                __DIR__.'/../../vendor/components/bootstrap/js/bootstrap.min.js'
                            => public_path('packages/components/bootstrap/js/bootstrap.min.js'),
                __DIR__.'/../../vendor/components/bootstrap/fonts/glyphicons-halflings-regular.eot'
                            => public_path('packages/components/bootstrap/fonts/glyphicons-halflings-regular.eot'),
                __DIR__.'/../../vendor/components/bootstrap/fonts/glyphicons-halflings-regular.svg'
                            => public_path('packages/components/bootstrap/fonts/glyphicons-halflings-regular.svg'),
                __DIR__.'/../../vendor/components/bootstrap/fonts/glyphicons-halflings-regular.ttf'
                            => public_path('packages/components/bootstrap/fonts/glyphicons-halflings-regular.ttf'),
                __DIR__.'/../../vendor/components/bootstrap/fonts/glyphicons-halflings-regular.woff'
                            => public_path('packages/components/bootstrap/fonts/glyphicons-halflings-regular.woff'),
                __DIR__.'/../../vendor/components/bootstrap/fonts/glyphicons-halflings-regular.woff2'
                            => public_path('packages/components/bootstrap/fonts/glyphicons-halflings-regular.woff2'),
                //blueimp
                __DIR__.'/../../vendor/blueimp/jquery-file-upload/js/jquery.fileupload.js'
                            => public_path('packages/blueimp/jquery-file-upload/js/jquery.fileupload.js'),
            ]);
    }
}
