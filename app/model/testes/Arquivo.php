<?php

namespace App\model\testes;

use Djck\Core;
use App\model\AppModel;

Core::uses('AppModel', 'App');

class Arquivo extends AppModel {
  
  protected $permanent_delete = false;
  protected $log = false;
  
  public function __construct() {
    
    $mapper = new FileMapper();
    $mapper->setEntity(TEMP_PATH.'/arquivos.txt');
    $mapper->setFields(array('arq', 'data', 'ord'));
    $mapper->setPointer('arq');
    
    $this->setMapper($mapper);
    
    $this->addBehavior('Single');
    
    return parent::__construct();
  }
  
}