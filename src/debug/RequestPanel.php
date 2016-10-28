<?php

namespace tourze\swoole\yii2\debug;

use tourze\swoole\yii2\Application;
use tourze\swoole\yii2\web\Response;
use Yii;

/**
 * Class RequestPanel
 *
 * @package tourze\swoole\yii2\debug
 */
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

        /** @var Response $response */
        $response = Yii::$app->getResponse();
        // 在cli下, 使用headers_list获取的数据为空或者不准确, 所以只能从response对象中获取
        $headers = $response->getSentHeaders();
        $rs['responseHeaders'] = array_merge($rs['responseHeaders'], $headers);
        return $rs;
    }
}
