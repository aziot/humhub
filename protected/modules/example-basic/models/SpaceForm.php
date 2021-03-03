<?php

namespace acmeCorp\humhub\modules\models;

use Yii;
use yii\base\Model;

class SpaceForm extends Model
{
    public $total;

    public function rules()
    {
        return [
            [['total'], 'required'],
        ];
    }
}
