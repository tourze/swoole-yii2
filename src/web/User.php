<?php

namespace tourze\swoole\yii2\web;

use tourze\swoole\yii2\Application;
use Yii;

class User extends \yii\web\User
{

    /**
     * @inheritdoc
     */
    protected function renewAuthStatus()
    {
        if (Application::$workerApp)
        {
            // swoole中不会自动触发open, 所以手动open
            Yii::$app->session->open();
        }
        parent::renewAuthStatus();
    }
}
