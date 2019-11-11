<?php

namespace tests\unit\drivers\gearman;

use tests\unit\drivers\CommandTestCase;
use yii\queue\gearman\Command;
use yii\queue\gearman\Queue;

class CommandTest extends CommandTestCase
{
    public $queueClass = Queue::class;
    public $commandClass = Command::class;

    public function testActionRemove()
    {

    }

    public function testActionClear()
    {
        
    }

    public function testActionRemoveJobNotFound()
    {

    }
}
