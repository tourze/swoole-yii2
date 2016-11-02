<?php

namespace demo\models;

use yii\db\ActiveRecord;

class User extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_user}}';
    }
}
