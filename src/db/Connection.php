<?php

namespace tourze\swoole\yii2\db;

class Connection extends \xj\dbreconnect\base\Connection
{

    /**
     * @var string
     */
    public $commandClass = 'tourze\swoole\yii2\db\Command';
}
