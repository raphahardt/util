<?php

namespace Djck\database\query;

use Djck\Core;

Core::uses('InstructionBase', 'Djck\database\query\base');

/**
 * Description of IUpdate
 *
 * @todo fazer
 * @author Rapha e Dani
 */
class IInsertAll
extends base\InstructionBase {
  
  private $fields = array();
  private $entity;
  
  public function __construct(Table $table, $fields = array(), $defs = null) { 
    parent::__construct();
    
    if (!is_array($fields)) $fields = array($fields);
    
    $this->fields = $fields;
    $this->entity = $table;
    $this->filter = null;
    $this->defs = $defs;
  }
  
  public function __toString() {
    
    if (empty($this->fields)) {
      return '';
    }
    
    parent::__toString();
    
    $s = 'INSERT INTO ';
    $s .= $this->entity . ' '. $this->entity->getAlias();
    $s .= ' (';
    $s .= implode(', ', $this->fields);
    $s .= ') VALUES (';
    
    $sSel = '';
    foreach ($this->fields as $field) {
      $sSel .= ( empty($sSel) ? '' : ', ' );
      $sSel .= self::parseValue($field->getValue());
    }
    $s .= $sSel;
    $s .= ')';
    
    if ($this->defs) {
      $s .= ' '.$this->defs;
    }
    
    return $s;
  }
  
}
