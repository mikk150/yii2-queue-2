<?php

namespace tests\unit\drivers\traits;

use React\Promise\Promise;
use tests\app\DummyJob;
use yii\queue\ExecEvent;

trait DelayTrait
{
    public function testDelay()
    {
        $messages = [];

        /**
         * @var \yii\queue\Queue $queue
         */
        $queue = $this->construct($this->queueClass, [$this->queueConfig], [
            'handleMessage' => function ($id, $message, $ttr, $attempt) use (&$messages, &$queue) {
                $messages[] = $queue->serializer->unserialize($message);
                return new Promise(function ($fulfill) {
                    call_user_func($fulfull, new ExecEvent());
                });
            }
        ]);

        $queue->delay(1)->push(new DummyJob());

        $queue->run(false);
        $this->assertEmpty($messages);

        sleep(2);

        $queue->run(false);
        $this->assertCount(1, $messages);
    }
}
