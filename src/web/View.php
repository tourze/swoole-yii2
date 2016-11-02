<?php

namespace tourze\swoole\yii2\web;

class View extends \yii\web\View
{

    /**
     * @var array
     */
    public static $phpCodeCache = [];

    /**
     * 缓存文件+eval执行
     *
     * @inheritdoc
     */
    public function renderPhpFile($_file_, $_params_ = [])
    {
        ob_start();
        ob_implicit_flush(false);
        extract($_params_, EXTR_OVERWRITE);

        if ( ! isset(self::$phpCodeCache[$_file_]))
        {
            self::$phpCodeCache[$_file_] = '?>' .file_get_contents($_file_);
        }
        //require($_file_);
        eval(self::$phpCodeCache[$_file_]);

        return ob_get_clean();
    }
}
