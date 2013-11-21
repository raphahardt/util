<?php

/**
 * Campo de uma tabela.
 */
class SQLField extends SQLFieldBase implements ISQLExpressions {
  
  private $fieldname;
  private $type;
  private $strictName = false;
  private $data = null;
  
  private $over = null;
  
  protected function hash() {
    return SQLBase::key($this->getAlias());
  }

  public function __construct($name, $alias = null) {
    $this->build($name, $alias);
    parent::__construct();
  }
  
  public function build($name, $alias = null) {
    $this->fieldname = $name;
    //$this->type = $this->_validateType($type);
    $this->alias = $alias;
  }
  
  public function destroy() {
    $this->fieldname = $this->alias = $this->over = null;
    $this->functions = array();
    $this->orderDesc = false;
  }
  
  public function get() {
    return $this->getField();
  }
  
  public function getField() {
    return $this->fieldname;
  }
  
  public function getValue() {
    // TODO: escapar os valores devidamente
    return $this->data;
  }
  
  public function getStrictName() {
    return $this->strictName;
  }
  
  public function setField($f) {
    $this->fieldname = $f;
  }
  
  public function setValue($val) {
    if ($this->_isDateString($val)) {
      $val = new SQLTDateTime($val);
    }
    $this->data = $val;
  }
  
  public function setStrictName($bool) {
    $this->strictName = $bool;
  }
  
  public function getAlias() {
    return $this->alias ? $this->alias : $this->fieldname;
  }

  public function setOver($over) {
    $this->over = $over;
  }
  
  public function getOver() {
    return $this->over;
  }
  
  function getName() {
    return $this->fieldname;
  }
  
  function getType() {
    return $this->type;
  }
  
  function isAnalyticFunction() {
    return strpos($this->fieldname, '()') !== false;
  }
  
  function __toString() {
    
    $s = $this->fieldname;
    
    if ($this->strictName) {
      $s = '"'.$s.'"';
    }
    
    if ($tb = $this->parentTable) {
      $s = $tb->toAlias() . '.' . $s;
    }
    
    if ($this->showFunctions && $this->functions) {
      foreach ($this->functions as $func) {
        $s = $func['name'] . '('.$s;
      }

      foreach ($this->functions as $func) {
        if (!empty($func['params'])) {
          array_walk($func['params'], 'SQLBase::parseValueIterator');
          $s .= ', '.implode(', ', $func['params']);
        }
        $s .= ')';
      }

      // TODO: passar o Over pro ISelect
      /*if ($this->over) {
        $s .= ' OVER ('.$this->over.')';
      }*/

    }
    
    return $s;
  }
  
}