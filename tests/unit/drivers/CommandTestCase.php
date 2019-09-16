<?php

namespace tests\unit\drivers;

use Codeception\Stub\Expected;

abstract class CommandTestCase extends \tests\ApplicationTestCase
{
    public $queueClass;
    public $commandClass;

    public function testListen()
    {
        $command = new $this->commandClass('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty($this->queueClass, [
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
        $command = new $this->commandClass('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty($this->queueClass, [
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
        $command = new $this->commandClass('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty($this->queueClass, [
                'run' => Expected::never()
            ])
        ]);

        $command->runAction('listen', [
            '-3'
        ]);
    }

    public function testActionRun()
    {
        $command = new $this->commandClass('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty($this->queueClass, [
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
            $this->commandClass,
            [
                'test',
                $this->mockApplication(),
                [
                    'queue' => $this->makeEmpty($this->queueClass, [
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
        $command = new $this->commandClass('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty($this->queueClass, [
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
        $command = new $this->commandClass('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty($this->queueClass, [
                'remove' => Expected::once(function ($jobId) {
                    $this->assertEquals('10', $jobId);
                    return false;
                })
            ])
        ]);

        $command->runAction('remove', ['10']);
    }
}
