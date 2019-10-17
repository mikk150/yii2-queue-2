<?php

namespace tests\unit\drivers\amqp;

use tests\unit\drivers\CommandTestCase;
use yii\queue\amqp\Command;
use yii\queue\amqp\Queue;

class CommandTest extends CommandTestCase
{
    public $queueClass = Queue::class;
    public $commandClass = Command::class;

    public function testActionRemove()
    {
    }
    public function testActionRemoveJobNotFound()
    {
    }

}
