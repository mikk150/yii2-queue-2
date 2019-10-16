<?php

namespace tests\unit\drivers\file;

use React\Promise\Promise;
use tests\app\DummyJob;
use tests\unit\drivers\TestCase;
use tests\unit\drivers\traits\DelayTrait;
use yii\helpers\FileHelper;
use yii\queue\ExecEvent;
use yii\queue\file\Queue;

class DriverTest extends TestCase
{
    use DelayTrait;

    public $queueClass = Queue::class;
    public function _before()
    {
        $this->queueConfig = [
            'path' => codecept_output_dir() . 'queue'
        ];
        FileHelper::removeDirectory($this->queueConfig['path']);
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

    public function testRemoveDelayed()
    {
        $queue = $this->getQueue();

        $id = $queue->delay(10)->push(new DummyJob());

        $this->assertEquals(Queue::STATUS_WAITING, $queue->status($id));

        $queue->remove($id);

        $this->assertEquals(Queue::STATUS_DONE, $queue->status($id));
    }

    public function testRemoveReserved()
    {
        $queue = $this->construct(Queue::class, [],
        [
            'handleMessage' => function ($id) use (&$queue) {
                $queue->remove($id);
                $this->assertEquals(Queue::STATUS_DONE, $queue->status($id));
                return new Promise(function ($fulfull) {
                    call_user_func($fulfull, new ExecEvent());
                });
            }
        ]);

        $id = $queue->push(new DummyJob());
        $this->assertEquals(Queue::STATUS_WAITING, $queue->status($id));

       $queue->run(false);

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
        $queue = $this->getQueue();
        $id = $queue->push(new DummyJob());

        $queue = $this->construct(Queue::class, [$this->queueConfig],
        [
            'handleMessage' => function ($id) use (&$queue) {
                // $this->assertEquals(Queue::STATUS_RESERVED, $queue->status($id));
                return new Promise(function ($fulfull) {
                    call_user_func($fulfull, new ExecEvent());
                });
            }
        ]);

        $queue->run(false);
    }

    public function testPushWithFileMode()
    {
        $queue = $this->getQueue();
        $queue->fileMode = 0664;

        $id = $queue->push(new DummyJob());

        $this->assertEquals('664', decoct(fileperms($queue->path . '/job' . $id . '.data') & 0777));
    }
}