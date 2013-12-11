<?php

namespace Djck\database\query\base;

use Djck\Core;
use Djck\database\query\interfaces;

Core::uses('Base', 'Djck\database\query\base');

/**
 * Description of EntityBase
 *
 * @author Rapha e Dani
 */
abstract class EntityBase extends Base implements interfaces\HasAlias {
  
  protected $alias = null;
  
  public function setAlias($alias) {
    $this->alias = $alias;
  }
  
  public function getAlias() {
    return $this->alias;
  }
  
  public function toAlias() {
    if ($this->alias) {
      return $this->alias;
    } else {
      return (string)$this;
    }
  }
  
}
