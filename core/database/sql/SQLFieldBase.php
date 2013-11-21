<?php

abstract class SQLFieldBase extends SQLBase implements ISQLAliasable, ISQLOrdenable, ISQLFunctionable, ISQLSelectables {
  
  protected $alias = null;
  protected $orderDesc = false;
  protected $functions = array();
  protected $showFunctions = true;
  protected $parentTable = null;
  
  public function setTable(SQLTable &$t) {
    $this->parentTable = $t;
  }
  
  public function getTable() {
    return $this->parentTable;
  }
  
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
  
  public function getFunction() {
    $lfunc = end($this->functions);
    return $lfunc['name'];
  }


  public function setFunction($func, $params = null) {
    $args = func_get_args();
    array_shift($args);
    
    $functionParams = array();
    
    if (count($args) > 1) {
      $functionParams = $args;
    } elseif (count($args) == 1) {
      if (is_array($params)) {
        $functionParams = $params;
      } else {
        $functionParams[] = $params;
      }
    }
    
    $this->functions[SQLBase::key($func)] = array( 'name' => $func, 'params' => $functionParams );
  }
  
  public function unsetFunction($func) {
    unset($this->functions[SQLBase::key($func)]);
  }
  
  public function showFunctions($bool) {
    $this->showFunctions = (bool)$bool;
  }

  public function getOrder() {
    return ($this->orderDesc ? 'DESC' : 'ASC');
  }
  
  public function setOrder($order) {
    $this->orderDesc = (strtoupper($order) == 'DESC' ? true : false );
  }
  
}