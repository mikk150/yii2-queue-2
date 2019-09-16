<?php

namespace tests\unit\drivers;

abstract class TestCase extends \tests\TestCase
{
    public $queueClass;

    public $queueConfig;


    /**
     * @return \yii\queue\Queue;
     */
    public function getQueue()
    {
        return new $this->queueClass($this->queueConfig);
    }
}
