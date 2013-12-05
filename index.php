<?php

namespace Djck;

require 'core/bootstrap.php';

$mapper = new mvc\mappers\TempMapper();
for($i=0;$i<50;$i++) {
  $mapper->push(array('coluna' => rand(0, 4000)));
}
$mapper->setFilter(new database\query\Criteria(new database\query\Field('coluna'), '>', 200));
dump($mapper->select());

dump($mapper);

//$Dispatcher = new Dispatcher();
//$Dispatcher->dispatch($Router, $Q);
