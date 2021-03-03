<?php

namespace acmeCorp\humhub\modules\models;

use Yii;
use yii\base\Model;

class SpaceForm extends Model
{
    public $id;
    public $guid;
    public $name;
    public $description;
    public $url;

    public function rules()
    {
        return [
            [['id'], 'required'],
        ];
    }
}
