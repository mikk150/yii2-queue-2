<?php

namespace tests\unit\drivers\db;

use React\Promise\Promise;
use tests\app\DummyJob;
use tests\unit\drivers\traits\DelayTrait;
use tests\unit\drivers\traits\PriorityTrait;
use yii\db\Query;
use yii\mutex\Mutex;
use yii\queue\db\Queue;
use yii\queue\ExecEvent;

abstract class TestCase extends \tests\unit\drivers\TestCase
{
    use PriorityTrait;
    use DelayTrait;

    public $queueClass = Queue::class;

    public $queueConfig = [
        'mutex' => [
            'class' => \yii\mutex\FileMutex::class
        ],
        'db' => false,
    ];

    public function testPushMessage()
    {
        $queue = $this->getQueue();

        $id = $queue->push(new DummyJob());

        $this->assertTrue((new Query)->where(['id' => $id])->from($queue->tableName)->exists($queue->db));
    }

    public function testRemove()
    {
        $queue = $this->getQueue();

        $id = $queue->push(new DummyJob());
        $queue->remove($id);

        $this->assertFalse((new Query)->where(['id' => $id])->from($queue->tableName)->exists($queue->db));
    }

    public function testClear()
    {
        $queue = $this->getQueue();

        for ($i=0; $i < 10; $i++) { 
            $queue->push(new DummyJob());
        }

        $this->assertEquals(10, (new Query)->from($queue->tableName)->count('*', $queue->db));

        $queue->clear();

        $this->assertEquals(0, (new Query)->from($queue->tableName)->count('*', $queue->db));
    }

    public function testDelete()
    {
        $queue = $this->getQueue();

        $id = $queue->push(new DummyJob());

        $queue->run(false);

        $this->assertFalse((new Query)->where(['id' => $id])->from($queue->tableName)->exists($queue->db));
    }

    public function testDeleteWhenDeleteReleasedFalse()
    {
        $queue = $this->getQueue();
        $queue->deleteReleased = false;

        $id = $queue->push(new DummyJob());

        $queue->run(false);

        $this->assertTrue((new Query)->where(['id' => $id])->from($queue->tableName)->exists($queue->db));

        $this->assertNotEmpty((new Query)->where(['id' => $id])->from($queue->tableName)->one($queue->db)['done_at']);
    }

    /**
     * @expectedException yii\base\Exception
     * @expectedExceptionMessage Has not waited the lock.
     */
    public function testLockNotWaited()
    {
        $queue = $this->getQueue();
        $queue->mutex = $this->make(Mutex::class, [
            'acquireLock' => function () {
                return false;
            }
        ]);

        $queue->run(false);
    }

    public function testStatusHasntStartedYet()
    {
        $queue = $this->getQueue();

        $id = $queue->push(new DummyJob());
        $this->assertEquals(Queue::STATUS_WAITING, $queue->status($id));
    }

    public function testStatusOfFinishedJob()
    {
        $queue = $this->getQueue();

        $this->assertEquals(Queue::STATUS_DONE, $queue->status(10));
    }

    /**
     * @expectedException yii\base\InvalidArgumentException
     * @expectedExceptionMessage Unknown message ID: 10.
     */
    public function testStatusOfNonExistingJob()
    {
        $queue = $this->getQueue();
        $queue->deleteReleased = false;
        $queue->status(10);
    }

    public function testStatusStartedButNotFinishedJob()
    {
        $id = null;
        $queue = $this->construct(Queue::class, [[
            'db' => $this->getQueue()->db,
            'mutex' => $this->getQueue()->mutex,
        ]],
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

    public function testStatusOnFinishedJobAndDeleteReservedFalse()
    {
        $queue = $this->construct(Queue::class, [[
            'db' => $this->getQueue()->db,
            'mutex' => $this->getQueue()->mutex,
            'deleteReleased' => false,
        ]],
        [
            'handleMessage' => function () {
                return new Promise(function ($fulfull) {
                    call_user_func($fulfull, new ExecEvent());
                });
            }
        ]);

        $id = $queue->push(new DummyJob());

        $queue->run(false);

        $this->assertEquals(Queue::STATUS_DONE, $queue->status($id));
    }
}
