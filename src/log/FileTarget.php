<?php

namespace tourze\swoole\yii2\log;

use tourze\swoole\yii2\Application;
use tourze\swoole\yii2\async\Task;
use yii\base\InvalidConfigException;

/**
 * Class FileTarget
 *
 * @package tourze\swoole\yii2\log
 */
class FileTarget extends \yii\log\FileTarget
{

    /**
     * 异步写日志到文件
     *
     * @param $text
     * @param $enableRotation
     * @param $logFile
     * @param $fileMode
     * @param $maxFileSize
     * @param $maxLogFiles
     * @param $rotateByCopy
     * @throws \yii\base\InvalidConfigException
     */
    public static function taskFlush($text, $enableRotation, $logFile, $fileMode, $maxFileSize, $maxLogFiles, $rotateByCopy)
    {
        //sleep(1);
        if (($fp = @fopen($logFile, 'a+')) === false)
        {
            throw new InvalidConfigException("Unable to append to log file: {$logFile}");
        }
        @flock($fp, LOCK_EX);
        if ($enableRotation)
        {
            // clear stat cache to ensure getting the real current file size and not a cached one
            // this may result in rotating twice when cached file size is used on subsequent calls
            clearstatcache();
        }
        if ($enableRotation && @filesize($logFile) > $maxFileSize * 1024)
        {
            for ($i = $maxLogFiles; $i >= 0; --$i)
            {
                // $i == 0 is the original log file
                $rotateFile = $logFile . ($i === 0 ? '' : '.' . $i);
                if (is_file($rotateFile))
                {
                    // suppress errors because it's possible multiple processes enter into this section
                    if ($i === $maxLogFiles)
                    {
                        @unlink($rotateFile);
                    }
                    else
                    {
                        if ($rotateByCopy)
                        {
                            @copy($rotateFile, $logFile . '.' . ($i + 1));
                            if ($fp = @fopen($rotateFile, 'a'))
                            {
                                @ftruncate($fp, 0);
                                @fclose($fp);
                            }
                            if ($fileMode !== null)
                            {
                                @chmod($logFile . '.' . ($i + 1), $fileMode);
                            }
                        }
                        else
                        {
                            @rename($rotateFile, $logFile . '.' . ($i + 1));
                        }
                    }
                }
            }
            @flock($fp, LOCK_UN);
            @fclose($fp);
            @file_put_contents($logFile, $text, FILE_APPEND | LOCK_EX);
        }
        else
        {
            @fwrite($fp, $text);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
        if ($fileMode !== null)
        {
            @chmod($logFile, $fileMode);
        }
    }

    /**
     * @inheritdoc
     */
    protected function getContextMessage()
    {
        if ( ! Application::$workerApp)
        {
            return parent::getContextMessage();
        }
        // 原来的上下文格式化函数, VarDumper太耗时了, 改成直接print_r, 虽然样式丢失不了, 但是效率提升不少
        $result = [];
        foreach ($this->logVars as $key)
        {
            if (isset($GLOBALS[$key]))
            {
                $result[] = "\${$key} = " . print_r($GLOBALS[$key], true);
            }
        }
        return implode("\n\n", $result);
    }

    /**
     * @inheritdoc
     */
    public function export()
    {
        if ( ! Application::$workerApp)
        {
            parent::export();
            return;
        }

        // 这里的$text合并, 最好也放到task中去实现, 但是因为在格式化的过程中, 需要用到当前的一些组件信息, 不好大改, 暂时这样先
        // TODO 优化array_map, 减少代码执行量
        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
        Task::addTask('\tourze\swoole\yii2\log\FileTarget::taskFlush', [
            'text' => $text,
            'enableRotation' => $this->enableRotation,
            'logFile' => $this->logFile,
            'fileMode' => $this->fileMode,
            'maxFileSize' => $this->maxFileSize,
            'maxLogFiles' => $this->maxLogFiles,
            'rotateByCopy' => $this->rotateByCopy,
        ]);
    }
}
