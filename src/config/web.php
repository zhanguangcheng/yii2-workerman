<?php

$params = require(__DIR__ . '/params.php');
$db = require(__DIR__ . '/db.php');
$redis = require __DIR__ . '/redis.php';

$config = [
    'id' => 'yii2-workerman',
    'name' => 'yii2-workerman',
    'bootstrap' => ['log'],
    'language' => 'zh',
    'timeZone' => 'Asia/Shanghai',
    'aliases' => [
        '@bower' => '@vendor/yidas/yii2-bower-asset/bower',
        '@npm' => '@vendor/npm-asset',
        '@webroot' => '@app/web',
    ],
    'basePath' => dirname(__DIR__),
    'vendorPath' => dirname(__DIR__, 2) . '/vendor',
    'params' => $params,
    'components' => [
        'db' => $db,
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'gcProbability' => 0,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'flushInterval' => 1,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'exportInterval' => 1,
                    'microtime' => true,
                ],
            ]
        ],
        /*
        'redis' => $redis,
        */
        'errorHandler' => [
            'discardExistingOutput' => false,
            'silentExitOnException' => true,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
        'request' => [
            'cookieValidationKey' => '',
            'enableCsrfValidation' => false,
            // Trust Proxy IP
            'trustedHosts' => [
                '127.0.0.1'
            ]
        ],
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