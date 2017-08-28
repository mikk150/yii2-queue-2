<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\serializers;

use tests\app\SimpleJob;
use yii\base\Object;
use yii\queue\serializers\Serializer;

/**
 * Class TestCase
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends \tests\TestCase
{
    /**
     * @return Serializer
     */
    abstract protected function createSerializer();

    /**
     * @dataProvider providerSerialize
     * @param mixed $expected
     */
    public function testSerialize($expected)
    {
        $serializer = $this->createSerializer();

        $serialized = $serializer->serialize($expected);
        $actual = $serializer->unserialize($serialized);

        $this->assertEquals($expected, $actual, "Payload: $serialized");
    }

    public function providerSerialize()
    {
        return [
            // Job object
            [
                new SimpleJob(['uid' => 123])
            ]
        ];
    }
}
