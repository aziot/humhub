<?php
use yii\helpers\Html;
?>
<p>Space Information:</p>

<ul>
    <li><label>Id</label>: <?= Html::encode($model->id) ?></li>
    <li><label>Guid</label>: <?= Html::encode($model->guid) ?></li>
    <li><label>Name</label>: <?= Html::encode($model->name) ?></li>
    <li><label>Description</label>: <?= Html::encode($model->description) ?></li>
    <li><label>URL</label>: <?= Html::encode($model->url) ?></li>
</ul>
