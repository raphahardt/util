<?php

abstract class SQLInstructionBase extends SQLBase {
  
  protected $filter;
  protected $defs; // campo que pode aceitar qualquer tipo de objeto ou texto e adenda
  // sempre no fim de uma instrucao. por ex: INSERT INTO .... 'RETURNING ID into :id' <-
  
  function setFilter(SQLExpressionBase $filter) {
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