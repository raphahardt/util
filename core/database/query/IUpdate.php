<?php

namespace Djck\database\query;

use Djck\Core;

Core::uses('InstructionBase', 'Djck\database\query\base');

/**
 * Description of IUpdate
 *
 * @author Rapha e Dani
 */
class IUpdate
extends base\InstructionBase {
  
  private $fields = array();
  private $entity;
  
  public function __construct(Table $table, $fields = array(), $where = null, $defs = null) { 
    parent::__construct();
    
    if (!is_array($fields)) $fields = array($fields);
    
    $this->fields = $fields;
    $this->entity = $table;
    $this->filter = $where;
    $this->defs = $defs;
  }
  
  public function __toString() {
    
    if (empty($this->fields) || empty($this->filter)) {
      return '';
    }
    
    parent::__toString();
    
    $s = 'UPDATE ';
    $s .= $this->entity . ' '. $this->entity->getAlias();
    $s .= ' SET ';
    
    $sSel = '';
    foreach ($this->fields as $key => $field) {
      $sSel .= ( empty($sSel) ? '' : ', ' );
      if ($field instanceOf base\Base) {
        $sSel .= $field . ' = '. self::parseValue($field->getValue());
      } else {
        $sSel .= $this->entity->getField($key) . ' = '. self::parseValue($field);
      }
    }
    $s .= $sSel;
    
    if ($this->filter) {
      $s .= ' WHERE '. $this->filter;
    }
    
    if ($this->defs) {
      $s .= ' '.$this->defs;
    }
    
    return $s;
  }
  
}
