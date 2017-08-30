<?php

namespace yii\queue\executors\instant;

use Kraken\Promise\Promise;
use \Exception;

/**
 */
class Executor extends \yii\queue\executors\Executor
{
    /**
     * @inheritdoc
     */
    public function handleMessage($message, $id = null, $ttr = null, $attempt = null)
    {
        return new Promise(function ($resolve, $reject) {
            $job = $this->getQueue()->getSerializer()->unserialize();
            try {
                call_user_func($resolve, $job->execute());
            } catch (Exception $e) {
                call_user_func($reject, $e);
            }
        });
    }
}
