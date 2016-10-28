<?php

namespace tourze\swoole\yii2\web;

use swoole_http_request;

/**
 * @property swoole_http_request swooleRequest
 */
class Request extends \yii\web\Request
{

    /**
     * @var swoole_http_request
     */
    protected $_swooleRequest;

    /**
     * @return mixed
     */
    public function getSwooleRequest()
    {
        return $this->_swooleRequest;
    }

    /**
     * @param mixed $swooleRequest
     */
    public function setSwooleRequest($swooleRequest)
    {
        $this->_swooleRequest = $swooleRequest;
    }
}
