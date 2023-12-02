<?php

$params = require(__DIR__ . '/params.php');
$db = require(__DIR__ . '/db.php');

$config = [
    'id' => 'yii2-workerman',
    'name' => 'yii2-workerman',
    'bootstrap' => ['log'],
    'language' => 'zh',
    'timeZone' => 'Asia/Shanghai',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@webroot' => '@app/web',
    ],
    'basePath' => dirname(__DIR__),
    'params' => $params,
    'components' => [
        'db' => $db,
        // Functions are faster than array declarations,
        // since they avoid the cost of Dependency Injection.
        'cache' => function () {
            return new yii\caching\FileCache([
                'gcProbability' => 0,
            ]);
        },
        'log' => function () {
            return new yii\log\Dispatcher([
                'traceLevel' => YII_DEBUG ? 3 : 0,
                'flushInterval' => 1,
                'targets' => [
                    new yii\log\FileTarget([
                        'exportInterval' => 1,
                        'levels' => ['error', 'warning'],
                        'microtime' => true,
                    ]),
                ]
            ]);
        },
        'redis' => function () {
            return new yii\redis\Connection([
                'hostname' => '127.0.0.1',
                'port' => 6379,
                'database' => 0,
                'retries' => 1,
            ]);
        },
        'urlManager' => function () {
            return new yii\web\UrlManager([
                'enablePrettyUrl' => true,
                'showScriptName' => false,
            ]);
        },
        // These components are overloaded for a small gain in performance (no DI)
        'request' => function () {
            return new yii\web\Request([
                'cookieValidationKey' => '',
                'enableCsrfValidation' => false,
            ]);
        },
        'response' => function () {
            return new yii\web\Response();
        },
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    // $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}
return $config;