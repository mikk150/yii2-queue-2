<?php

namespace tests\unit\drivers\redis;

use tests\unit\drivers\CommandTestCase;
use yii\queue\redis\Command;
use yii\queue\redis\InfoAction;
use yii\queue\redis\Queue;

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
}