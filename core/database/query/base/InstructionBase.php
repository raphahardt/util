<?php

namespace Djck\database\query\base;

use Djck\Core;

Core::uses('Base', 'Djck\database\query\base');

/**
 * Description of InstructionBase
 *
 * @author Rapha e Dani
 */
abstract class InstructionBase extends Base {
  
  protected $filter;
  protected $defs; // campo que pode aceitar qualquer tipo de objeto ou texto e adenda
  // sempre no fim de uma instrucao. por ex: INSERT INTO .... 'RETURNING ID into :id' <-
  
  function setFilter(ExpressionBase $filter) {
    $this->filter = $filter;
  }
  
  function getFilter() {
    return $this->filter;
  }
  
  function setDef($def) {
    $this->defs = $def;
  }
  
  function getDef() {
    return $this->defs;
  }
  
  function __toString() {
    // antes de começar qualquer instrução, os binds precisam ser limpos
    $this->clearBinds();
  }
  
}
