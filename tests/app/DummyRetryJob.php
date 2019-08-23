<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\app;

use yii\base\BaseObject;
use yii\base\Exception;
use yii\queue\RetryableJobInterface;

/**
 * Dummy Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DummyRetryJob extends BaseObject implements RetryableJobInterface
{
    public function execute($queue)
    {
        usleep(rand(1000,1000000));
        throw new Exception('Planned error.');
    }

    public function getTtr()
    {
        return 2;
    }

    public function canRetry($attempt, $error)
    {
        return $attempt < 2;
    }
}
