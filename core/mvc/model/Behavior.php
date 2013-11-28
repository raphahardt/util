<?php

namespace Djck\mvc;

use Djck\Core;
use Djck\system\AbstractSingleton;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// registra os behaviors principais
$package = '/core/mvc/model/behaviors';
//Core::register('SingleBehavior', $package);
//Core::register('CollectionBehavior', $package);

/**
 * Description of Behavior
 *
 * @author usuario
 */
abstract class Behavior {
  
  // TODO: usar a prioridade de um behavior para decidir qual metodo o model vai usar primeiro
  public $priority = 0;
  
}