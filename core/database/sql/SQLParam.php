<?php

/**
 * Campo de uma tabela.
 */
class SQLParam extends SQLBase {
  
  private $name;
  private $type = 'IN';
  private $data = null;
  
  protected function hash() {
    return SQLBase::key($this->name);
  }

  public function __construct($name, $type = null) {
    $this->build($name, $type);
    parent::__construct();
  }
  
  public function build($name, $type = null) {
    $this->name = $name;
    if ($type)
      $this->type = strtoupper($type) == 'OUT' ? 'OUT' : 'IN';
  }
  
  public function destroy() {
    $this->name = null;
    $this->type = 'IN';
  }
  
  public function get() {
    return $this->getName();
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function getValue() {
    // TODO: escapar os valores devidamente
    return $this->data;
  }
  
  public function setName($f) {
    $this->name = $f;
  }
  
  public function setValue($val) {
    $this->data = $val;
  }
  
  function setType($type) {
    $this->type = strtoupper($type) == 'OUT' ? 'OUT' : 'IN';
  }
  
  function getType() {
    return $this->type;
  }
  
  function __toString() {
    
    //$s = SQLBase::parseValue($this->data, SQLBase::key($this->name));
    $s = $this->name;
    return $s;
  }
  
}