<?php

abstract class SQLEntityBase extends SQLBase {
  
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
  //abstract function toKey();
  
}