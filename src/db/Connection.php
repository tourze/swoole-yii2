<?php

namespace tourze\swoole\yii2\db;

class Connection extends \yii\db\Connection
{

    /**
     * @var string
     */
    public $commandClass = 'tourze\swoole\yii2\db\Command';
}
