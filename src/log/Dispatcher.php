<?php

namespace tourze\swoole\yii2\log;

use tourze\swoole\yii2\Application;
use tourze\swoole\yii2\web\ErrorHandler;

class Dispatcher extends \yii\log\Dispatcher
{

    /**
     * @inheritdoc
     */
    public function dispatch($messages, $final)
    {
        if ( ! Application::$workerApp)
        {
            parent::dispatch($messages, $final);
            return;
        }
        //return;
        foreach ($this->targets as $target)
        {
            //var_dump(get_class($target));
            if ($target->enabled)
            {
                try
                {
                    $target->collect($messages, $final);
                }
                catch (\Exception $e)
                {
                    // 日志记录器出错
                    $target->enabled = false;
                    echo 'Unable to send log via ' . get_class($target) . ': ' . ErrorHandler::convertExceptionToString($e) . "\n";
                }
            }
        }
    }
}
