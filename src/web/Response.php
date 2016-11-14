<?php

namespace tourze\swoole\yii2\web;

use tourze\swoole\yii2\Application;
use tourze\swoole\yii2\web\formatter\JsonResponseFormatter;
use swoole_http_response;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * 内部实现response
 *
 * @property swoole_http_response serverResponse
 */
class Response extends \yii\web\Response
{

    /**
     * @var array
     */
    protected $_sentHeaders = [];

    /**
     * @return array
     */
    public function getSentHeaders()
    {
        return $this->_sentHeaders;
    }

    /**
     * @param array $sentHeaders
     */
    public function setSentHeaders($sentHeaders)
    {
        $this->_sentHeaders = $sentHeaders;
    }

    /**
     * @var swoole_http_response
     */
    protected $_serverResponse;

    /**
     * @return swoole_http_response
     */
    public function getServerResponse()
    {
        return $this->_serverResponse;
    }

    /**
     * @param swoole_http_response $serverResponse
     */
    public function setServerResponse($serverResponse)
    {
        $this->_serverResponse = $serverResponse;
    }

    /**
     * @inheritdoc
     */
    protected function defaultFormatters()
    {
        return ArrayHelper::merge(parent::defaultFormatters(), [
            self::FORMAT_JSON => JsonResponseFormatter::className(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function sendHeaders()
    {
        if ( ! Application::$workerApp)
        {
            parent::sendHeaders();
            return;
        }

        $this->_sentHeaders = [];
        $headers = $this->getHeaders();
        $response = $this->getServerResponse();
        if ( ! $response)
        {
            //xdebug_print_function_stack();
            throw new Exception("The swoole response is empty.");
        }
        if ($headers)
        {
            foreach ($headers as $name => $values)
            {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                if (count($values) == 1)
                {
                    $value = array_shift($values);
                    $this->_sentHeaders[$name] = $value;
                    $response->header($name, $value);
                }
                else
                {
                    $this->_sentHeaders[$name] = $values;
                    /** @var array $values */
                    foreach ($values as $value)
                    {
                        //echo "$name: $value\n";
                        $response->header($name, $value);
                    }
                }
            }
        }
        $this->getServerResponse()->status($this->getStatusCode());
        $this->sendCookies();
    }

    /**
     * Sends the cookies to the client.
     */
    protected function sendCookies()
    {
        if ( ! Application::$workerApp)
        {
            parent::sendCookies();
            return;
        }

        if ($this->getCookies() === null)
        {
            return;
        }
        $request = Yii::$app->getRequest();
        if ($request->enableCookieValidation)
        {
            if ($request->cookieValidationKey == '')
            {
                throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
            }
            $validationKey = $request->cookieValidationKey;
        }
        foreach ($this->getCookies() as $cookie)
        {
            $value = $cookie->value;
            if ($cookie->expire != 1 && isset($validationKey))
            {
                $value = Yii::$app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
            }
            $this->getServerResponse()->cookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }
    }

    /**
     * @inheritdoc
     */
    protected function sendContent()
    {
        if ( ! Application::$workerApp)
        {
            parent::sendContent();
            return;
        }
        if ($this->content === null)
        {
            $this->getServerResponse()->end();
        }
        else
        {
            $this->getServerResponse()->end($this->content);
        }
    }
}
