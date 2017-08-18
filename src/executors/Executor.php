<?php

namespace yii\queue\executors;

use yii\base\Object;
use yii\queue\Queue;

/**
* 
*/
abstract class Executor extends Object
{
    private $_queue;

    /**
     * { function_description }
     *
     * @param      string  $id       The identifier
     * @param      string  $message  The message
     * @param      int  $ttr      The ttr
     * @param      int  $attempt  The attempt
     *
     * @return     \GuzzleHttp\Promise\Promise
     */
    abstract public function handleMessage($message, $id = null, $ttr = null, $attempt = null);

    /**
     * Sets the queue.
     *
     * @param      \yii\queue\Queue  $queue  The queue
     */
    public function setQueue(Queue $queue)
    {
        $this->_queue = $queue;
    }

    /**
     * @return     Queue  The queue.
     */
    protected function getQueue()
    {
        return $this->_queue;
    }
}
