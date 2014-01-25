<?php

namespace Djck;

require 'core/bootstrap.php';

use Djck\database\Dbc;
use Djck\database\query\Table;
use Djck\model\Model as NewModel;
Core::import('Model', 'Djck\model');

class AModel extends NewModel {
  
  public function __construct() {
    $table = new Table('teste', 't');
    $table->addField('id');
    $table->addField('coluna1');
    $table->addField('col2');
    $table->addField('col3');

    // cria o mapper que é como o model vai manipular os dados e que tipo de persistencia será usada
    /*$mapper = new mvc\mappers\DbcMapper();
    $mapper->setEntity($table);/**/

    /*$mapper = new mvc\mappers\TempMapper();
    $mapper->setFields(array(
        new database\query\Field('id'),
        new database\query\Field('coluna1'),
        new database\query\Field('col2'),
        new database\query\Field('col3'),
    ));
    $mapper->setEntity('');/**/

    $mapper = new mvc\mappers\FileMapper();
    $mapper->setFields(array(
        new database\query\Field('id'),
        new database\query\Field('coluna1'),
        new database\query\Field('col2'),
        new database\query\Field('col3'),
    ));
    $mapper->setEntity(TEMP_PATH . DS. 'file.txt');/**/
    
    // faz as definições para o model
    $this->setMapper($mapper);
    
    parent::__construct();
  }
}

$bd = Dbc::getInstance('default');
$bd->prepare('TRUNCATE teste');
$bd->execute();
$bd->free();
$bd = null;
$f = fopen(TEMP_PATH.DS.'file.txt', 'w');
fclose($f);

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

$reg['col2'] = 'AAAA';
$reg2['coluna1'] = 'joao';
//$reg2->delete();
$model->delete($reg2);

$reg3 = $model->create();
$reg3['coluna1'] = 'ookokokoko';
$reg3['col3'] = 'SSSSookokokoko';

//dump($model);
$model->digest();

$reg2b = $model->get($reg3['id']);
$reg2b['col2'] = 'CCC';
$reg['col3'] = 'DDD';

$model->digest();

$allregs = $model->orderBy('coluna1', true)->limit(2)->columns(array('coluna1', 'id'))->getAll();
foreach ($allregs as $r) {
  dump($r);
  dump($r['coluna1']);
}

echo '<hr>';

//dump($reg2);
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
