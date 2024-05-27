<?php

namespace app\controllers;

use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    public function actionIndex(): string
    {
        return $this->render('index');
    }

    public function actionJson(): Response
    {
        return $this->asJson(['message' => "Hello"]);
    }
}
