<?php

namespace App\Providers;

use App\Dataset;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
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
                //blueimp
                __DIR__.'/../../vendor/blueimp/jquery-file-upload/js/jquery.fileupload.js'
                            => public_path('packages/blueimp/jquery-file-upload/js/jquery.fileupload.js'),
            ]);
        
    }

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
        $this->app->singleton('Dataset', function($app) {
            if (session()->has('Dataset')) {
                return session('Dataset');
            } else {
                return new Dataset();
            }
        });
    }
}
