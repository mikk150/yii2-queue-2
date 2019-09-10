<?php

namespace tests\unit\cli;

use Codeception\Stub\Expected;
use tests\ApplicationTestCase;
use tests\stubs\ArrayQueue;
use yii\base\Exception;
use yii\console\Controller;
use yii\queue\cli\VerboseBehavior;
use yii\queue\cli\WorkerEvent;
use yii\queue\ExecEvent;

class VerboseBehaviorTest extends ApplicationTestCase
{
    public function testEvents()
    {
        $behavior = new VerboseBehavior();

        foreach ($behavior->events() as $eventKey => $eventFunction) {
            $this->assertTrue(method_exists($behavior, $eventFunction));
        }
    }

    public function testBeforeExec()
    {
        $queue = new ArrayQueue([
            'as verbose' => [
                'class' => VerboseBehavior::class,
                'command' => $this->make(Controller::class, [
                    'stdout' => Expected::exactly(5)
                ])
            ]
        ]);


        $queue->trigger(ArrayQueue::EVENT_BEFORE_EXEC, new ExecEvent());
    }

    public function testAfterExec()
    {
        $queue = new ArrayQueue([
            'as verbose' => [
                'class' => VerboseBehavior::class,
                'command' => $this->make(Controller::class, [
                    'stdout' => Expected::exactly(6)
                ])
            ]
        ]);


        $queue->trigger(ArrayQueue::EVENT_AFTER_EXEC, new ExecEvent());
    }

    public function testAfterExecWhenJobHasStarted()
    {
        $queue = new ArrayQueue([
            'as verbose' => [
                'class' => VerboseBehavior::class,
                'command' => $this->make(Controller::class, [
                    'stdout' => Expected::exactly(5 + 6)
                ])
            ]
        ]);

        $queue->trigger(ArrayQueue::EVENT_BEFORE_EXEC, new ExecEvent());

        $queue->trigger(ArrayQueue::EVENT_AFTER_EXEC, new ExecEvent());
    }

    public function testAfterError()
    {
        $queue = new ArrayQueue([
            'as verbose' => [
                'class' => VerboseBehavior::class,
                'command' => $this->make(Controller::class, [
                    'stdout' => Expected::exactly(8)
                ])
            ]
        ]);


        $queue->trigger(ArrayQueue::EVENT_AFTER_ERROR, new ExecEvent([
            'error' => new Exception('Test error')
        ]));
    }

    public function testAfterErrorWhenJobHasStarted()
    {
        $queue = new ArrayQueue([
            'as verbose' => [
                'class' => VerboseBehavior::class,
                'command' => $this->make(Controller::class, [
                    'stdout' => Expected::exactly(5 + 9)
                ])
            ]
        ]);

        $queue->trigger(ArrayQueue::EVENT_BEFORE_EXEC, new ExecEvent());

        $queue->trigger(ArrayQueue::EVENT_AFTER_ERROR, new ExecEvent([
            'error' => new Exception('Test error')
        ]));
    }

    public function testWorkerStart()
    {
        $queue = new ArrayQueue([
            'as verbose' => [
                'class' => VerboseBehavior::class,
                'command' => $this->make(Controller::class, [
                    'stdout' => Expected::exactly(3)
                ])
            ]
        ]);

        $queue->trigger(ArrayQueue::EVENT_WORKER_START, new WorkerEvent());
    }

    public function testWorkerStop()
    {
        $queue = new ArrayQueue([
            'as verbose' => [
                'class' => VerboseBehavior::class,
                'command' => $this->make(Controller::class, [
                    'stdout' => Expected::exactly(4)
                ])
            ]
        ]);

        $queue->trigger(ArrayQueue::EVENT_WORKER_STOP, new WorkerEvent());
    }
}
