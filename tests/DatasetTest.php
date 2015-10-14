<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Dataset;

class DatasetTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        
        $objDataset = app('Dataset');
        
        $this->assertInstanceOf('App\Dataset', $objDataset);
        
        
        
        
        
        
        
        
        
        $this->assertTrue(true);
    }
}
