<?php

namespace Djck;

require 'core/bootstrap.php';

class Teste extends system\AbstractAspectDelegate {
  
  protected function metodo($a, $b='bbbbbbbbb2323423') {
    return "Você quis ($a) e [$b]";
  }
  
  protected function getNome() {
    return "jose";
  }
  
  protected function getSobrenome() {
    return "da silva";
  }
  
}

class TesteAdvice extends aspect\Advice {
  
  public function beforeMetodo($arguments) {
    array_pop($arguments);
    return $arguments;
  }
  
  public function afterGets($result) {
    return strtoupper($result);
  }
  
}

$teste = new Teste;
$aspect = new TesteAdvice;

system\AspectDelegator::register($teste, 'metodo', $aspect);
system\AspectDelegator::register($teste, '/^get.*/', $aspect, 'gets');

dump($teste->metodo('aaa', 'bbb'));
//dump($teste->getNome());
//dump($teste->getSobrenome());
dump($teste);

finish();

database\DbcConfig::set('aaa', array(
  '#host'     => 'localhost',
  '#user'     => 'root',
  '#password' => '',
  '#schema'   => 'fastmotors'
));

$bd = database\Dbc::getInstance('aaa');

$bd->prepare('select id from compra');
$bd->execute();
while ($row = $bd->fetch_assoc()) {
  dump($row);
}
$bd->close();

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
