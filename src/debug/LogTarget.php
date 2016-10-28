<?php

namespace tourze\swoole\yii2\debug;

use tourze\swoole\yii2\Application;
use tourze\swoole\yii2\async\Task;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

/**
 * 调试模块的日志记录器
 *
 * @package tourze\swoole\yii2\debug
 */
class LogTarget extends \yii\debug\LogTarget
{

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

        FileHelper::createDirectory($this->module->dataPath, $this->module->dirMode);

        $summary = $this->collectSummary();
        $data = [];
        // 收集面板的调试信息
        foreach ($this->module->panels as $id => $panel)
        {
            $data[$id] = $panel->save();
        }
        $data['summary'] = $summary;

        //self::saveDebugData($this->tag, $this->module->dataPath, $data, $this->module->fileMode, $this->module->historySize, $summary);
        Task::addTask('\tourze\swoole\yii2\debug\LogTarget::saveDebugData', [$this->tag, $this->module->dataPath, $data, $this->module->fileMode, $this->module->historySize, $summary]);
    }

    /**
     * 将原有的export部分逻辑/ updateIndexFile / gc 合并在一起
     * 这个方法默认只应该由task去执行
     *
     * @param $tag
     * @param $dataPath
     * @param $data
     * @param $fileMode
     * @param $historySize
     * @param $summary
     * @throws \yii\base\InvalidConfigException
     */
    public static function saveDebugData($tag, $dataPath, $data, $fileMode, $historySize, $summary)
    {
        $dataFile = "$dataPath/{$tag}.data";
        file_put_contents($dataFile, serialize($data));
        if ($fileMode !== null)
        {
            @chmod($dataFile, $fileMode);
        }

        $indexFile = "$dataPath/index.data";
        touch($indexFile);
        if (($fp = @fopen($indexFile, 'r+')) === false)
        {
            throw new InvalidConfigException("Unable to open debug data index file: $indexFile");
        }
        @flock($fp, LOCK_EX);
        $manifest = '';
        while (($buffer = fgets($fp)) !== false)
        {
            $manifest .= $buffer;
        }
        if ( ! feof($fp) || empty($manifest))
        {
            // error while reading index data, ignore and create new
            $manifest = [];
        }
        else
        {
            $manifest = unserialize($manifest);
        }

        $manifest[$tag] = $summary;
        if (count($manifest) > $historySize + 10)
        {
            $n = count($manifest) - $historySize;
            foreach (array_keys($manifest) as $tag)
            {
                $file = $dataPath . "/$tag.data";
                @unlink($file);
                unset($manifest[$tag]);
                if (--$n <= 0)
                {
                    break;
                }
            }
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, serialize($manifest));

        @flock($fp, LOCK_UN);
        @fclose($fp);

        if ($fileMode !== null)
        {
            @chmod($indexFile, $fileMode);
        }
    }
}
