<?php

/**
 * The purpose of this file is to tell the IDE how to autocomplete.
 *
 * @noinspection PhpMissingFieldTypeInspection
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

use Workerman\Connection\TcpConnection;

class Yii
{
    /**
     * @var \yii\web\Application|\yii\console\Application|__Application
     */
    public static $app;
}

/**
 * @property yii\rbac\DbManager $authManager
 * @property \yii\web\User|__WebUser $user
 * @property \yii\redis\Connection $redis
 * @property TcpConnection $connection
 *
 */
class __Application
{
}

class __WebUser
{
}
