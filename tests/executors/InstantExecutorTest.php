<?php

namespace tests\executors;

/**
* 
*/
class InstantExecutorTest extends TestCase
{
    public function getExecutor()
    {
        return new \yii\queue\executors\instant\Executor([
            'queue' => $this->getQueue()
        ]);
    }
}