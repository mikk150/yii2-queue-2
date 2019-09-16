<?php

namespace tests\unit\drivers\db;


class SqliteDriverTest extends TestCase
{
    public $db = 'sqlite';

    public $queueConfig = [
        'mutex' => [
            'class' => \yii\mutex\FileMutex::class
        ],
        'db' => 'sqlite',
    ];
}
