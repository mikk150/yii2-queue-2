<?php

namespace yii\queue;

/**
 */
class ListenAction extends \yii\base\Action
{
    public function run()
    {
        $this->controller->queue->messenger->listen();
    }
}
