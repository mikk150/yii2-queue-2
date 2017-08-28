<?php

namespace yii\queue\messengers\arraymessenger;

use yii\base\NotSupportedException;

/**
* 
*/
class ArrayMessenger extends \yii\queue\messengers\Messenger
{
    private $_messages = [];

    public function pop()
    {
        return array_pop($this->_messages);
    }

    public function push($message, $delay = null)
    {
        if ($delay) {
            throw new NotSupportedException("Delay is not supported on ArrayMessenger");
        }

        $this->_messages[] = $message;
    }
}
