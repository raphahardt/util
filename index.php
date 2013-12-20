<?php

namespace Djck;

require 'core/bootstrap.php';

use Djck\model\Model as NewModel;
Core::import('Model', 'Djck\model');

$model = new NewModel();
$registro = $model->create();
$registro['col1'] = 'bbb';
unset($registro);

$registro2 = $model->create();
$registro2['col1'] = 'ccc';

echo '<pre>';
dump($model);

$model->save();
dump($model);

$r = $model->get(2);
dump($r['col1']);
$r['col1'] = 'opopop';
dump($registro2['col1']);

dump($model);

finish();

$mapper = new mvc\mappers\TempMapper();
$mapper->setFields(array(new database\query\Field('id'), new database\query\Field('coluna'), new database\query\Field('coluna2'), new database\query\Field('coluna4')));
for($i=0;$i<49;$i++) {
  $mapper->push(array(
      'coluna' => $i * 100, 
      'coluna2' => $i
  ));
}
dump($mapper->insert());
$mapper->push(array(
    'coluna' => $i * 100, 
    'coluna2' => $i
));
dump($mapper->insert());

$mapper->get(3);
$mapper['coluna'] = '333333';
$mapper->refresh();

$mapper->get(6);
$mapper['coluna'] = '444444';
$mapper->refresh();

$mapper->get(4);
$mapper['coluna'] = '444444';
$mapper->refresh();

//$mapper->get(7);

dump($mapper->update());

dump($mapper);


//$Dispatcher = new Dispatcher();
//$Dispatcher->dispatch($Router, $Q);
