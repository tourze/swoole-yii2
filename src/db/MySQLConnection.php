<?php

namespace tourze\swoole\yii2\db;

use xj\dbreconnect\base\Connection;

class MySQLConnection extends Connection
{

    /**
     * @var string
     */
    public $commandClass = 'tourze\swoole\yii2\db\Command';
}
