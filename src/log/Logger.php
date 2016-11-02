<?php

namespace tourze\swoole\yii2\log;

use tourze\swoole\yii2\Application;

class Logger extends \yii\log\Logger
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ( ! Application::$workerApp)
        {
            parent::init();
        }
    }

    /**
     * @inheritdoc
     */
    public function flush($final = false)
    {
        if ( ! Application::$workerApp)
        {
            parent::flush($final);
            return;
        }
        $messages = $this->messages;
        $this->messages = [];
        if ($this->dispatcher instanceof Dispatcher)
        {
            // \tourze\swoole\yii2\log\Dispatcher::dispatch
            $this->dispatcher->dispatch($messages, true);
        }
    }
}
