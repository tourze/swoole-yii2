<?php

use tourze\swoole\yii2\server\HttpServer;

defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

/** @var HttpServer $server */
$server = new HttpServer;
$server->run([
    'host' => '127.0.0.1',
    'port' => '6677',
    'root' => __DIR__,
    'xhprofDebug' => false,
    // bootstrap文件, 只会引入一次
    'bootstrapFile' => [
        __DIR__ . '/config/aliases.php',
    ],
    // Yii的配置文件, 只会引入一次
    'configFile' => [
        __DIR__ . '/config/config.php',
    ],
    // 有一些模块比较特殊, 无法实现Refreshable接口, 此时唯有在这里指定他的类名
    'bootstrapRefresh' => [],
    // 配置参考 https://www.kancloud.cn/admins/swoole/201155
    'server' => [
        'worker_num' => 1,
        'max_request' => 10000,
        // 任务进程数
        'task_worker_num' => 1,
    ],
]);
