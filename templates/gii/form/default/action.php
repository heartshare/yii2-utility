<?php
/**
 * This is the template for generating an action view file.
 */

use yii\helpers\Inflector;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\form\Generator */

echo "<?php\n";
?>

public function action<?= Inflector::id2camel(trim(basename($generator->viewName), '_')) ?>()
{
	$model = new <?= $generator->modelClass ?><?= empty($generator->scenarioName) ? "()" : "(['scenario' => '{$generator->scenarioName}'])" ?>;
	
	if ($model->load(Yii::$app->request->post()) && $model->validate()) {
		// do sth.
	}
	
	return $this->render('<?= basename($generator->viewName) ?>', [
		'model' => $model,
	]);
}