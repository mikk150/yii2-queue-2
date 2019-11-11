<?php

namespace tests\unit\drivers\traits;

use React\Promise\Promise;
use tests\app\PriorityJob;
use yii\queue\ExecEvent;

trait PriorityTrait
{
    public function testPriority()
    {
        $messages = [];

        /**
         * @var \yii\queue\Queue $queue
         */
        $queue = $this->construct($this->queueClass, [$this->queueConfig], [
            'handleMessage' => function ($id, $message, $ttr, $attempt) use (&$messages, &$queue) {
                $messages[] = $queue->serializer->unserialize($message);
                return new Promise(function ($fulfill) {
                    return new ExecEvent();
                });
            }
        ]);

        $queue->priority(2000)->push(new PriorityJob(['number' => 2000]));
        $queue->priority(100)->push(new PriorityJob(['number' => 100]));
        $queue->priority(1024)->push(new PriorityJob(['number' => 1024]));
        $queue->run(false);


        $this->assertEquals(100, $messages[0]->number);
        $this->assertEquals(1024, $messages[1]->number);
        $this->assertEquals(2000, $messages[2]->number);
    }
}
