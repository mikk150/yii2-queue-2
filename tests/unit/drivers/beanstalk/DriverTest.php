<?php

namespace tests\unit\drivers\beanstalk;

use tests\app\DummyJob;
use tests\unit\drivers\TestCase;
use yii\queue\beanstalk\Queue;

class DriverTest extends TestCase
{
    public $queueClass = Queue::class;

    public function _before()
    {
        $this->queueConfig = [
            'host' => getenv('BEANSTALK_HOST') ?: 'localhost',
        ];
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
}
