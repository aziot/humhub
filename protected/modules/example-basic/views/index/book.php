<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>
<?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'title') ?>

    <div class="form-group">
        <?= Html::submitButton('Create Space', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
