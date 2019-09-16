<?php

namespace tests\unit\drivers;

use React\Promise\Promise;
use tests\app\PriorityJob;
use yii\queue\ExecEvent;

abstract class PriorityTestCase extends TestCase
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
        
        $queue->priority(300)->push(new PriorityJob(['number' => 300]));
        $queue->priority(400)->push(new PriorityJob(['number' => 400]));
        $queue->priority(100)->push(new PriorityJob(['number' => 100]));
        $queue->priority(200)->push(new PriorityJob(['number' => 200]));

        $queue->run(false);

        $this->assertEquals(100, $messages[0]->number);
        $this->assertEquals(200, $messages[1]->number);
        $this->assertEquals(300, $messages[2]->number);
        $this->assertEquals(400, $messages[3]->number);
    }
}
