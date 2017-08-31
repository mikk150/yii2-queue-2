<?php
$config = [
    'id' => 'yii2-queue-app',
    'basePath' => dirname(__DIR__),
    'vendorPath' => dirname(dirname(dirname(__DIR__))) . '/vendor',
    'bootstrap' => [
    ],
    'components' => [
        'mysql' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=mysql;dbname=mysql_database',
            'username' => 'root',
            'password' => 'mysql_strong_password',
            'charset' => 'utf8',
            'attributes' => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode = "STRICT_ALL_TABLES"',
            ],
        ],

        'pgsql' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'pgsql:host=postgres;dbname=yii2_queue_test',
            'username' => 'postgres',
            'password' => '',
            'charset' => 'utf8',
        ],

        'sqlite' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'sqlite:@runtime/yii2_queue_test.db',
        ],
    ],
];

return $config;