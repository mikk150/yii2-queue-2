<?php

namespace tests\unit\cli;

use tests\ApplicationTestCase;
use tests\stubs\ArrayQueue;
use yii\base\InlineAction;
use yii\queue\cli\Command;
use yii\queue\cli\VerboseBehavior;

class CommandTest extends ApplicationTestCase
{
    public function testBeforeAction()
    {
        $app = $this->mockApplication();

        /**
         * @var Command $command
         */
        $command = $this->construct(Command::class, ['test', $app, [
            'queue' => new ArrayQueue()
        ]], [
            'isWorkerAction' => true
        ]);

        $this->assertTrue($command->beforeAction(new InlineAction('exec', $command, 'exec')));

        $this->isNull($command->getBehavior('verbose'));
    }

    public function testBeforeActionWithVerbose()
    {
        $app = $this->mockApplication();

        /**
         * @var Command $command
         */
        $command = $this->construct(Command::class, ['test', $app, [
            'queue' => new ArrayQueue()
        ]], [
            'isWorkerAction' => true,
            'verbose' => true
        ]);

        $this->assertTrue($command->beforeAction(new InlineAction('exec', $command, 'exec')));

        $this->isInstanceOf(VerboseBehavior::class, $command->getBehavior('verbose'));
    }
}