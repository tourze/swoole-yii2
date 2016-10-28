<?php

namespace tourze\swoole\yii2\async;
use tourze\swoole\yii2\Application;

/**
 * 使用swoole来实现的异步文件操作
 *
 * @package tourze\swoole\yii2\async
 */
class FileHelper
{

    /**
     * 异步写文件
     *
     * @param string $filename
     * @param string $content
     * @param int    $offset 默认为-1, 意思是追加文件
     * @param callable $callback
     * @return bool
     */
    public static function write($filename, $content, $offset = -1, $callback = null)
    {
        //echo "Async write file: $filename\n";
        if (Application::$workerApp)
        {
            return swoole_async_write($filename, $content, $offset, $callback);
        }
        else
        {
            // 非CLI时的异步方法, 暂时不支持
            return false;
        }
    }

    /**
     * 异步读文件
     *
     * @param string $filename
     * @param callable $callback
     * @return bool
     */
    public static function read($filename, $callback)
    {
        // 文件不存在, 则直接执行?
        if ( ! file_exists($filename))
        {
            call_user_func_array($callback, [$filename, null]);
            return false;
        }
        if (Application::$workerApp)
        {
            //echo "Async read file: $filename\n";
            @swoole_async_readfile($filename, $callback);
            return true;
        }
        else
        {
            // 非CLI时的异步方法
            return false;
        }
    }
}
