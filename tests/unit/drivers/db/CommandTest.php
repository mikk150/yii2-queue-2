<?php

namespace tests\unit\drivers\db;

use Codeception\Stub\Expected;
use tests\ApplicationTestCase;
use yii\queue\db\Command;
use yii\queue\db\InfoAction;
use yii\queue\db\Queue;

class CommandTest extends ApplicationTestCase
{
    public function testActions()
    {
        $command = new Command('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty(Queue::class)
        ]);

        $this->assertInstanceOf(InfoAction::class, $command->createAction('info'));
    }

    public function testListen()
    {
        $command = new Command('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty(Queue::class, [
                'run' => Expected::once(function ($listen, $timeout) {
                    $this->assertTrue($listen);
                    $this->assertEquals(3, $timeout);
                })
            ])
        ]);

        $command->runAction('listen');
    }

    /**
     * @expectedException yii\console\Exception
     * @expectedExceptionMessage Timeout must be numeric.
     */
    public function testListenTimeoutNotInt()
    {
        $command = new Command('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty(Queue::class, [
                'run' => Expected::never()
            ])
        ]);

        $command->runAction('listen', [
            'test'
        ]);
    }

    /**
     * @expectedException yii\console\Exception
     * @expectedExceptionMessage Timeout must be greater than zero.
     */
    public function testListenTimeoutGreaterThanZero()
    {
        $command = new Command('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty(Queue::class, [
                'run' => Expected::never()
            ])
        ]);

        $command->runAction('listen', [
            '-3'
        ]);
    }

    public function testActionRun()
    {
        $command = new Command('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty(Queue::class, [
                'run' => Expected::once(function ($listen) {
                    $this->assertFalse($listen);
                })
            ])
        ]);

        $command->runAction('run');
    }

    public function testActionClear()
    {
        $command = $this->construct(
            Command::class,
            [
                'test',
                $this->mockApplication(),
                [
                    'queue' => $this->makeEmpty(Queue::class, [
                        'clear' => Expected::once()
                    ])
                ],
            ],
            [
                'confirm' => Expected::once(function ($message) {
                    $this->assertEquals('Are you sure?', $message);
                    return true;
                })
            ]
        );

        $command->runAction('clear');
    }

    public function testActionRemove()
    {
        $command = new Command('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty(Queue::class, [
                'remove' => Expected::once(function ($jobId) {
                    $this->assertEquals('10', $jobId);
                    return true;
                })
            ])
        ]);

        $command->runAction('remove', ['10']);
    }

    /**
     * @expectedException yii\console\Exception
     * @expectedExceptionMessage The job is not found.
     */
    public function testActionRemoveJobNotFound()
    {
        $command = new Command('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty(Queue::class, [
                'remove' => Expected::once(function ($jobId) {
                    $this->assertEquals('10', $jobId);
                    return false;
                })
            ])
        ]);

        $command->runAction('remove', ['10']);
    }
}