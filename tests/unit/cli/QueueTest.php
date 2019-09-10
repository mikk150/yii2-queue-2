<?php

namespace tests\unit\cli;

use Codeception\Stub\Expected;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use tests\app\DummyJob;
use tests\ApplicationTestCase;
use tests\stubs\ArrayQueue;
use tests\TestCase;
use yii\base\Exception;
use yii\queue\cli\LoadWatcher;
use yii\queue\cli\WorkerEvent;
use yii\queue\ExecEvent;

class QueueTest extends ApplicationTestCase
{
    public function testLoop()
    {
        $queue = new ArrayQueue();

        $this->assertInstanceOf(LoopInterface::class, $queue->getLoop());
    }

    public function testDoWork()
    {
        $queue = $this->construct(ArrayQueue::class, [], [
            'getLoop' => $this->makeEmpty(LoopInterface::class, [
                'futureTick' => Expected::once()
            ]),
            'handleMessage' => function () {
                return new Promise(function ($success) {
                    call_user_func($success, new ExecEvent());
                });
            }
        ]);

        $queue->push(new DummyJob());

        $queue->run(false);
    }

    public function testDoWorkJobThrowsException()
    {
        $queue = $this->construct(ArrayQueue::class, [], [
            'getLoop' => $this->makeEmpty(LoopInterface::class, [
                'futureTick' => Expected::once()
            ]),
            'handleError' => Expected::once(),
            'handleMessage' => function () {
                return new Promise(function ($success, $reject) {
                    call_user_func($reject, new ExecEvent([
                        'error' => new Exception('Expected one')
                    ]));
                });
            }
        ]);

        $queue->push(new DummyJob());

        $queue->run(false);
    }

    public function testRepeatAddsTimerWhenNoJobsHasBeenReturned()
    {
        $queue = $this->construct(ArrayQueue::class, [], [
            'getLoop' => $this->makeEmpty(LoopInterface::class, [
                'addTimer' => Expected::once()
            ]),
        ]);

        $queue->run(true);
    }

    public function testNoJobIsReservedWhenLoadIsHigh()
    {
        $queue = $this->construct(ArrayQueue::class, [], [
            'getLoop' => $this->makeEmpty(LoopInterface::class, [
                'addTimer' => Expected::once()
            ]),
            'reserve' => Expected::never(),
            'loadWatcher' => $this->make(LoadWatcher::class, [
                'shouldGetJob' => false
            ])
        ]);

        $queue->run(true);
    }

    public function testBootstrap()
    {
        $queue = new ArrayQueue();

        $application = $this->mockApplication([
            'components' => [
                'queue' => $queue
            ],
            'bootstrap' => ['queue']
        ]);

        $this->assertArrayHasKey('queue', $application->controllerMap);
    }

    /**
     * @expectedException yii\base\InvalidConfigException
     * @expectedExceptionMessage Queue must be an application component.
     */
    public function testBootstrapWhenNotOnApplication()
    {
        $queue = new ArrayQueue();

        $application = $this->mockApplication();

        $queue->bootstrap($application);
    }

    public function testRunWorkerEventFails()
    {
        $queue = new ArrayQueue([
            'on ' . ArrayQueue::EVENT_WORKER_START => function (WorkerEvent $event) {
                $event->exitCode = 22;
            }
        ]);

        $this->assertEquals(22, $queue->run(false));
    }

    public function testExecute()
    {
        $queue = new ArrayQueue();

        $promise = $queue->execute('1', $queue->serializer->serialize(new DummyJob()), 1, 1, 1);

        $this->assertInstanceOf(PromiseInterface::class, $promise);

        $recievedEvent = null;
        $promise->then(function (ExecEvent $event) use (&$recievedEvent) {
            $recievedEvent = $event;
        });
        $this->assertNull($recievedEvent->error);
    }

    public function testHandleMessage()
    {
        $queue = $this->construct(ArrayQueue::class, [], [
            'getLoop' => $this->makeEmpty(LoopInterface::class, [
                'futureTick' => Expected::once()
            ]),
        ]);

        $queue->push(new DummyJob());

        $queue->run(false);
    }
}