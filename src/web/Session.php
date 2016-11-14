<?php

namespace tourze\swoole\yii2\web;

use swoole_http_response;
use tourze\swoole\yii2\Application;
use Yii;
use yii\web\Cookie;

/**
 * Class Session
 *
 * @property string sessionKey
 * @property swoole_http_response serverResponse
 */
class Session extends \yii\redis\Session
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
     * @var string
     */
    protected $_sessionKey = 'JSESSIONID';

    /**
     * @return string
     */
    public function getSessionKey()
    {
        return $this->_sessionKey;
    }

    /**
     * @param string $sessionKey
     */
    public function setSessionKey($sessionKey)
    {
        $this->_sessionKey = $sessionKey;
    }

    /**
     * @var string
     */
    protected $_id;

    /**
     * 从cookie中取session id
     *
     * @return string
     */
    public function getId()
    {
        if ($this->_id)
        {
            return $this->_id;
        }
        $cookie = Yii::$app->getRequest()->getCookies()->get($this->sessionKey);
        if ($cookie)
        {
            return $cookie->value;
        }
        return null;
    }

    /**
     * @param string $value
     */
    public function setId($value)
    {
        $cookie = new Cookie([
            'name' => $this->sessionKey,
            'value' => $value
        ]);
        $this->_id = $value;
        Yii::$app->response->getCookies()->add($cookie);
    }

    /**
     * @var bool
     */
    protected $_isActive = false;

    /**
     * 判断当前是否使用了session
     */
    public function getIsActive()
    {
        if ( ! Application::$workerApp)
        {
            return parent::getIsActive();
        }
        return $this->_isActive;
    }

    /**
     * @param bool $isActive
     */
    public function setIsActive($isActive)
    {
        $this->_isActive = $isActive;
    }

    /**
     * 打开会话连接, 从redis中加载会话数据
     *
     * @inheritdoc
     */
    public function open()
    {
        if ( ! Application::$workerApp)
        {
            parent::open();
            return;
        }
        if ($this->getIsActive())
        {
            Yii::info('Session started', __METHOD__);
            //$this->updateFlashCounters();
            return;
        }
        $this->setIsActive(true);
        @session_start();
        if ( ! Yii::$app->getRequest()->cookies->has($this->sessionKey))
        {
            //echo __METHOD__ . " regenerateID \n";
            $this->regenerateID();
        }
        $id = $this->getId();
        //var_dump($id);
        if ( ! empty($id))
        {
            $data = $this->readSession($this->getId());
            $_SESSION = (array) json_decode($data, true);
        }
    }

    /**
     * 注销对象时, 自动关闭和保存session
     */
    public function __destruct()
    {
        //$this->close();
    }

    /**
     * 关闭连接时, 主动记录session到redis
     *
     * @inheritdoc
     */
    public function close()
    {
        if ( ! Application::$workerApp)
        {
            parent::close();
            return;
        }
        //echo "Session is saving.\n";
        // 如果当前会话激活了, 则写session到redis
        if ($this->getIsActive())
        {
            // 将session数据存放到redis咯
            //echo $this->getId()." Write session \n";
            $this->writeSession($this->getId(), json_encode($_SESSION));
            // 清空当前会话数据
            $_SESSION = [];
        }
        $this->setIsActive(false);
    }

    /**
     * 自定义生成会话ID
     *
     * @inheritdoc
     */
    public function regenerateID($deleteOldSession = false)
    {
        if ( ! Application::$workerApp)
        {
            parent::regenerateID($deleteOldSession);
            return;
        }
        if ($deleteOldSession)
        {
            $id = $this->getId();
            $this->destroySession($id);
        }
        $id = 'S' . md5(Yii::$app->security->generateRandomString());
        $this->setId($id);
    }

    /**
     * 判断当前会话是否使用了cookie来存放标识
     * 在swoole中, 暂时只支持cookie标识, 所以只会返回true
     *
     * @inheritdoc
     */
    public function getUseCookies()
    {
        if ( ! Application::$workerApp)
        {
            return parent::getUseCookies();
        }
        return true;
    }
}
