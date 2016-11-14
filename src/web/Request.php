<?php

namespace tourze\swoole\yii2\web;

use swoole_http_request;

/**
 * @property swoole_http_request serverRequest
 */
class Request extends \yii\web\Request
{

    /**
     * @var swoole_http_request
     */
    protected $_serverRequest;

    /**
     * @return mixed
     */
    public function getServerRequest()
    {
        return $this->_serverRequest;
    }

    /**
     * @param mixed $serverRequest
     */
    public function setServerRequest($serverRequest)
    {
        $this->_serverRequest = $serverRequest;
    }
}
