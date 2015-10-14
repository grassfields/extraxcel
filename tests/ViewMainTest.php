<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ViewMainTest extends TestCase
{
    /**
     * ビュー「main」関連のテスト
     *
     * @return void
     */
    public function testMainView()
    {
        //レスポンスHTMLに特定の文字列がある
        $this->visit('/')
             ->see('<title>Extraxcel</title>')
             ->see('保存しておいたセル範囲の設定ファイルを取り込むことで項目の並び順などが簡単に切り替えられます。');
             
        
        //HTTPレスポンスが200を返す
        $status = $this->call('GET', '/')->getStatusCode();
        $this->assertEquals( 200, $status);
        
    }
}
