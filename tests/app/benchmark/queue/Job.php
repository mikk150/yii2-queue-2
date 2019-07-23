<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\app\benchmark\queue;

use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * The job calculates waiting time.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Job extends BaseObject implements JobInterface
{
    public $payload;

    public $lockFileName;

    public function execute($queue)
    {
        if (file_exists($this->lockFileName)) {
            // Emulation of job execution
            usleep(rand(100000, 300000));
            // Signals to the benchmark that job is done
            unlink($this->lockFileName);
        }
    }
}
