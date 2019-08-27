<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\app\benchmark\queue;

use Symfony\Component\Process\Process;
use Yii;
use yii\helpers\Console;
use yii\queue\Queue;

/**
 * Benchmark of queue clear time.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Action extends \yii\base\Action
{
    /**
     * @var array
     */
    public $modes = [
        // Worker will be run in fast mode
        'fast' => [
            'gearmanQueue'     => 'gearman-queue/run      --isolate=0',
            'beanstalkQueue'   => 'beanstalk-queue/run    --isolate=0',
            'redisQueue'       => 'redis-queue/run        --isolate=0',
            'amqpQueue'        => 'amqp-queue/run         --isolate=0',
            // 'amqpInteropQueue' => 'amqp-interop-queue/run --isolate=0',
            'mysqlQueue'       => 'mysql-queue/run        --isolate=0',
            'fileQueue'        => 'file-queue/run         --isolate=0',
            // 'stompQueue'       => 'stomp-queue/run        --isolate=0',
        ],
        // Worker will be run in isolate mode
        'isolate' => [
            'gearmanQueue'     => 'gearman-queue/run      --isolate=1',
            'beanstalkQueue'   => 'beanstalk-queue/run    --isolate=1',
            'redisQueue'       => 'redis-queue/run        --isolate=1',
            'amqpQueue'        => 'amqp-queue/run         --isolate=1',
            // 'amqpInteropQueue' => 'amqp-interop-queue/run --isolate=1',
            'mysqlQueue'       => 'mysql-queue/run        --isolate=1',
            'fileQueue'        => 'file-queue/run         --isolate=1',
            // 'stompQueue'       => 'stomp-queue/run        --isolate=1',
        ],
    ];

    /**
     * @var Process[]
     */
    private $_workers = [];

    /**
     * Runs benchmark of how fast is queue being cleared.
     *
     * @param string $mode one of 'fast' or 'isolate'
     * @param int $jobCount number of jobs that will be pushed to a queue
     * @param int $workerCount number of workers that listen a queue
     * @param int $payloadSize additional job size
     * @throws
     */
    public function run($mode = 'fast', $jobCount = 1000, $workerCount = 1, $payloadSize = 0)
    {
        if (!isset($this->modes[$mode])) {
            throw new ConsoleException("Unknown mode: $mode.");
        }
        if ($jobCount <= 0) {
            throw new ConsoleException("Job count must be greater than zero.");
        }
        if ($workerCount <= 0) {
            throw new ConsoleException("Worker count must be greater than zero.");
        }

        foreach ($this->modes[$mode] as $queueName => $workerCommand) {
            /** @var Queue $queue */
            $queue = Yii::$app->get($queueName);
            try {
                Console::startProgress(0, $jobCount, str_pad("Pushing jobs $queueName: ", 22));
                $jobs = [];
                for ($i = 0; $i <= $jobCount; $i++) {
                    $jobs[] = $job = new Job();
                    $lockName = uniqid($queueName);
                    $job->lockFileName = Yii::getAlias("@runtime/$lockName.lock");
                    $job->payload = str_repeat('a', $payloadSize);
                    touch($job->lockFileName);
                    $queue->push($job);

                    Console::updateProgress($i, $jobCount);
                }

                // Starts worker
                $stdoutFileName = Yii::getAlias("@runtime/$queueName-out.log");
                file_put_contents($stdoutFileName, '');
                $this->startWorkers($workerCommand, $workerCount, function ($type, $buffer) use ($stdoutFileName) {
                    file_put_contents($stdoutFileName, $buffer, FILE_APPEND | LOCK_EX);
                });
                Console::endProgress();
                Console::startProgress(0, $jobCount, str_pad("- $queueName: ", 22));
                do {
                    usleep(100);
                    $jobs = array_filter($jobs, function (Job $job) {
                        return file_exists($job->lockFileName);
                    });
                    Console::updateProgress($jobCount - count($jobs), $jobCount);
                } while (count($jobs));
                Console::endProgress();
            } finally {
                $this->stopWorkers();
            }
        }
    }

    /**
     * Starts few workers.
     *
     * @param string $command
     * @param int $count
     * @param callable $callback
     */
    private function startWorkers($command, $count, callable $callback)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->_workers[] = $worker = new Process("exec php tests/yii $command");
            $worker->start($callback);
        }
    }

    /**
     * Stops started workers.
     */
    private function stopWorkers()
    {
        foreach ($this->_workers as $worker) {
            /** @var Process $worker */
            $worker->stop();
        }
        $this->_workers = [];
    }
}
