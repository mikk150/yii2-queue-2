<?php

namespace tests\unit\drivers\db;

use React\Promise\Promise;
use tests\app\DummyJob;
use tests\unit\drivers\TestCase;
use tests\unit\drivers\traits\DelayTrait;
use yii\queue\ExecEvent;
use yii\queue\redis\Queue;

class DriverTest extends TestCase
{
    use DelayTrait;

    public function setUp()
    {
        $this->getQueue()->clear();

        parent::setUp();
    }

    public $queueClass = Queue::class;

    public $queueConfig = [];

    public function testPushMessage()
    {
        $queue = $this->getQueue();

        $queue->push(new DummyJob());
    }

    /**
     * @expectedException yii\base\NotSupportedException
     * @expectedExceptionMessage Job priority is not supported in the driver.
     */
    public function testPushMessageWithPriority()
    {
        $queue = $this->getQueue();

        $queue->priority(10)->push(new DummyJob());
    }

    public function testRemove()
    {
        $queue = $this->getQueue();

        $id = $queue->push(new DummyJob());

        $this->assertEquals(Queue::STATUS_WAITING, $queue->status($id));

        $queue->remove($id);

        $this->assertEquals(Queue::STATUS_DONE, $queue->status($id));
    }

    public function testRemoveOnJobThatDoesNotExist()
    {
        $queue = $this->getQueue();
        $this->assertFalse($queue->remove(22));
    }

    public function testDelete()
    {
        $queue = $this->getQueue();

        $id = $queue->push(new DummyJob());

        $queue->run(false);

        $this->assertEquals(Queue::STATUS_DONE, $queue->status($id));
    }

    /**
     * @expectedException yii\base\InvalidArgumentException
     * @expectedExceptionMessage Unknown message ID: test.
     */
    public function testStatusNotNumeric()
    {
        $queue = $this->getQueue();
        $queue->status('test');
    }

    /**
     * @expectedException yii\base\InvalidArgumentException
     * @expectedExceptionMessage Unknown message ID: -44.
     */
    public function testStatusNotPositive()
    {
        $queue = $this->getQueue();
        $queue->status(-44);
    }

    public function testStatusStartedButNotFinishedJob()
    {
        $id = null;
        $queue = $this->construct(Queue::class, [],
        [
            'handleMessage' => function () use (&$queue, &$id) {
                $this->assertEquals(Queue::STATUS_RESERVED, $queue->status($id));
                return new Promise(function ($fulfull) {
                    call_user_func($fulfull, new ExecEvent());
                });
            }
        ]);

        $id = $queue->push(new DummyJob());

        $queue->run(false);
    }
}
