<?php

namespace tests\unit\cli;

use Codeception\Stub\Expected;
use tests\app\DummyJob;
use tests\ApplicationTestCase;
use tests\stubs\ArrayQueue;
use yii\base\BaseObject;
use yii\base\InlineAction;
use yii\queue\cli\AsyncCommand;
use yii\queue\cli\AsyncQueue;

class AsyncCommandTest extends ApplicationTestCase
{
    /**
     * @expectedException yii\base\InvalidArgumentException
     * @expectedExceptionMessage `queue` must be instance of yii\queue\cli\AsyncQueue
     */
    public function testInitWithNoQueue()
    {
        $this->construct(AsyncCommand::class, [$this->mockApplication(), 'test'] , [
            'isWorkerAction' => true
        ]);
    }

    /**
     * @expectedException yii\base\InvalidArgumentException
     * @expectedExceptionMessage `queue` must be instance of yii\queue\cli\AsyncQueue
     */
    public function testInitWithRandomObject()
    {
        $this->construct(AsyncCommand::class, [
            $this->mockApplication(),
            'test', [
                'queue' => new BaseObject()
            ]],
            [
                'isWorkerAction' => true
            ]
        );
    }

    public function testInit()
    {
        $this->construct(AsyncCommand::class, [
            $this->mockApplication(),
            'test', [
                'queue' => $this->make(AsyncQueue::class, [
                    'pushMessage' => true
                ])
            ]],
            [
                'isWorkerAction' => true
            ]
        );
    }

    public function testHandleMessage()
    {
        $queue = $this->construct(ArrayQueue::class, [], [
            'delete' => Expected::once()
        ]);

        $queue->push(new DummyJob());

        $command = $this->construct(AsyncCommand::class, [
            $this->mockApplication(),
            'test', [
                'queue' => $queue
            ]],
            [
                'isWorkerAction' => true,
                'getCommand' => function () {
                    return ['cat'];
                },
                'stdout' => Expected::once(function ($buffer) {
                    $this->assertEquals('O:18:"tests\app\DummyJob":0:{}', $buffer);
                })
            ]
        );

        $command->beforeAction(new InlineAction('test', $command, 'test'));

        $queue->run(false);
    }

    public function testHandleMessageThatOutputsError()
    {
        $queue = $this->construct(ArrayQueue::class, [], [
            'delete' => Expected::once()
        ]);

        $queue->push(new DummyJob());

        $command = $this->construct(AsyncCommand::class, [
            $this->mockApplication(),
            'test', [
                'queue' => $queue
            ]],
            [
                'isWorkerAction' => true,
                'getCommand' => function () {
                    return ['echo', 'test', '>&2'];
                },
                'stderr' => Expected::once(function ($buffer) {
                    $this->assertEquals('test' . PHP_EOL, $buffer);
                })
            ]
        );

        $command->beforeAction(new InlineAction('test', $command, 'test'));

        $queue->run(false);
    }

    public function testHandleMessageThatFails()
    {
        $queue = $this->construct(ArrayQueue::class, [], [
            'delete' => Expected::never(),
            'handleError' => Expected::once()
        ]);

        $queue->push(new DummyJob());

        $command = $this->construct(AsyncCommand::class, [
            $this->mockApplication(),
            'test', [
                'queue' => $queue
            ]],
            [
                'isWorkerAction' => true,
                'getCommand' => function () {
                    return ['false'];
                }
            ]
        );

        $command->beforeAction(new InlineAction('test', $command, 'test'));

        $queue->run(false);
    }

}
