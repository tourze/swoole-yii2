<?php

namespace demo\controllers;

use demo\models\User;
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

    /**
     * 查找所有用户
     */
    public function actionGetUsers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $rs = [];

        /** @var User[] $users */
        $users = User::find()->all();
        foreach ($users as $user)
        {
            $rs[] = $user->toArray();
        }
        return $rs;
    }
}
