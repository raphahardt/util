<?php

namespace Djck\database\query;

use Djck\Core;

Core::uses('InstructionBase', 'Djck\database\query\base');

/**
 * Description of IUpdate
 *
 * @author Rapha e Dani
 */
class IOnDuplicate
extends base\InstructionBase {
  
  private $fields = array();
  
  public function __construct($fields = array()) {
    parent::__construct();
    
    if (!is_array($fields)) $fields = array($fields);
    
    $this->fields = $fields;
    $this->filter = null;
  }
  
  public function __toString() {
    
    if (empty($this->fields)) {
      return '';
    }
    
    parent::__toString();
    
    $s = 'ON DUPLICATE KEY UPDATE ';
    
    $sSel = '';
    foreach ($this->fields as $field) {
      $sSel .= ( empty($sSel) ? '' : ', ' );
      $sSel .= $field . ' = '. self::parseValue($field->getValue());
    }
    $s .= $sSel;
    
    return $s;
  }
  
}
