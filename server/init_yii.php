<?php

defined('YII_DEBUG') or define("YII_DEBUG", (bool)_env('YII_DEBUG', true));
defined('YII_ENV') or define("YII_ENV", _env('YII_ENV', 'dev'));
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);

require_once APP_PATH . '/vendor/yiisoft/yii2/Yii.php';
