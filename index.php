<?php

namespace Djck;

require 'core/bootstrap.php';

Core::dump();

$mapper = new mvc\mappers\TempMapper();
//$mapper->setFields(array(new database\query\Field('coluna'), new database\query\Field('coluna2'), new database\query\Field('coluna4')));
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

$mapper->setOrderBy(array(array($mapper->coluna2, 'desc')));
$mapper->setFilter(array(new database\query\Criteria($mapper->coluna, '>=', 2000),
        new database\query\Criteria($mapper->coluna, '<=', 3000)));
dump($mapper->select());

//$mapper->get(25);

$mapper['coluna2'] = new database\query\Expression('+', new database\query\Expression('+', $mapper->coluna, $mapper->coluna2), 1000000);

dump($mapper->update());


$mapper->setFilter(array(new database\query\Criteria($mapper->coluna, '>', 1000),
        new database\query\Criteria($mapper->coluna, '<=', 4000)));

dump($mapper->select());

$count =0;
$mapper->first();
do {
  echo ++$count , '>', $mapper['coluna'].' - '.$mapper['coluna2'].'<br>';
} while ($mapper->next());

$mapper->first();
$mapper['coluna2'] = new database\query\Expression('+', new database\query\Expression('+', $mapper->coluna, $mapper->coluna2), 10000000);
//$mapper->refresh();
$mapper->next();
$mapper->prev();
dump($mapper['coluna2']);

dump($mapper);


//$Dispatcher = new Dispatcher();
//$Dispatcher->dispatch($Router, $Q);
