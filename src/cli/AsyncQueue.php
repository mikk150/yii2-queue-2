<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

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
}
