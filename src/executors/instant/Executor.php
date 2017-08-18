<?php

namespace yii\queue\executors\instant;

use GuzzleHttp\Promise\Promise;
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
        /**
         * @var  \yii\queue\Job
         */
        return $promise = new Promise(function () use (&$promise, $message) {        
            $job = $this->getQueue()->getSerializer()->unserialize($message);
            try {
                $promise->resolve($job->execute());
            } catch (Exception $e) {
                $promise->reject($e);
            }
        });
    }
}
