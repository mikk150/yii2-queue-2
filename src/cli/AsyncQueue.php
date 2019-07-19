<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface as ReactLoopInterface;
use yii\base\BootstrapInterface;

/**
 * Queue with CLI.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class AsyncQueue extends Queue implements BootstrapInterface
{
    /**
     * @var string command class name
     */
    public $commandClass = AsyncCommand::class;

    /**
     * @var ReactLoopInterface
     */
    private $_loop;

    /**
     * Gets react EventLoop, if not present, creates one
     *
     * @return ReactLoopInterface
     */
    public function getLoop()
    {
        if (!$this->_loop) {
            $this->_loop = Factory::create();
        }
        return $this->_loop;
    }

    /**
     * @inheritdoc
     */
    protected function handleMessage($id, $message, $ttr, $attempt)
    {
        if ($this->messageHandler) {
            return call_user_func($this->messageHandler, $id, $message, $ttr, $attempt);
        }

        return parent::handleMessage($id, $message, $ttr, $attempt);
    }

    protected function doWork(callable $canContinue, $repeat, $timeout)
    {
        if ($canContinue()) {
            if (($payload = $this->reserve()) !== null) {
                list($id, $message, $ttr, $attempt) = $payload;
                $this->handleMessage($id, $message, $ttr, $attempt)->then(
                    function () use ($payload) {
                        $this->delete($payload);
                    }
                );

                $this->getLoop()->futureTick(
                    function () use ($canContinue, $repeat, $timeout) {
                        $this->doWork($canContinue, $repeat, $timeout);
                    }
                );
                return ;
            }
            if ($repeat) {
                $this->getLoop()->addTimer(
                    $timeout,
                    function () use ($canContinue, $repeat, $timeout) {
                        $this->doWork($canContinue, $repeat, $timeout);
                    }
                );
            }
        }
    }
}
