<?php

namespace tests\unit\drivers\amqp;

use Codeception\Stub\Expected;
use Codeception\Util\Stub;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use React\Promise\Promise;
use tests\app\DummyJob;
use tests\unit\drivers\TestCase;
use Yii;
use yii\queue\amqp\AmQpReserver;
use yii\queue\amqp\Queue;
use yii\queue\ExecEvent;

class DriverTest extends TestCase
{

    public $queueClass = Queue::class;

    public function _before()
    {
        $this->queueConfig = [
            'host' => getenv('RABBITMQ_HOST') ?: 'localhost',
            'user' => getenv('RABBITMQ_USER') ?: 'guest',
            'password' => getenv('RABBITMQ_PASSWORD') ?: 'guest',
            'queueName' => 'queue-basic',
            'exchangeName' => 'exchange-basic',
        ];

        $queue = $this->getQueue();
        $queue->clear();
        $queue->close();

        parent::_before();
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

    /**
     * @expectedException yii\base\NotSupportedException
     * @expectedExceptionMessage Delayed work is not supported in the driver.
     */
    public function testPushMessageWithDelay()
    {
        $queue = $this->getQueue();

        $queue->delay(10)->push(new DummyJob());
    }

    /**
     * @expectedException yii\base\NotSupportedException
     * @expectedExceptionMessage Status is not supported in the driver.
     */
    public function testStatus()
    {
        $queue = $this->getQueue();

        $queue->status(10);
    }

    public function testPushMessage()
    {
        $queue = $this->construct($this->queueClass, [$this->queueConfig], [
            'channel' => $this->make(AMQPChannel::class, [
                'basic_publish' => Expected::once()
            ])
        ]);

        $jobId = $queue->push(new DummyJob());
    }

    public function testRun()
    {
        $queue = $this->construct($this->queueClass, [$this->queueConfig], [
            'getReserver' => $this->makeEmpty(AmQpReserver::class, [
                'reserve' => Stub::consecutive([1, 'message', 110, 1, $this->make(AMQPMessage::class, [
                    'delivery_info' => [
                        'channel' => $this->make(AMQPChannel::class, [
                            'basic_ack' => Expected::once(function ($tag) {
                                $this->assertEquals('tag', $tag);
                            })
                        ]),
                        'delivery_tag' => 'tag'
                    ],
                ])], null)
            ]),
            'handleMessage' => function ($fetchedId) {
                $this->assertEquals(1, $fetchedId);
                return new Promise(function ($fulfull) {
                    call_user_func($fulfull, new ExecEvent());
                });
            },
        ]);

        $queue->run(false);
    }
}
