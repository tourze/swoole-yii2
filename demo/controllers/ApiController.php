<?php

namespace demo\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * API相关的测试控制器
 *
 * @package demo\controllers
 */
class ApiController extends Controller
{

    /**
     * 返回json
     *
     * @return array
     */
    public function actionJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['time' => time(), 'str' => 'hello'];
    }
}
