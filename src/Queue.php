<?php

namespace yii\queue;

use yii\di\Instance;
use React\EventLoop\Factory;
use yii\queue\messengers\Messenger;
use yii\queue\executors\Executor;
use yii\queue\serializers\Serializer;

/**
* 
*/
class Queue extends \yii\base\Component
{
    private $_serializer;
    
    private $_messenger;

    private $_executor;

    private $_eventLoop;

    /**
     * Sets the messenger.
     *
     * @param      array|string|Messenger  $messenger  The messenger
     */
    public function setMessenger($messenger)
    {
        $this->_messenger = Instance::ensure($messenger, Messenger::className());
        $this->_messenger->setQueue($this);
    }

    /**
     * Sets the executor.
     *
     * @param      array|string|Executor  $executor  The executor
     */
    public function setExecutor($executor)
    {
        $this->_executor = Instance::ensure($executor, Executor::className());
        $this->_executor->setQueue($this);
    }

    /**
     * Sets the executor.
     *
     * @param      array|string|Serializer  $serializer  The serializer
     */
    public function setSerializer($serializer)
    {
        $this->_serializer = Instance::ensure($serializer, Serializer::class);
    }

    /**
     * Gets the serializer.
     *
     * @return     Serializer  The serializer.
     */
    public function getSerializer()
    {
        return $this->_serializer;
    }

    /**
     * Gets the executor.
     *
     * @return     Executor  The executor.
     */
    protected function getExecutor()
    {
        return $this->_executor;
    }

    /**
     * Gets the messenger.
     *
     * @return     Messenger  The messenger.
     */
    public function getMessenger()
    {
        return $this->_messenger;
    }

    /**
     * Gets the event loop.
     *
     * @return     \React\EventLoop\LoopInterface  The event loop.
     */
    public function getEventLoop()
    {
        if (!$this->_eventLoop) {
            $this->_eventLoop = Factory::create();
        }
        return $this->_eventLoop;
    }

    /**
     * { function_description }
     *
     * @param      string  $message  The message
     * @param      string  $id       The identifier
     * @param      int     $ttr      The ttr
     * @param      int     $attempt  The attempt
     *
     * @return     string
     */
    public function handleMessage($message, $id = null, $ttr = null, $attempt = null)
    {
        return $this->getExecutor()->handleMessage($message, $id, $ttr, $attempt);
    }
}
