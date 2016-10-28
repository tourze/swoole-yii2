<?php

namespace tourze\swoole\yii2\debug;

use tourze\swoole\yii2\Application;
use tourze\swoole\yii2\async\FileHelper;

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

        $path = $this->module->dataPath;
        \yii\helpers\FileHelper::createDirectory($path, $this->module->dirMode);

        $summary = $this->collectSummary();
        $dataFile = "$path/{$this->tag}.data";
        $data = [];
        foreach ($this->module->panels as $id => $panel)
        {
            $data[$id] = $panel->save();
        }
        $data['summary'] = $summary;

        // 异步写文件
        FileHelper::write($dataFile, serialize($data), 0, function () use ($dataFile, $path, $summary) {
            //echo 'async write file: '.__METHOD__ . "\n";
            if ($this->module->fileMode !== null)
            {
                @chmod($dataFile, $this->module->fileMode);
            }
            $indexFile = "$path/index.data";
            $this->updateIndexFile($indexFile, $summary);
        });
    }

    /**
     * Updates index file with summary log data
     *
     * @param string $indexFile path to index file
     * @param array $summary summary log data
     * @throws \yii\base\InvalidConfigException
     */
    private function updateIndexFile($indexFile, $summary)
    {
        if ( ! file_exists($indexFile))
        {
            //echo __METHOD__ . " create index file \n";
            file_put_contents($indexFile, 'a:0:{}');
        }
        FileHelper::read($indexFile, function ($filename, $content) use ($indexFile, $summary) {
            //echo __METHOD__ . " read file ok.\n";
            if (empty($content))
            {
                // error while reading index data, ignore and create new
                $manifest = [];
            }
            else
            {
                // 因为有莫名其妙的错误, 麻烦, 直接屏蔽错误了..
                $manifest = (array) @unserialize($content);
            }
            $manifest[$this->tag] = $summary;
            // 下面的gc, 在并发大的情况下, 有点问题
            $this->gc($manifest);
            $manifest = serialize($manifest);
            // 序列化问题: http://stackoverflow.com/questions/10152904/unserialize-function-unserialize-error-at-offset
            $manifest = preg_replace_callback('!s:(\d+):"(.*?)";!', function($match) {
                return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
            }, $manifest);
            FileHelper::write($indexFile, $manifest, 0, function () use ($indexFile) {
                if ($this->module->fileMode !== null)
                {
                    @chmod($indexFile, $this->module->fileMode);
                }
            });
        });
    }
}
