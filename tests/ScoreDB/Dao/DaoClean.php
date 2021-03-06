<?php
namespace tests\ScoreDB\Dao;

use WScore\ScoreDB\Dao;

class DaoClean extends Dao
{
    public $tested = false;
    public $filtered  = false;
    public function onTestHook()
    {
        $this->tested = true;
    }
    public function onMoreFilter()
    {
        $this->filtered = true;
    }
}
