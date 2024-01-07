<?php

namespace app;

class Installer
{
    public static function fix(): void
    {
        $replaces = [
            '/vendor/yiisoft/yii2/base/Application.php' => [
                'exit($status)' => 'exit_exception($status)',
            ],
            '/vendor/yiisoft/yii2/base/ErrorHandler.php' => [
                'exit(1);' => 'exit_exception(1);',
                'register_shutdown_function(' => 'register_shutdown_function_user(',
            ],
            '/vendor/yiisoft/yii2/web/Request.php' => [
                "file_get_contents('php://input')" => 'request_raw_body()',
            ],
            '/vendor/yiisoft/yii2/log/Logger.php' => [
                "YII_BEGIN_TIME" => "\$_SERVER['REQUEST_TIME_FLOAT']",
                'register_shutdown_function(' => 'register_shutdown_function_user(',
            ],
            '/vendor/yiisoft/yii2/log/Target.php' => [
                "YII_BEGIN_TIME" => "\$_SERVER['REQUEST_TIME_FLOAT']",
            ],
            '/vendor/yiisoft/yii2/mutex/Mutex.php' => [
                'register_shutdown_function(' => 'register_shutdown_function_user(',
            ],
            '/vendor/yiisoft/yii2/web/Session.php' => [
                'register_shutdown_function(' => 'register_shutdown_function_user(',
            ],
        ];

        foreach ($replaces as $file => $item) {
            $file = __DIR__ . '/..' . $file;
            $text = file_get_contents($file);
            foreach ($item as $search => $replace) {
                $text = str_replace($search, $replace, $text);
            }
            file_put_contents($file, $text);
        }
    }
}