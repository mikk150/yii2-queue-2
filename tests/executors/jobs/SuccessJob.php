<?php

namespace tests\executors\jobs;

use yii\queue\Job;
use yii\base\Object;

/**
* 
*/
class SuccessJob extends Object implements Job
{
    public $return;

    public function execute()
    {
        return $this->return;
    }
}
