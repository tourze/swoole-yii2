<?php

namespace tourze\swoole\yii2;

use swoole_http_request;
use swoole_http_response;
use swoole_http_server;
use tourze\swoole\yii2\web\ErrorHandler;
use tourze\swoole\yii2\web\Request;
use tourze\swoole\yii2\web\Response;
use tourze\swoole\yii2\web\Session;
use tourze\swoole\yii2\web\User;
use tourze\swoole\yii2\web\View;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Controller;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\Widget;

/**
 * @property swoole_http_request  serverRequest
 * @property swoole_http_response serverResponse
 * @property swoole_http_server   server
 * @property string rootPath
 */
class Application extends \yii\web\Application
{

    /**
     * @var array 全局配置信息
     */
    public static $_globalConfig = [];

    /**
     * 设置全局配置信息
     *
     * @param array $config
     */
    public static function setGlobalConfig($config)
    {
        static::$_globalConfig = $config;
    }

    /**
     * 获取全局配置信息
     *
     * @return array
     */
    public static function getGlobalConfig()
    {
        return static::$_globalConfig;
    }

    /**
     * @var static 当前进行中的$app实例, 存放的是一个通用的, 可以供复制的app实例
     */
    public static $workerApp = null;

    /**
     * @var swoole_http_server 当前运行中的swoole实例
     */
    protected $_server;

    /**
     * @return swoole_http_server
     */
    public function getServer()
    {
        return $this->_server;
    }

    /**
     * @param swoole_http_server $server
     */
    public function setServer($server)
    {
        $this->_server = $server;
    }

    /**
     * @var swoole_http_request 当前正在处理的swoole请求实例
     */
    protected $_serverRequest;

    /**
     * @return swoole_http_request
     */
    public function getServerRequest()
    {
        return $this->_serverRequest;
    }

    /**
     * @param swoole_http_request $serverRequest
     */
    public function setServerRequest($serverRequest)
    {
        $this->_serverRequest = $serverRequest;
    }

    /**
     * @var swoole_http_response 当前正在处理的swoole响应实例
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
    protected $_rootPath;

    /**
     * @return string
     */
    public function getRootPath()
    {
        return $this->_rootPath;
    }

    /**
     * @param string $rootPath
     */
    public function setRootPath($rootPath)
    {
        $this->_rootPath = $rootPath;
    }

    /**
     * @var array
     */
    public $bootstrapRefresh = [];

    /**
     * @var array 扩展缓存
     */
    public static $defaultExtensionCache = null;

    /**
     * 获取默认的扩展
     *
     * @return array|mixed
     */
    public function getDefaultExtensions()
    {
        if (static::$defaultExtensionCache === null)
        {
            $file = Yii::getAlias('@vendor/yiisoft/extensions.php');
            static::$defaultExtensionCache = is_file($file) ? include($file) : [];
        }
        return static::$defaultExtensionCache;
    }

    /**
     * @var bool
     */
    public static $webAliasInit = false;

    /**
     * 初始化流程
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function bootstrap()
    {
        if ( ! static::$webAliasInit)
        {
            $request = $this->getRequest();
            Yii::setAlias('@webroot', dirname($request->getScriptFile()));
            Yii::setAlias('@web', $request->getBaseUrl());
            static::$webAliasInit = true;
        }

        $this->extensionBootstrap();
        $this->moduleBootstrap();
    }

    /**
     * 自动加载扩展的初始化
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function extensionBootstrap()
    {
        if ( ! $this->extensions)
        {
            $this->extensions = $this->getDefaultExtensions();
        }
        foreach ($this->extensions as $k => $extension)
        {
            if ( ! empty($extension['alias']))
            {
                foreach ($extension['alias'] as $name => $path)
                {
                    Yii::setAlias($name, $path);
                }
            }
            if (isset($extension['bootstrap']))
            {
                $this->bootstrap[] = $extension['bootstrap'];
                Yii::trace('Push extension bootstrap to module bootstrap list', __METHOD__);
            }
        }
    }

    /**
     * 自动加载模块的初始化
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function moduleBootstrap()
    {
        foreach ($this->bootstrap as $k => $class)
        {
            $component = null;
            if (is_string($class))
            {
                if ($this->has($class))
                {
                    $component = $this->get($class);
                }
                elseif ($this->hasModule($class))
                {
                    $component = $this->getModule($class);
                }
                elseif (strpos($class, '\\') === false)
                {
                    throw new InvalidConfigException("Unknown bootstrapping component ID: $class");
                }
            }
            if ( ! isset($component))
            {
                $component = Yii::createObject($class);
            }

            if ($component instanceof BootstrapInterface)
            {
                Yii::trace('Bootstrap with ' . get_class($component) . '::bootstrap()', __METHOD__);
                $this->bootstrap[$k] = $component;
                $component->bootstrap($this);
                $this->bootstrap[$k] = $component;
            }
            else
            {
                Yii::trace('Bootstrap with ' . get_class($component), __METHOD__);
            }
        }
    }

    /**
     * @param $errorHandler
     * @throws \yii\base\InvalidConfigException
     */
    public function setErrorHandler($errorHandler)
    {
        $this->set('errorHandler', $errorHandler);
    }

    /**
     * 返回一个异常处理器
     *
     * @return ErrorHandler
     */
    public function getErrorHandler()
    {
        return parent::getErrorHandler();
    }

    /**
     * 复制一个request对象
     *
     * @param Request $request
     * @throws \yii\base\InvalidConfigException
     */
    public function setRequest($request)
    {
        $this->set('request', $request);
    }

    /**
     * 返回当前request对象
     *
     * @return Request
     */
    public function getRequest()
    {
        return parent::getRequest();
    }

    /**
     * 复制一个response对象
     *
     * @param Response $response
     * @throws \yii\base\InvalidConfigException
     */
    public function setResponse($response)
    {
        $this->set('response', $response);
    }

    /**
     * 返回当前response对象
     *
     * @return Response
     */
    public function getResponse()
    {
        return parent::getResponse();
    }

    /**
     * 复制一个view对象
     *
     * @param View|\yii\web\View $view
     * @throws \yii\base\InvalidConfigException
     */
    public function setView($view)
    {
        $this->set('view', $view);
    }

    /**
     * 返回当前view对象
     *
     * @return View
     */
    public function getView()
    {
        return parent::getView();
    }

    /**
     * 创建会话
     *
     * @param Session $session
     * @throws \yii\base\InvalidConfigException
     */
    public function setSession($session)
    {
        $this->set('session', $session);
    }

    /**
     * 返回当前session对象
     *
     * @return Session
     */
    public function getSession()
    {
        return parent::getSession();
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return parent::getUser();
    }

    /**
     * @param $user
     * @throws \yii\base\InvalidConfigException
     */
    public function setUser($user)
    {
        $this->set('user', $user);
    }

    /**
     * 预热一些可以浅复制的对象
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function prepare()
    {
        $this->getLog()->setLogger(Yii::getLogger());
        $this->getSecurity();
        $this->getUrlManager();
        $this->getRequest()->setBaseUrl('');
        $this->getRequest()->setScriptUrl('/index.php');
        $this->getRequest()->setScriptFile('/index.php');
        $this->getRequest()->setUrl(null);
        $this->getResponse();
        foreach ($this->getResponse()->formatters as $type => $class)
        {
            $this->getResponse()->formatters[$type] = Yii::createObject($class);
        }
        $this->getSession();
        $this->getAssetManager();
        $this->getView();
        $this->getDb();
        $this->getUser();
        $this->getMailer();
    }

    /**
     * run之前先准备上下文信息
     */
    public function beforeRun()
    {
        Event::offAll();
        // widget计数器等要清空
        Widget::$counter = 0;
        Widget::$stack = [];
        $this->getErrorHandler()->setServerResponse($this->getServerResponse());
        $this->getRequest()->setQueryParams(isset($this->getServerRequest()->get) ? $this->getServerRequest()->get : []);
        // 上面处理了$_GET部分, 但是没处理$_POST部分.
        $this->getRequest()->setHostInfo('http://' . $this->getServerRequest()->header['host']);
        $this->getRequest()->setPathInfo($this->getServerRequest()->server['path_info']);
        $this->getRequest()->setServerRequest($this->getServerRequest());
        $this->getResponse()->setServerResponse($this->getServerResponse());
        foreach ($this->bootstrap as $k => $component)
        {
            if ( ! is_object($component))
            {
                if ($this->has($component))
                {
                    $component = $this->get($component);
                }
                elseif ($this->hasModule($component))
                {
                    $component = $this->getModule($component);
                }
            }
            if (in_array(get_class($component), $this->bootstrapRefresh))
            {
                /** @var BootstrapInterface $component */
                $component->bootstrap($this);
            }
            elseif ($component instanceof Refreshable)
            {
                $component->refresh();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ( ! Application::$workerApp)
        {
            return parent::run();
        }
        $this->beforeRun();
        return parent::run();
    }

    /**
     * 阻止默认的exit执行
     *
     * @param int   $status
     * @param mixed $response
     * @return int|void
     */
    public function end($status = 0, $response = null)
    {
        if ( ! Application::$workerApp)
        {
            return parent::run();
        }
        if ($this->state === self::STATE_BEFORE_REQUEST || $this->state === self::STATE_HANDLING_REQUEST)
        {
            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);
        }

        if ($this->state !== self::STATE_SENDING_RESPONSE && $this->state !== self::STATE_END)
        {
            $this->state = self::STATE_END;
            $response = $response ? : $this->getResponse();
            $response->send();
        }
        return 0;
    }

    /**
     * 用于收尾
     * 这里因为用了swoole的task,所以性能很低
     */
    public function afterRun()
    {
        Yii::getLogger()->flush();
        $this->getSession()->close();
    }

    /**
     * @var array 保存 id => controller 的实例缓存
     */
    public static $controllerIdCache = [];

    /**
     * 保存控制器实例缓存, 减少一次创建请求的开销
     * 能提升些少性能.
     * 这里要求控制器在实现时, 业务逻辑尽量不要写在构造函数中
     *
     * @inheritdoc
     */
    public function createControllerByID($id)
    {
        if ( ! Application::$workerApp)
        {
            return parent::createControllerByID($id);
        }

        if ( ! isset(self::$controllerIdCache[$id]))
        {
            $controller = parent::createControllerByID($id);
            if ( ! $controller)
            {
                return $controller;
            }
            // 清空id和module的引用
            $controller->id = null;
            $controller->module = null;
            self::$controllerIdCache[$id] = clone $controller;
        }

        /** @var Controller $controller */
        $controller = clone self::$controllerIdCache[$id];
        $controller->id = $id;
        $controller->module = $this;
        $controller->init();
        return $controller;
    }
}
