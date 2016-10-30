<?php

namespace tourze\swoole\yii2\server;

use swoole_http_request;
use swoole_http_response;
use swoole_http_server;
use swoole_server;
use tourze\swoole\yii2\Application;
use tourze\swoole\yii2\async\Task;
use tourze\swoole\yii2\Container;
use tourze\swoole\yii2\log\Logger;
use Yii;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class HttpServer extends Server
{

    /**
     * @var array 当前配置文件
     */
    public $config = [];

    /**
     * @var string 缺省文件名
     */
    public $indexFile = 'index.php';

    /**
     * @var bool 是否开启xhprof调试
     */
    public $xhprofDebug = false;

    /**
     * @var bool
     */
    public $debug = false;

    /**
     * @var string
     */
    public $root;

    /**
     * @var swoole_http_server
     */
    public $server;

    /**
     * @var string
     */
    public $sessionKey = 'JSESSIONID';

    /**
     * @inheritdoc
     */
    public function run($app)
    {
        $this->config = (array) Yii::$app->params['swooleHttp'][$app];
        if (isset($this->config['xhprofDebug']))
        {
            $this->xhprofDebug = $this->config['xhprofDebug'];
        }
        if (isset($this->config['debug']))
        {
            $this->debug = $this->config['debug'];
        }
        $this->root = $this->config['root'];
        $this->server = new swoole_http_server($this->config['host'], $this->config['port']);

        $this->server->on('start', [$this, 'onServerStart']);
        $this->server->on('shutdown', [$this, 'onServerStop']);

        $this->server->on('managerStart', [$this, 'onManagerStart']);

        $this->server->on('workerStart', [$this, 'onWorkerStart']);
        $this->server->on('workerStop', [$this, 'onWorkerStop']);

        $this->server->on('request', [$this, 'onRequest']);

        if (method_exists($this, 'onOpen'))
        {
            $this->server->on('open', [$this, 'onOpen']);
        }
        if (method_exists($this, 'onClose'))
        {
            $this->server->on('close', [$this, 'onClose']);
        }

        if (method_exists($this, 'onWsHandshake'))
        {
            $this->server->on('handshake', [$this, 'onWsHandshake']);
        }
        if (method_exists($this, 'onWsMessage'))
        {
            $this->server->on('message', [$this, 'onWsMessage']);
        }

        if (method_exists($this, 'onTask'))
        {
            $this->server->on('task', [$this, 'onTask']);
        }
        if (method_exists($this, 'onFinish'))
        {
            $this->server->on('finish', [$this, 'onFinish']);
        }

        $this->server->set($this->config['server']);
        $this->server->start();
    }

    /**
     * Worker启动时触发
     *
     * @param swoole_http_server $serv
     * @param $worker_id
     */
    public function onWorkerStart($serv , $worker_id)
    {
        // 初始化一些变量, 下面这些变量在进入真实流程时是无效的
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_URI'] = $_SERVER['SCRIPT_NAME'] = '';

        $this->setProcessTitle($this->name . ': worker');
        // 关闭Yii2自己实现的异常错误
        defined('YII_ENABLE_ERROR_HANDLER') || define('YII_ENABLE_ERROR_HANDLER', false);
        // 每个worker都创建一个独立的app实例

        // 加载文件和一些初始化配置
        if (isset($this->config['bootstrapFile']))
        {
            foreach ($this->config['bootstrapFile'] as $file)
            {
                require $file;
            }
        }
        $config = [];
        foreach ($this->config['configFile'] as $file)
        {
            $config = ArrayHelper::merge($config, include $file);
        }

        // 为Yii分配一个新的DI容器
        Yii::$container = new Container();

        if ( ! isset($config['components']['assetManager']['basePath']))
        {
            $config['components']['assetManager']['basePath'] = $this->root . '/assets';
        }
        $config['aliases']['@webroot'] = $this->root;
        $config['aliases']['@web'] = '/';
        if (isset($this->config['bootstrapMulti']))
        {
            $config['bootstrapMulti'] = $this->config['bootstrapMulti'];
        }
        $this->app = Yii::$app = Application::$workerApp = new Application($config);
        Yii::setLogger(new Logger());
        $this->app->setRootPath($this->root);
        $this->app->setSwooleServer($this->server);
        $this->app->prepare();
    }

    public function onWorkerStop()
    {
    }

    /**
     * 处理异步任务
     *
     * @param swoole_server $serv
     * @param mixed $task_id
     * @param mixed $from_id
     * @param string $data
     */
    public function onTask($serv, $task_id, $from_id, $data)
    {
        //echo "New AsyncTask[id=$task_id]".PHP_EOL;
        //$serv->finish("$data -> OK");
        Task::runTask($data, $task_id);
    }

    /**
     * 处理异步任务的结果
     *
     * @param swoole_server $serv
     * @param mixed $task_id
     * @param string $data
     */
    public function onFinish($serv, $task_id, $data)
    {
        //echo "AsyncTask[$task_id] Finish: $data".PHP_EOL;
    }

    public static $test = 1;

    /**
     * 执行请求
     *
     * @param swoole_http_request $request
     * @param swoole_http_response $response
     */
    public function onRequest($request, $response)
    {
        // 测试DI Container性能
//        $j = 100000;
//        $s1 = microtime(true);
//        for ($i=0; $i<$j; $i++)
//        {
//            $obj = Yii::createObject('yii\web\Request');
//        }
//        $t1 = microtime(true) - $s1;
//        // 更换新的Container
//        $s2 = microtime(true);
//        Yii::$container = new Container();
//        for ($i=0; $i<$j; $i++)
//        {
//            $obj = Yii::createObject('yii\web\Request');
//        }
//        $t2 = microtime(true) - $s2;
//        $response->end(json_encode(['t1' => $t1, 't2' => $t2]));
//        return;

        //$id = posix_getpid();
        //echo "id: $id\n";
//        $t = '<pre>';
//        $t .= print_r($_SERVER, true);
//        $t .= print_r($request, true);
//        $t .= '</pre>';
//        $response->end($t);
//        return;

        //xdebug_start_trace();

        if ($this->xhprofDebug)
        {
            xhprof_enable(XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_CPU);
        }

        $uri = $request->server['request_uri'];
        $file = $this->root . $uri;
        if ($uri != '/' && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) != 'php')
        {
            // 非php文件, 最好使用nginx来输出
            $response->header('Content-Type', FileHelper::getMimeTypeByExtension($file));
            $response->header('Content-Length', filesize($file));
            $response->end(file_get_contents($file));
        }
        else
        {
            // 准备环境信息
            // 只要进入PHP的处理流程, 都默认转发给Yii来做处理
            // 这样意味着, web目录下的PHP文件, 不会直接执行
            $file = $this->root . '/' . $this->indexFile;
            //echo $file . "\n";

            // 备份当前的环境变量
            $backupServerInfo = $_SERVER;

            foreach ($request->header as $k => $v)
            {
                $k = 'HTTP_' . strtoupper(str_replace('-', '_', $k));
                $_SERVER[$k] = $v;
            }
            foreach ($request->server as $k => $v)
            {
                $k = strtoupper(str_replace('-', '_', $k));
                $_SERVER[$k] = $v;
            }
            $_GET = [];
            if (isset($request->get))
            {
                $_GET = $request->get;
            }
            $_POST = [];
            if (isset($request->post))
            {
                $_POST = $request->post;
            }
            $_COOKIE = [];
            if (isset($request->cookie))
            {
                $_COOKIE = $request->cookie;
            }

            $_SERVER['SERVER_ADDR'] = '127.0.0.1';
            $_SERVER['SERVER_NAME'] = 'localhost';
            $_SERVER['SCRIPT_FILENAME'] = $file;
            $_SERVER['DOCUMENT_ROOT'] = $this->root;
            $_SERVER['DOCUMENT_URI'] = $_SERVER['SCRIPT_NAME'] = '/' . $this->indexFile;

            // 使用clone, 原型模式
            // 所有请求都clone一个原生$app对象
            $app = clone $this->app;
            $app->setSwooleRequest($request);
            $app->setSwooleResponse($response);
            $app->setErrorHandler(clone $this->app->getErrorHandler());
            $app->setRequest(clone $this->app->getRequest());
            $app->setResponse(clone $this->app->getResponse());
            $app->setView(clone $this->app->getView());
            $app->setSession(clone $this->app->getSession());
            $app->setUser(clone $this->app->getUser());
            Yii::$app = $app;

            try
            {
                //$t = '<pre>';
                //$t .= print_r($_SERVER, true);
                //$t .= print_r($request, true);
                //$t .= '</pre>';
                //$response->end($t);
                //return;

                $app->run();
                $app->afterRun();
            }
            catch (ErrorException $e)
            {
                $app->afterRun();
                if ($this->debug)
                {
                    echo (string) $e;
                    echo "\n";
                    $response->end('');
                }
                else
                {
                    $app->getErrorHandler()->handleException($e);
                }
            }
            catch (\Exception $e)
            {
                $app->afterRun();
                if ($this->debug)
                {
                    echo (string) $e;
                    echo "\n";
                    $response->end('');
                }
                else
                {
                    $app->getErrorHandler()->handleException($e);
                }
            }
            // 还原环境变量
            Yii::$app = $this->app;
            unset($app);
            $_SERVER = $backupServerInfo;
        }

        //xdebug_stop_trace();
        //xdebug_print_function_stack();

        if ($this->xhprofDebug)
        {
            $xhprofData = xhprof_disable();
            $xhprofRuns = new \XHProfRuns_Default();
            $runId = $xhprofRuns->save_run($xhprofData, 'xhprof_test');
            echo "http://127.0.0.1/xhprof/xhprof_html/index.php?run=" . $runId . '&source=xhprof_test'."\n";
        }
    }
}
