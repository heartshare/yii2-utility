<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();

echo "<?php\n";
?>

use drodata\helpers\Html;
use drodata\widgets\Box;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

$this->title = <?= $generator->generateString('Update {modelClass}: ', ['modelClass' => Inflector::camel2words(StringHelper::basename($generator->modelClass))]) ?> . $model-><?= $generator->getNameAttribute() ?>;
$this->params = [
    'title' => $this->title,
    'subtitle' => '',
];
?>
<div class=row "<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-update">
    <div class="col-md-12 col-lg-6 col-lg-offset-3">
        <?= "<?= " ?>Box::widget([
            'title' => $this->title,
            'content' => $this->render('_form', [
                'model' => $model,
            ]),
        ]) <?= "?>\n" ?>
    </div>
</div>
