<?php

namespace tests\unit\drivers\gearman;

use tests\app\DummyJob;
use tests\unit\drivers\traits\PriorityTrait;
use yii\queue\gearman\Queue;

class DriverTest extends \tests\unit\drivers\TestCase
{
    use PriorityTrait;
    // use DelayTrait;

    public $queueClass = Queue::class;

    public function _before()
    {
        $this->queueConfig = [
            'host' => getenv('GEARMAN_HOST') ?: 'localhost',
        ];

        $queue = $this->getQueue();
        $queue->clear();

        parent::_before();
    }

    /**
     * @expectedException yii\base\NotSupportedException
     * @expectedExceptionMessage Delayed work is not supported in the driver.
     */
    public function testPushMessageWithDelay()
    {
        $queue = $this->getQueue();

        $queue->delay(10)->push(new DummyJob());
    }
}