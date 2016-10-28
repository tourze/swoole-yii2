<?php

namespace tourze\swoole\yii2\debug;

use tourze\swoole\yii2\Application;
use Yii;

class RequestPanel extends \yii\debug\panels\RequestPanel
{

    /**
     * @inheritdoc
     */
    public function save()
    {
        $rs = parent::save();
        if ( ! Application::$workerApp)
        {
            return $rs;
        }
        // swoole是跑在cli下的

        $headers = Yii::$app->response->getSentHeaders();
        //echo "merge response headers:".json_encode($headers)." \n";
        $rs['responseHeaders'] = array_merge($rs['responseHeaders'], $headers);
        return $rs;
    }
}
