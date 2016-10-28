<?php

namespace tourze\swoole\yii2\web\formatter;

use tourze\swoole\yii2\Application;

class JsonResponseFormatter extends \yii\web\JsonResponseFormatter
{

    /**
     * 不使用JSON助手类库
     *
     * @inheritdoc
     */
    protected function formatJson($response)
    {
        if ( ! Application::$workerApp)
        {
            parent::formatJson($response);
            return;
        }
        $response->getHeaders()->set('Content-Type', 'application/json; charset=UTF-8');
        if ($response->data !== null)
        {
            $options = $this->encodeOptions;
            if ($this->prettyPrint)
            {
                $options |= JSON_PRETTY_PRINT;
            }
            $response->content = json_encode($response->data, $options);
        }
    }
}
