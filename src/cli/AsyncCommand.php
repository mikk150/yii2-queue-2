<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use React\ChildProcess\Process;
use React\Promise\Promise;
use yii\base\InvalidArgumentException;

/**
 * Base Command.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class AsyncCommand extends Command
{
    public function init()
    {
        parent::init();

        if (!($this->queue instanceof AsyncQueue)) {
            throw new InvalidArgumentException('`queue` must be instance of ' . AsyncQueue::class);
        }
    }

    /**
     * Handles message using child process.
     *
     * @param string|null $id of a message
     * @param string $message
     * @param int $ttr time to reserve
     * @param int $attempt number
     * @return Promise
     * @throws
     * @see actionExec()
     */
    protected function handleMessage($id, $message, $ttr, $attempt)
    {
        // Child process command: php yii queue/exec "id" "ttr" "attempt" "pid"
        $cmd = [
            $this->phpBinary,
            $_SERVER['SCRIPT_FILENAME'],
            $this->uniqueId . '/exec',
            $id,
            $ttr,
            $attempt,
            $this->queue->getWorkerPid() ?: 0,
        ];

        foreach ($this->getPassedOptions() as $name) {
            if (in_array($name, $this->options('exec'), true)) {
                $cmd[] = '--' . $name . '=' . $this->$name;
            }
        }
        if (!in_array('color', $this->getPassedOptions(), true)) {
            $cmd[] = '--color=' . $this->isColorEnabled();
        }

        $process = new Process(join(' ', $cmd));

        return new Promise(
            function ($fulfill, $reject) use ($process, $message) {
                $process->start($this->queue->getLoop());
                $process->stdin->end($message);

                $process->on(
                    'exit',
                    function ($exitCode) use ($fulfill, $reject) {
                        if ($exitCode == self::EXEC_DONE) {
                            call_user_func($fulfill, $exitCode);
                            return ;
                        }
                        $reject($exitCode);
                    }
                );
            },
            function () use (&$process) {
                $process->terminate();
            }
        );
    }
}
