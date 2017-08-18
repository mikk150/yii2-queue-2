<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue;

/**
 * Interface Job
 *
 * @author Mikk Tendermakk <mikk150@gmail.com>
 */
interface Job
{
    /**
     */
    public function execute();
}
