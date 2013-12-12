<?php

namespace Djck;

require 'core/bootstrap.php';

database\DbcConfig::set('aaa', array(
  '#host'     => 'localhost',
  '#user'     => 'root',
  '#password' => '',
  '#schema'   => 'fastmotors'
));

$bd = database\Dbc::getInstance('aaa');

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

$mapper->setOrderBy(array(array($mapper->coluna2, 'desc')));
$mapper->setFilter(array(new database\query\Criteria($mapper->coluna, '>=', 2000),
        new database\query\Criteria($mapper->coluna, '<=', 3000)));
dump($mapper->select());

//$mapper->get(25);

$mapper['coluna2'] = new database\query\Expression('+', new database\query\Expression('+', $mapper->coluna, $mapper->coluna2), 1000000);
//$mapper['coluna2'] = new database\query\Expression('+', new database\query\Expression('+', $mapper->coluna, $mapper->coluna2, 1), 1000000);

dump($mapper->update());


//$mapper->setFilter(array(new database\query\Criteria($mapper->coluna, '>', 2000),
//        new database\query\Criteria($mapper->coluna, '<=', 3000)));

dump($mapper->select());

$count =0;
$mapper->first();
do {
  echo ++$count ,'(', $mapper['id'], ')>', $mapper['coluna'].' - '.$mapper['coluna2'].'<br>';
} while ($mapper->next());

$mapper->first();
$mapper['coluna2'] = 1;
//$mapper->setFilter(array());
dump($mapper->count());
dump($mapper->delete());
dump($mapper->count());

echo '<hr>';

$count = 0;
$mapper->first();
do {
  echo ++$count ,'(', $mapper['id'], ')>', $mapper['coluna'].' - '.$mapper['coluna2'].'<br>';
} while ($mapper->next());

$mapper->next();
$mapper->prev();
dump($mapper['coluna2']);

$mapper->first();

dump($mapper);


//$Dispatcher = new Dispatcher();
//$Dispatcher->dispatch($Router, $Q);
