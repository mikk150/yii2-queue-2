<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\app;

use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Dummy Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DummyJob extends BaseObject implements JobInterface
{
    public function execute($queue)
    {
        usleep(rand(1000,1000000));
    }
}
