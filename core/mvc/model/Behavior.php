<?php

namespace Djck\mvc;

use Djck\Core;
use Djck\system\AbstractSingleton;

// registra os behaviors principais
Core::registerPackage('Djck\mvc:model\behaviors');

/**
 * Description of Behavior
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 */
abstract class Behavior {
  
  // TODO: usar a prioridade de um behavior para decidir qual metodo o model vai usar primeiro
  public $priority = 0;
  
}