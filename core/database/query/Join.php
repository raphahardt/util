<?php

namespace Djck\database\query;

use Djck\Core;

Core::uses('EntityBase', 'Djck\database\query\base');

/**
 * Description of Join
 *
 * @author Rapha e Dani
 */
class Join
extends base\EntityBase {
  
  private $table1; // pode ser uma tabela ou outro join
  private $table2; // pode ser uma tabela ou outro join
  private $innertype;
  private $on; // expressao
  
  // para implementar
  private $using = array(); // sqlfields
  
  public function __construct($innertype, base\EntityBase $table1, 
          base\EntityBase $table2, base\ExpressionBase $on) {
    parent::__construct();
    
    $this->table1 = $table1;
    $this->table2 = $table2;
    $this->innertype = strtoupper($innertype); // TODO: validar os tipos de join
    $this->on = $on;
  }
  
  public function getTable1() {
    $table = $this->table1;
    if ($table instanceof Join) {
      $table = $table->getTable1();
    }
    return $table;
  }
  
  public function getTable2() {
    $table = $this->table2;
    if ($table instanceof Join) {
      $table = $table->getTable2();
    }
    return $table;
  }
  
  public function __toString() {
    if (!($this->table2)) {
      return '';
    }
    
    $s = '';
    if ($this->table1 instanceof Join) {
      $s .= "{$this->table1} "; // join 1
    } else {
      $s .= "{$this->table1} {$this->table1->getAlias()} "; // tabela 1
    }
    $s .= "{$this->innertype} JOIN ";
    if ($this->table2 instanceof Join) {
      $s .= "({$this->table2}) "; // join 2
    } else {
      $s .= "{$this->table2} {$this->table2->getAlias()} "; // tabela 2
    }
    // clausulas de condicao
    $s .= 'ON ('.($this->on->getNegate() == true ? ' NOT ':'').$this->on.')'; // on

    return $s;
  }
  
}
