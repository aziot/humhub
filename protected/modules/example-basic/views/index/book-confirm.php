<?php
use yii\helpers\Html;
?>
<p>Book Information:</p>

<ul>
    <li><label>Title</label>: <?= Html::encode($model->title) ?></li>
    <li><label>Name</label>: <?= Html::encode($model->name) ?></li>
    <li><label>Description</label>: <?= Html::encode($model->description) ?></li>
    <li><label>URL</label>: <?= Html::encode($model->url) ?></li>
</ul>
