<?php

namespace Djck\database\query;

use Djck\Core;

Core::uses('InstructionBase', 'Djck\database\query\base');

/**
 * Description of IUpdate
 *
 * @author Rapha e Dani
 */
class IInsert
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

    // tira temporariamente o alias da tabela, pois INSERT não suporta alias
    $alias = $this->entity->getAlias();
    $this->entity->setAlias(null);
    
    $s = 'INSERT INTO ';
    $s .= $this->entity;// . ' '. $this->entity->getAlias();
    $s .= ' (';
    //$s .= implode(', ', $this->fields);
    $sSel = '';
    foreach ($this->fields as $key => $field) {
      $sSel .= ( empty($sSel) ? '' : ', ' );
      if ($field instanceOf base\Base) {
        $sSel .= (string)$field;
      } else {
        $sSel .= (string)$this->entity->getField($key);
      }
    }
    $s .= $sSel;
    $s .= ') VALUES (';
    
    $sSel = '';
    foreach ($this->fields as $key => $field) {
      $sSel .= ( empty($sSel) ? '' : ', ' );
      if ($field instanceOf base\Base) {
        $sSel .= self::parseValue($field->getValue());
      } else {
        $sSel .= self::parseValue($field);
      }
    }
    $s .= $sSel;
    $s .= ')';
    
    if ($this->defs) {
      $s .= ' '.$this->defs;
    }

    // devolve o alias da tabela
    $this->entity->setAlias($alias);
    
    return $s;
  }
  
}
