<?php

namespace demo\controllers;

use Yii;
use yii\web\Controller;

class SiteController extends Controller
{

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionError()
    {
        return (string) Yii::$app->errorHandler->exception;
    }
}
