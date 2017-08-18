<?php

namespace yii\queue;

use yii\queue\messengers\interfaces\ListenableInterface;
use yii\queue\actions\ListenAction;

/** */
class Controller extends \yii\console\Controller
{
    /**
     * @var Queue
     */
    private $_queue;

    public function setQueue($queue)
    {
        $this->_messenger = Instance::ensure($queue, Queue::className());
    }

    /**
     * Gets the queue.
     * 
     * @return Queue
     */
    public function getQueue()
    {
        return $this->_queue;
    }

    public function actions()
    {
        $actions = [];

        if ($this->getQueue()->getMessenger() instanceof ListenableInterface) {
            $actions['listen'] = ListenAction::className();
        }

        return $actions;
    }
}
