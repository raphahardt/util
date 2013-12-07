<?php

namespace Djck;

require 'core/bootstrap.php';

$mapper = new mvc\mappers\TempMapper();
for($i=0;$i<50;$i++) {
  $mapper->push(array(
      'coluna' => $i * 100, 
      'coluna2' => rand(0, 900)
  ));
}
$mapper->setFilter(array(new database\query\Criteria(new database\query\Field('coluna'), '>=', 2000),
        new database\query\Criteria(new database\query\Field('coluna'), '<=', 3000)));
dump($mapper->select());

$mapper->get(25);

$mapper['coluna2'] = 'aaa';

dump($mapper->update());

/*$mapper->setFilter(array(new database\query\Criteria(new database\query\Field('coluna'), '>', 1000),
        new database\query\Criteria(new database\query\Field('coluna'), '<=', 4000)));

dump($mapper->select());*/

$count =0;
$mapper->first();
do {
  echo ++$count , '>', $mapper['coluna'].' - '.$mapper['coluna2'].'<br>';
} while ($mapper->next());

dump($mapper);


//$Dispatcher = new Dispatcher();
//$Dispatcher->dispatch($Router, $Q);
