<?php

class SQLJoin extends SQLEntityBase {
  
  private $table1; // pode ser uma tabela ou outro join
  private $table2; // pode ser uma tabela ou outro join
  private $innertype;
  private $on; // expressao
  
  // para implementar
  private $using = array(); // sqlfields
  
  protected function hash() {
    return SQLBase::key(__CLASS__.self::$_obj_id++);
  }
  
  public function __construct($innertype, SQLEntityBase $table1, SQLEntityBase $table2, SQLExpressionBase $on) {
    parent::__construct();
    $this->build($innertype, $table1, $table2, $on);
  }
  
  public function build($innertype, SQLEntityBase $table1, SQLEntityBase $table2, SQLExpressionBase $on) {
    $this->table1 = $table1;
    $this->table2 = $table2;
    $this->innertype = strtoupper($innertype); // TODO: validar os tipos de join
    $this->on = $on;
  }
  
  public function destroy() {
    $this->table1 = null;
    $this->table2 = null;
    $this->innertype = null;
    $this->on = null;
  }
  
  public function get() {
    return array($this->table1, $this->table2);
  }
  
  public function getTable1() {
    $table = $this->table1;
    if ($table instanceof SQLJoin) {
      $table = $table->getTable1();
    }
    return $table;
  }
  
  public function getTable2() {
    $table = $this->table2;
    if ($table instanceof SQLJoin) {
      $table = $table->getTable2();
    }
    return $table;
  }
  
  public function setAlias($alias) { return; }
  public function getAlias() { return ''; }
  public function toAlias() { return ''; }
  
  public function __toString() {
    if (!($this->table2))
      return '';
    
    $s = '';
    if ($this->table1 instanceof SQLJoin) {
      $s .= "{$this->table1} "; // join 1
    } else {
      $s .= "{$this->table1} {$this->table1->getAlias()} "; // tabela 1
    }
    $s .= "{$this->innertype} JOIN ";
    if ($this->table2 instanceof SQLJoin) {
      $s .= "({$this->table2}) "; // join 2
    } else {
      $s .= "{$this->table2} {$this->table2->getAlias()} "; // tabela 2
    }
    // clausulas de condicao
    $s .= 'ON ('.($this->on->getNegate() == true ? ' NOT ':'').$this->on.')'; // on

    return $s;
  }
  
}