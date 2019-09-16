<?php

namespace tests\unit\drivers\db;

use tests\unit\drivers\CommandTestCase;
use yii\queue\db\Command;
use yii\queue\db\InfoAction;
use yii\queue\db\Queue;

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