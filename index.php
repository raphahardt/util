<?php

namespace Djck;

require 'core/bootstrap.php';

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
