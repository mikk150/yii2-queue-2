<?php

namespace tests\executors\jobs;

use yii\queue\Job;
use yii\base\Object;
use Exception;

/**
* 
*/
class RejectJob extends Object implements Job
{
    public $message;

    public function execute()
    {
        throw new Exception('test');
    }
}
