<?php

namespace tourze\swoole\yii2\web;

use swoole_http_response;
use tourze\swoole\yii2\Application;
use Yii;
use yii\base\ExitException;
use yii\base\UserException;
use yii\web\HttpException;

/**
 * @property swoole_http_response serverResponse
 */
class ErrorHandler extends \yii\web\ErrorHandler
{

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
    protected function renderException($exception)
    {
        if ( ! Application::$workerApp)
        {
            parent::renderException($exception);
            return;
        }

        if ( ! $this->getServerResponse())
        {
            echo (string) $exception;
            echo "\n";
            return;
        }
        $response = new Response;
        $response->setServerResponse($this->getServerResponse());

        $useErrorView = $response->format === Response::FORMAT_HTML && ( ! YII_DEBUG || $exception instanceof UserException);

        if ($useErrorView && $this->errorAction !== null)
        {
            $result = Yii::$app->runAction($this->errorAction);
            if ($result instanceof Response)
            {
                $response = $result;
            }
            else
            {
                $response->data = $result;
            }
        }
        elseif ($response->format === Response::FORMAT_HTML)
        {
            if (YII_ENV_TEST || isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
            {
                // AJAX request
                $response->data = '<pre>' . $this->htmlEncode(static::convertExceptionToString($exception)) . '</pre>';
            }
            else
            {
                // if there is an error during error rendering it's useful to
                // display PHP error in debug mode instead of a blank screen
                if (YII_DEBUG)
                {
                    ini_set('display_errors', 1);
                }
                $file = $useErrorView ? $this->errorView : $this->exceptionView;
                $response->data = $this->renderFile($file, [
                    'exception' => $exception,
                ]);
            }
        }
        elseif ($response->format === Response::FORMAT_RAW)
        {
            $response->data = static::convertExceptionToString($exception);
        }
        else
        {
            $response->data = $this->convertExceptionToArray($exception);
        }

        if ($exception instanceof HttpException)
        {
            $response->setStatusCode($exception->statusCode);
        }
        else
        {
            $response->setStatusCode(500);
        }

        $response->send();
    }

    /**
     * @inheritdoc
     */
    public function handleException($exception)
    {
        if ( ! $this->getServerResponse())
        {
            parent::handleException($exception);
            return;
        }
        if ($exception instanceof ExitException)
        {
            $this->getServerResponse()->end('');
            return;
        }

        $this->exception = $exception;
        $this->getServerResponse()->status(500);

        try
        {
            $this->logException($exception);
            if ($this->discardExistingOutput)
            {
                $this->clearOutput();
            }
            $this->renderException($exception);
        }
        catch (\Exception $e)
        {
            // an other exception could be thrown while displaying the exception
            $msg = "An Error occurred while handling another error:\n";
            $msg .= (string) $e;
            $msg .= "\nPrevious exception:\n";
            $msg .= (string) $exception;
            if (YII_DEBUG)
            {
                $html = '<pre>' . htmlspecialchars($msg, ENT_QUOTES, Yii::$app->charset) . '</pre>';
            }
            else
            {
                $html = 'An internal server error occurred.';
            }
            $this->getServerResponse()->header('Content-Type', 'text/html; charset=utf-8');
            $this->getServerResponse()->end($html);
        }

        $this->exception = null;
    }
}
