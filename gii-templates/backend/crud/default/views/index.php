<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();
$nameAttribute = $generator->getNameAttribute();

echo "<?php\n";
?>

use yii\helpers\Html;
use yii\bootstrap\BaseHtml;
use <?= $generator->indexWidgetType === 'grid' ? "yii\\grid\\GridView" : "yii\\widgets\\ListView" ?>;
<?= $generator->enablePjax ? "use yii\widgets\Pjax;\n" : '' ?>
use common\widgets\Box;
use common\models\Lookup;

/* @var $this yii\web\View */
<?= !empty($generator->searchModelClass) ? "/* @var \$searchModel " . ltrim($generator->searchModelClass, '\\') . " */\n" : '' ?>
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = <?= $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>;
$this->params['title'] = $this->title;
$this->params['breadcrumbs'] = [
    // ['label' => '', 'url' => ''],
    $this->title,
];
?>
<div class="row <?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-index">
    <div class="col-sm-12">
<?php if(!empty($generator->searchModelClass)): ?>
<?= "       <?php " . ($generator->indexWidgetType === 'grid' ? "// " : "") ?>echo $this->render('_search', ['model' => $searchModel]); ?>
<?php endif; ?>
        <p>
            <?= "<?= " ?>Html::a(<?= $generator->generateString('Create ' . Inflector::camel2words(StringHelper::basename($generator->modelClass))) ?>, ['create'], ['class' => 'btn btn-success']) ?>
        </p>
        <?= "<?php " ?>Box::begin([
            'title' => $this->title,
        ]);<?= "?>\n" ?>
<?= $generator->enablePjax ? "            <?php Pjax::begin(); ?>\n" : '' ?>
<?php if ($generator->indexWidgetType === 'grid'): ?>

            <?= "<?= " ?>GridView::widget([
                'dataProvider' => $dataProvider,
                <?= !empty($generator->searchModelClass) ? "'filterModel' => \$searchModel,\n               'columns' => [\n" : "'columns' => [\n"; ?>
                    ['class' => 'yii\grid\SerialColumn'],
<?php
$count = 0;
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        if (++$count < 6) {
            echo "                    '" . $name . "',\n";
        } else {
            echo "                    // '" . $name . "',\n";
        }
    }
} else {
    foreach ($tableSchema->columns as $column) {
        $format = $generator->generateColumnFormat($column);
        if (++$count < 6) {
            echo "                    '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
        } else {
            echo "                    // '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
        }
    }
}
?>

                    /*
                    [
                        'attribute' => 'status',
                        'filter' => Lookup::items('UserStatus'),
                        'value' => function ($model, $key, $index, $column) {
                            return Lookup::item('UserStatus', $model->status);
                        },
                    ],
                    [
                        'label' => '',
                        'format' => 'raw',
                        'value' => function ($model, $key, $index, $column) {
                            return $model->rolesString;
                        },
                    ],
                    */
                    ['class' => 'yii\grid\ActionColumn'],
                    /*
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {update} {delete} {}',
                        'buttons' => [
                            '' => function ($url, $model, $key) {
                                return BaseHtml::a(BaseHtml::icon(''), ['/order/view', 'id' => $model->id],[
                                    'title' => '',
                                    'data' => [
                                        'id' => $model->id,
                                    ],
                                ]);
                            },
                        ],
                    ],
                    */
                ],
            ]); ?>
<?php else: ?>
            <?= "<?= " ?>ListView::widget([
                'dataProvider' => $dataProvider,
                'options' => ['class' => 'row'],
                'itemOptions' => ['class' => 'col-sm-12 col-md-6 col-lg-4'],
                'summaryOptions' => ['class' => 'col-xs-12'],
                'itemView' => '_list-view',
            ]) ?>
<?php endif; ?>
<?= $generator->enablePjax ? "            <?php Pjax::end(); ?>\n" : '' ?>
        <?= "<?php " ?>Box::end();<?= "?>\n" ?>
    </div>
</div> <!-- .row -->