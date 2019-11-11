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

    public function testStatusOnWaitingJob()
    {
        $queue = $this->getQueue();

        $jobId = $queue->push(new DummyJob());

        $this->assertEquals(Queue::STATUS_WAITING, $queue->status($jobId));
    }

    public function testStatusOnDoneJob()
    {
        $queue = $this->getQueue();

        $jobId = $queue->push(new DummyJob());

        $queue->run(false);

        $this->assertEquals(Queue::STATUS_DONE, $queue->status($jobId));
    }

    public function testStatusNotExistingJob()
    {
        $queue = $this->getQueue();

        $this->assertEquals(Queue::STATUS_DONE, $queue->status('notexsiting'));
    }

    public function testReservedJob()
    {
        /**
         * @var \yii\queue\Queue $queue
         */
        $queue = $this->construct($this->queueClass, [$this->queueConfig], [
            'getClient' => $this->make(\GearmanClient::class, [
                'jobStatus' => [true, true]
            ])
        ]);

        $this->assertEquals(Queue::STATUS_RESERVED, $queue->status('definitelyreservedjob'));
    }
}