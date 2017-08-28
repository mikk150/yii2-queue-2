<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\messengers;

use tests\app\SimpleJob;
use yii\base\Object;
use yii\queue\messengers\Messenger;

/**
 * Class TestCase
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends \tests\TestCase
{
    /**
     * @return Messenger
     */
    abstract protected function createMessenger();

    /**
     *
     */
    public function testMessenger()
    {
        $messenger = $this->createMessenger();

        $messenger->push('awesomemessage');

        $actual = $messenger->pop();

        $this->assertEquals('awesomemessage', $actual);
    }

    /**
     *
     */
    public function testEmptyQueue()
    {
        $messenger = $this->createMessenger();

        $actual = $messenger->pop();

        $this->assertNull($actual);
    }
}
