<?php

namespace yii\queue\messengers\arraymessenger;

use yii\base\NotSupportedException;
use yii\queue\Message;

/**
* 
*/
class ArrayMessenger extends \yii\queue\messengers\Messenger
{
    private $_messages = [];

    public function reserve()
    {
        $message = null;
        if ($payload = array_pop($this->_messages)) {
            $message = new Message([
                'message' => $payload
            ]);
        }
        return $message;
    }

    public function release($message)
    {
        return true;
    }

    public function push($message, $delay = null, $priority = null)
    {
        if ($delay) {
            throw new NotSupportedException("Delay is not supported on ArrayMessenger");
        }

        if ($priority) {
            throw new NotSupportedException("Priority is not supported on ArrayMessenger");
        }

        $this->_messages[] = $message;
    }
}
