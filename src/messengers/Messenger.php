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
     * Reserves a message.
     *
     * @return     Message
     */
    abstract public function reserve();

    /**
     * Release a message.
     *
     * @return     mixed
     */
    abstract public function release($payload);

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

    public function listen()
    {
        $this->queue->getEventLoop()->addPeriodicTimer(0.1, function () {
            if ($payload = $this->pop()) {
                $this->handleMessage(
                    $payload['id'],
                    $payload['message'],
                    $payload['ttr'],
                    $payload['attempt']
                );
            }
        });
    }
}
