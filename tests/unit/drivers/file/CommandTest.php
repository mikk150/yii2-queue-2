<?php

namespace tests\unit\drivers\file;

use tests\unit\drivers\CommandTestCase;
use yii\queue\file\Command;
use yii\queue\file\InfoAction;
use yii\queue\file\Queue;

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