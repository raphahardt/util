<?php

namespace Djck;

require 'core/bootstrap.php';

use Djck\model\Model as NewModel;
Core::import('Model', 'Djck\model');

class AModel extends NewModel {
  
  public function __construct() {
    
    // cria o mapper que é como o model vai manipular os dados e que tipo de persistencia será usada
    $mapper = new mvc\mappers\TempMapper();
    $mapper->setFields(array(
        new database\query\Field('id'),
        new database\query\Field('coluna1'),
        new database\query\Field('col2'),
        new database\query\Field('col3'),
    ));
    $mapper->setEntity('');
    
    // faz as definições para o model
    $this->setMapper($mapper);
    
    parent::__construct();
  }
  
}

$model = AModel::getInstance();
$reg = $model->create();
$reg['coluna1'] = 'João da Silva';
$reg['col2'] = 'Rua A, 123';
$reg['col3'] = 'Sao paulo';

$reg2 = $model->create();
$reg2['coluna1'] = 'Maria José';
$reg2['col2'] = 'Rua B, 456';
$reg2['col3'] = 'Campinas';

$model->digest();

$reg2['col2'] = 'AAAA';
//$reg2['coluna1'] = 'joao';
//$reg2->delete();
//$reg3 = $model->create();
//$reg3['coluna1'] = 'ookokokoko';

$model->digest();

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
