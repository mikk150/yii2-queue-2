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
use yii\console\Controller;
use yii\queue\cli\AsyncQueue;

/**
 * Base Command.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Command extends Controller
{
    /**
     * The exit code of the exec action which is returned when job was done.
     */
    const EXEC_DONE = 0;
    /**
     * The exit code of the exec action which is returned when job wasn't done and wanted next attempt.
     */
    const EXEC_RETRY = 3;

    /**
     * @var Queue
     */
    public $queue;
    /**
     * @var bool verbose mode of a job execute. If enabled, execute result of each job
     * will be printed.
     */
    public $verbose = false;
    /**
     * @var array additional options to the verbose behavior.
     * @since 2.0.2
     */
    public $verboseConfig = [
        'class' => VerboseBehavior::class,
    ];
    /**
     * @var bool isolate mode. It executes a job in a child process.
     */
    public $isolate = true;
    /**
     * @var string path to php interpreter that uses to run child processes.
     * If it is undefined, PHP_BINARY will be used.
     * @since 2.0.3
     */
    public $phpBinary;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        if ($this->canVerbose($actionID)) {
            $options[] = 'verbose';
        }
        if ($this->canIsolate($actionID)) {
            $options[] = 'isolate';
            $options[] = 'phpBinary';
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'v' => 'verbose',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!($this->queue instanceof AsyncQueue)) {
            throw new InvalidArgumentException('`queue` must be instance of ' . AsyncQueue::class);
        }
    }

    /**
     * @param string $actionID
     * @return bool
     * @since 2.0.2
     */
    abstract protected function isWorkerAction($actionID);

    /**
     * @param string $actionID
     * @return bool
     */
    protected function canVerbose($actionID)
    {
        return $actionID === 'exec' || $this->isWorkerAction($actionID);
    }

    /**
     * @param string $actionID
     * @return bool
     */
    protected function canIsolate($actionID)
    {
        return $this->isWorkerAction($actionID);
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($this->canVerbose($action->id) && $this->verbose) {
            $this->queue->attachBehavior('verbose', ['command' => $this] + $this->verboseConfig);
        }

        if ($this->canIsolate($action->id) && $this->isolate) {
            if ($this->phpBinary === null) {
                $this->phpBinary = PHP_BINARY;
            }
            $this->queue->messageHandler = function ($id, $message, $ttr, $attempt) {
                return $this->handleMessage($id, $message, $ttr, $attempt);
            };
        }

        return parent::beforeAction($action);
    }

    /**
     * Executes a job.
     * The command is internal, and used to isolate a job execution. Manual usage is not provided.
     *
     * @param string|null $id of a message
     * @param int $ttr time to reserve
     * @param int $attempt number
     * @param int $pid of a worker
     * @return int exit code
     * @internal It is used with isolate mode.
     */
    public function actionExec($id, $ttr, $attempt, $pid)
    {
        $promise = $this->queue->execute($id, file_get_contents('php://stdin'), $ttr, $attempt, $pid ?: null);
        $return = self::EXEC_RETRY;
        $promise->then(
            function () use (&$return) {
                $return = self::EXEC_DONE;
            }
        );
        return $return;
    }

    protected function getCommand($id, $message, $ttr, $attempt)
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

        return $cmd;
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
        $process = new Process(join(' ', $this->getCommand($id, $message, $ttr, $attempt)));

        return new Promise(
            function ($fulfill, $reject) use ($process, $message) {
                $process->start($this->queue->getLoop());
                $process->stdin->end($message);
                $process->stderr->on(
                    'data',
                    function ($buffer) {
                        $this->stderr($buffer);
                    }
                );
                $process->stdout->on(
                    'data',
                    function ($buffer) {
                        $this->stdout($buffer);
                    }
                );
                $process->on(
                    'exit',
                    function ($exitCode) use ($fulfill, $reject) {
                        if ($exitCode == self::EXEC_DONE) {
                            call_user_func($fulfill, $exitCode);
                            return ;
                        }
                        call_user_func($reject, $exitCode);
                    }
                );
            }
        );
    }
}
