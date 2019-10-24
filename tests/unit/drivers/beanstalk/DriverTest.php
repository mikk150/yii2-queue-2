<?php

namespace tests\unit\drivers\beanstalk;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Pheanstalk\Exception\ServerException;
use Pheanstalk\Job;
use Pheanstalk\PheanstalkInterface;
use React\Promise\Promise;
use stdClass;
use tests\app\DummyJob;
use tests\unit\drivers\TestCase;
use yii\queue\beanstalk\Queue;
use yii\queue\ExecEvent;

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

    /**
     * @expectedException Pheanstalk\Exception\ServerException
     * @expectedExceptionMessage Custom exteption
     */
    public function testRemoveThrowsError()
    {
        $pheanstalk = $this->makeEmpty(PheanstalkInterface::class, [
            'delete' => function () {
                throw new ServerException('Custom exteption');
            }
        ]);

        $queue = $this->construct($this->queueClass, [$this->queueConfig], [
            'getPheanstalk' => $pheanstalk
        ]);

        $queue->remove(10);
    }

    public function testReserve()
    {
        $pheanstalk = $this->makeEmpty(PheanstalkInterface::class, [
            'reserveFromTube' => Stub::consecutive(new Job(10, '10;data'), null),
            'statsJob' => Expected::once(function (Job $job) {
                $this->assertEquals(10, $job->getId());

                $object = new stdClass;
                $object->ttr = 10;
                $object->reserves = 1;

                return $object;
            }),
        ]);

        $queue = $this->construct($this->queueClass, [$this->queueConfig], [
            'getPheanstalk' => $pheanstalk
        ]);

        $queue->run(false);
    }

    public function testDelete()
    {
        $pheanstalk = $this->makeEmpty(PheanstalkInterface::class, [
            'reserveFromTube' => Stub::consecutive(new Job(10, 'test'), null),
            'statsJob' => Expected::once(function (Job $job) {
                $this->assertEquals(10, $job->getId());

                $object = new stdClass;
                $object->ttr = 2;
                $object->reserves = 1;

                return $object;
            }),
            'delete' => Expected::once(function (Job $job) {
                $this->assertEquals(10, $job->getId());
            }),
        ]);

        $queue = $this->construct($this->queueClass, [$this->queueConfig], [
            'getPheanstalk' => $pheanstalk,
            'handleMessage' => function ($id, $message, $ttr, $attempt) {
                return new Promise(function ($fulfill) {
                    call_user_func($fulfill, new ExecEvent());
                });
            }
        ]);

        $queue->run(false);
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

    public function testStatusOnDone()
    {
        $pheanstalk = $this->makeEmpty(PheanstalkInterface::class, [
            'statsJob' => Expected::once(function ($job) {
                $this->assertEquals(10, $job);

                throw new ServerException('Server reported NOT_FOUND');
            })
        ]);

        $queue = $this->construct($this->queueClass, [$this->queueConfig], [
            'getPheanstalk' => $pheanstalk,
        ]);

        $this->assertEquals(Queue::STATUS_DONE, $queue->status(10));
    }

    public function testStatusOnReserved()
    {
        $pheanstalk = $this->makeEmpty(PheanstalkInterface::class, [
            'statsJob' => Expected::once(function ($job) {
                $this->assertEquals(10, $job);
                return ['state' => 'reserved'];
            })
        ]);

        $queue = $this->construct($this->queueClass, [$this->queueConfig], [
            'getPheanstalk' => $pheanstalk,
        ]);

        $this->assertEquals(Queue::STATUS_RESERVED, $queue->status(10));
    }

    /**
     * @expectedException Pheanstalk\Exception\ServerException
     * @expectedExceptionMessage Custom exteption
     */
    public function testStatusThrowsUnknownErrors()
    {
        $pheanstalk = $this->makeEmpty(PheanstalkInterface::class, [
            'statsJob' => Expected::once(function ($job) {
                $this->assertEquals(10, $job);
                throw new ServerException('Custom exteption');
            })
        ]);

        $queue = $this->construct($this->queueClass, [$this->queueConfig], [
            'getPheanstalk' => $pheanstalk,
        ]);

        $this->assertEquals(Queue::STATUS_RESERVED, $queue->status(10));
    }
}
