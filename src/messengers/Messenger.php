<?php

namespace yii\queue\messengers;

use yii\base\Object;
use yii\queue\Queue;

/**
 * @property-write Queue $queue
 */
abstract class Messenger extends Object
{
    private $_queue;

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


    /**
     * Pushes a message.
     *
     * @param      string  $message   The message
     * @param      int     $delay     The delay
     * @param      int  $priority  The priority
     *
     * @return     mixed
     */
    abstract public function push($message, $delay, $priority);

    /**
     * Pushes a message.
     *
     * @param      string  $message  The message
     * @param      int     $delay    The delay
     *
     * @return     mixed
     */
    abstract public function pop();

    /**
     * { function_description }
     *
     * @param      string  $message  The message
     * @param      string  $id       The identifier
     * @param      int     $ttr      The ttr
     * @param      int     $attempt  The attempt
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function handleMessage($id, $message, $ttr, $attempt)
    {
        return $this->getQueue()->handleMessage($id, $message, $ttr, $attempt);
    }
}
