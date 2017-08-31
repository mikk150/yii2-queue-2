<?php

namespace tests\executors;

use tests\executors\jobs\SuccessJob;
use tests\executors\jobs\RejectJob;
use yii\queue\Queue;
use yii\queue\serializers\PhpSerializer;
use Exception;

/**
* 
*/
abstract class TestCase extends \tests\TestCase
{
    /**
     * Gets the executor.
     * 
     * @return    \yii\queue\executors\Executor 
     */
    abstract public function getExecutor();

    protected function getQueue()
    {
        return new Queue([
            'serializer' => new PhpSerializer
        ]);
    }

    public function testSuccessfulJob()
    {
        $executor = $this->getExecutor();

        $job = new SuccessJob([
            'return' => 'test'
        ]);

        $message = $this->getQueue()->getSerializer()->serialize($job);
        $executor->handleMessage($message)->then(function ($result) {
            $this->assertEquals('test', $result);
        });
    }

    public function testRejectedJob()
    {
        $executor = $this->getExecutor();

        $job = new RejectJob([
            'message' => 'test'
        ]);

        $message = $this->getQueue()->getSerializer()->serialize($job);

        $executor->handleMessage($message)->then(function ($result) {
        }, function ($e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals('test', $e->getMessage());
        });
    }
}
