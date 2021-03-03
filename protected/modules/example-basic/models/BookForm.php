<?php

namespace acmeCorp\humhub\modules\models;

use Yii;
use yii\base\Model;

class BookForm extends Model
{
    public $title;
    public $name;
    public $description;
    public $url;

    public function rules()
    {
        return [
            [['title'], 'required'],
        ];
    }
}
