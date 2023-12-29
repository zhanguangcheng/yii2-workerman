<?php

namespace app\controllers;

use yii\web\Controller;

class SiteController extends Controller
{
    public function actionIndex(): string
    {
        return $this->render('index');
    }
}
