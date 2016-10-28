<?php

namespace tourze\swoole\yii2\server;

use swoole_server;
use tourze\swoole\yii2\Application;
use yii\base\Component;

/**
 * 基础的server实现
 *
 * @package tourze\swoole\yii2\server
 */
abstract class Server extends Component
{

    /**
     * @var string 服务器名称
     */
    public $name = 'swoole-server';

    /**
     * @var string 进程文件路径
     */
    public $pidFile;

    /**
     * @var swoole_server
     */
    public $server;

    /**
     * @var Application
     */
    public $app;

    /**
     * 设置进程标题
     *
     * @param string $name
     */
    protected function setProcessTitle($name)
    {
        if (function_exists('swoole_set_process_name'))
        {
            @swoole_set_process_name($name . ': master');
        }
        else
        {
            @cli_set_process_title($name . ': master');
        }
    }

    /**
     * 运行服务器
     *
     * @param string $app
     */
    abstract public function run($app);

    /**
     * 投递任务
     *
     * @param mixed $data
     * @param int $dst_worker_id
     * @return bool
     */
    public function task($data, $dst_worker_id = -1)
    {
        return $this->server->task($data, $dst_worker_id);
    }

    /**
     * @param swoole_server $server
     */
    public function onServerStart($server)
    {
        $this->setProcessTitle($this->name . ': master');
        if ($this->pidFile)
        {
            file_put_contents($this->pidFile, $server->master_pid);
        }
    }

    public function onServerStop()
    {
        if ($this->pidFile)
        {
            unlink($this->pidFile);
        }
    }

    /**
     * @param swoole_server $server
     */
    public function onManagerStart($server)
    {
        $this->setProcessTitle($this->name . ': manager');
    }
}
