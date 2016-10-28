<?php

namespace tourze\swoole\yii2\debug;

use tourze\swoole\yii2\Application;
use Yii;
use yii\web\View;

class Module extends \yii\debug\Module
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->setViewPath('@yii/debug/views');
    }

    /**
     * 继承原有逻辑, 增加一个异步写日志的LogTarget
     *
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        $this->logTarget = Yii::$app->getLog()->targets['debug'] = new LogTarget($this);

        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app) {
            $app->getView()->on(View::EVENT_END_BODY, [$this, 'renderToolbar']);
        });

        $app->getUrlManager()->addRules([
            [
                'class' => 'yii\web\UrlRule',
                'route' => $this->id,
                'pattern' => $this->id,
            ],
            [
                'class' => 'yii\web\UrlRule',
                'route' => $this->id . '/<controller>/<action>',
                'pattern' => $this->id . '/<controller:[\w\-]+>/<action:[\w\-]+>',
            ]
        ], false);
    }

    /**
     * @inheritdoc
     */
    protected function corePanels()
    {
        return array_merge(parent::corePanels(), [
            'config' => ['class' => 'tourze\swoole\yii2\debug\ConfigPanel'],
            'request' => ['class' => 'tourze\swoole\yii2\debug\RequestPanel'],
        ]);
    }
}
