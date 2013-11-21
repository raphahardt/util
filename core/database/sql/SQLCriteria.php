<?php

class SQLCriteria extends SQLExpressionBase implements ISQLExpressions {
  
  private $field;
  private $value = null;
  private $isRegexp = false;
  
  protected function hash() {
    return SQLBase::key(__CLASS__.self::$_obj_id++);
  }
  
  public function __construct(SQLBase $field, $sign, $value) {
    parent::__construct();
    $this->build($field, $sign, $value);
  }
  
  public function build(SQLBase $field, $sign, $value) {
    
    $this->field = $field;
    $this->setOperator($sign);
    $this->value = $value;
    $this->isRegexp = $this->operator === 'REGEXP';
    
    if (!$this->operator)
      throw new SQLException('Sinal inválido');
  }
  
  public function destroy() {
    unset($this->field, $this->operator, $this->value);
    $this->isRegexp = false;
  }
  
  public function get() {
    return $this->getField();
  }
  
  public function getField() {
    return $this->field;
  }
  
  public function getValue() {
    // TODO: escapar os valores devidamente
    return $this->value;
  }
  
  public function setField(SQLBase $f) {
    $this->field = $f;
  }
  
  public function setValue($val) {
    if ($this->_isDateString($val)) {
      $val = new SQLTDateTime($val);
    }
    $this->value = $val;
  }
  
  protected function _validateOperator($operator) {
    // TODO: passar essa validação pro SQLBase
    $operator = strtoupper($operator);
    
    $validOperators = $this->_getValidOperators();
    
    if ( in_array($operator, $validOperators['criteria']) )
      return $operator;
    
    return false;
  }
  
  // funções q ainda to pensando se sao necessarios =========
  
  public function __toString() {
    
    if (!$this->field)
      return '';
    
    // TODO: tratar regexp, tipos de valores e etc
    
    $value = $this->value;
    if ($value instanceof SQLBase) {
      $value = (string)$value;
    } elseif (is_array($value)) {
      foreach ($value as &$v) {
        $v = SQLBase::parseValue($v);
      }
      unset($v);
    } else {
      $value = SQLBase::parseValue($value);
    }
    
    // mudanças no operador
    $operator = $this->operator;
    if ($value == 'NULL') {
      if ($operator == '=') {
        $operator = 'IS';
      } elseif ($operator == '!=' || $operator == '<>') {
        $operator = 'IS NOT';
      }
    }
    if (is_array($value) && !($operator == 'BETWEEN' || $operator == 'NOT BETWEEN')) {
      if ($operator != 'IN' && $operator != 'NOT IN') {
        if ($operator == '!=' || $operator == '<>') {
          $operator .= ' ALL';
        } else {
          $operator .= ' ANY';
        }
      }
    }
    
    $s = $this->field.' '.$operator .' ';
    if ($operator == 'BETWEEN' || $operator == 'NOT BETWEEN') {
      $s .= ($value ? ''.$value[0].' AND '.$value[1]:'NULL');
    /*} elseif ($operator == 'LIKE' || $operator == 'NOT LIKE') {
      $s .= ($value ? self::F_UCASE.'('.$value.')' : 'NULL');*/
    } else {
      $s .= ($value ? (is_array($value) ? '('.implode(', ', $value).')':$value):'NULL');
    }
    
    return $s;
  }  
}