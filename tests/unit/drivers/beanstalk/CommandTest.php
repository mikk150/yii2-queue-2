<?php

namespace tests\unit\drivers\beanstalk;

use tests\unit\drivers\CommandTestCase;
use yii\queue\beanstalk\Command;
use yii\queue\beanstalk\InfoAction;
use yii\queue\beanstalk\Queue;

class CommandTest extends CommandTestCase
{
    public $queueClass = Queue::class;
    public $commandClass = Command::class;

    public function testActions()
    {
        $command = new $this->commandClass('test', $this->mockApplication(), [
            'queue' => $this->makeEmpty($this->queueClass)
        ]);

        $this->assertInstanceOf(InfoAction::class, $command->createAction('info'));
    }

    /**
     * 
     */
    public function testActionClear()
    {
        $this->markTestSkipped('Not supported');
    }
}