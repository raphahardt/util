<?php

namespace Djck\database\query;

use Djck\Core;
use Djck\types;

Core::uses('FieldBase', 'Djck\database\query\base');

/**
 * Description of Field
 *
 * @author Rapha e Dani
 */
class Field
extends base\FieldBase
implements interfaces\Expressee {
  
  private $name;
  private $strictName = false;
  private $data = null;
  
  private $over = null;
  
  protected function makeHash() {
    return $this->getAlias();
  }
  
  public function __construct($name, $alias = null) {
    parent::__construct();
    $this->name = $name;
    $this->alias = $alias;
  }
  
  public function setName($name) {
    $this->name = $name;
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function setValue($value) {
    if (types\DateTime::seemsDateTime($value)) {
      // se a string for uma data/hora, transformar em objeto
      $value = new types\DateTime($value);
    }
    $this->data = $value;
  }
  
  public function getValue() {
    // TODO: escapar os valores devidamente
    return $this->data;
  }
  
  public function isStrictName($bool = null) {
    if (is_null($bool)) {
      return $this->strictName;
    }
    $this->strictName = (bool)$bool;
  }
  
  public function setOver($over) {
    $this->over = $over;
  }
  
  public function getOver() {
    return $this->over;
  }
  
  public function getAlias() {
    return $this->alias ?: $this->name;
  }
  
  function isAnalyticFunction() {
    return strpos($this->name, '()') !== false;
  }
  
  
  
  function __toString() {
    
    $s = $this->name;
    
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
          array_walk($func['params'], array(self, 'parseValueWalk'));
          $s .= ', '.implode(', ', $func['params']);
        }
        $s .= ')';
      }

    }
    
    return $s;
  }
  
  
}
