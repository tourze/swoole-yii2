<?php

namespace tourze\swoole\yii2\log;

use tourze\swoole\yii2\Application;

class Logger extends \yii\log\Logger
{

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
        // https://github.com/yiisoft/yii2/issues/5619
        // new messages could be logged while the existing ones are being handled by targets
        $this->messages = [];
        if ($this->dispatcher instanceof Dispatcher)
        {
            // \tourze\swoole\yii2\log\Dispatcher::dispatch
            $this->dispatcher->dispatch($messages, true);
        }
    }
}
