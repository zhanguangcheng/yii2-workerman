<?php

namespace app\commands;

use yii\console\Controller;


class HelloController extends Controller
{
    public function actionIndex(string $message = 'hello world'): void
    {
        echo $message . "\n";
    }
}
