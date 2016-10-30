<?php

namespace tourze\swoole\yii2;

class Container extends \yii\di\Container
{

    /**
     * @var array 类的别名
     */
    public static $classAlias = [
        'yii\web\Request' => 'tourze\swoole\yii2\web\Request',
        'yii\web\Response' => 'tourze\swoole\yii2\web\Response',
        'yii\web\Session' => 'tourze\swoole\yii2\web\Session',
        'yii\web\AssetManager' => 'tourze\swoole\yii2\web\AssetManager',
        'yii\web\ErrorHandler' => 'tourze\swoole\yii2\web\ErrorHandler',
        'yii\web\User' => 'tourze\swoole\yii2\web\User',
        'yii\web\View' => 'tourze\swoole\yii2\web\View',
        'yii\log\Dispatcher' => 'tourze\swoole\yii2\log\Dispatcher',
        'yii\log\FileTarget' => 'tourze\swoole\yii2\log\FileTarget',
        'yii\db\Connection' => 'tourze\swoole\yii2\db\Connection',
        'yii\swiftmailer\Mailer' => 'tourze\swoole\yii2\mailer\SwiftMailer',
        'yii\debug\Module' => 'tourze\swoole\yii2\debug\Module',
        'yii\debug\panels\ConfigPanel' => 'tourze\swoole\yii2\debug\ConfigPanel',
        'yii\debug\panels\RequestPanel' => 'tourze\swoole\yii2\debug\RequestPanel',
    ];

    /**
     * @inheritdoc
     */
    protected function build($class, $params, $config)
    {
        if (isset(self::$classAlias[$class]))
        {
            $class = self::$classAlias[$class];
            //echo "alias: $class\n";
        }
        else
        {
            //echo "build: $class\n";
        }
        return parent::build($class, $params, $config);
    }
}
