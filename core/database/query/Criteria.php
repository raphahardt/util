<?php

namespace Djck\database\query;

use Djck\Core;
use Djck\types;

Core::uses('ExpressionBase', 'Djck\database\query\base');

/**
 * Description of Criteria
 *
 * @author Rapha e Dani
 */
class Criteria 
extends base\ExpressionBase {
  
  private $field;
  private $value = null;
  
  protected function _validateOperator($operator) {
    // TODO: passar essa validação pro SQLBase
    $operator = strtoupper($operator);
    
    $validOperators = self::$validOperators;
    
    if ( isset($validOperators['criteria'][$operator]) ) {
      return $operator;
    }
    
    throw new QueryException('Operador "'.$operator.'" inválido');
  }
  
  public function __construct(base\Base $field, $sign, $value) {
    parent::__construct();
    
    $this->field = $field;
    $this->setOperator($sign);
    $this->value = $value;
  }
  
  public function setField(base\Base $field) {
    $this->field = $field;
  }
  
  public function getField() {
    return $this->field;
  }
  
  public function setValue($value) {
    if (types\DateTime::seemsDateTime($value)) {
      // se a string for uma data/hora, transformar em objeto
      $value = new types\DateTime($value);
    }
    $this->value = $value;
  }
  
  public function getValue() {
    return $this->value;
  }
  
  
  
  public function __toString() {
    
    if (!$this->field) {
      return '';
    }
    
    // TODO: tratar regexp, tipos de valores e etc
    
    $value = $this->value;
    if ($value instanceof base\Base) {
      $value = (string)$value;
    } elseif (is_array($value)) {
      array_walk($value, array(self, 'parseValueWalk'));
    } else {
      $value = self::parseValue($value);
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
    
    $s = (string)$this->field.' '.$operator .' ';
    if ($operator == 'BETWEEN' || $operator == 'NOT BETWEEN') {
      $s .= ($value ? ''.$value[0].' AND '.$value[1]:'NULL');
    /*} elseif ($operator == 'LIKE' || $operator == 'NOT LIKE') {
      $s .= ($value ? self::F_UCASE.'('.$value.')' : 'NULL');*/
    } else {
      $s .= ($value ? (
              is_array($value) ? '('.implode(', ', $value).')' : $value
            ) : 'NULL');
    }
    
    return $s;
  }
  
  
}
