<?php

namespace Djck\database\query;

use Djck\Core;

Core::uses('InstructionBase', 'Djck\database\query\base');

/**
 * Description of IUpdate
 *
 * @author Rapha e Dani
 */
class IDelete
extends base\InstructionBase {
  
  private $entity;
  
  public function __construct(Table $table, base\Base $where = null, $defs = null) { 
    parent::__construct();
    
    $this->entity = $table;
    $this->filter = $where;
  }
  
  public function __toString() {
    
    if (empty($this->filter)) {
      return '';
    }
    
    parent::__toString();
    
    // mysql não suporta alias na tabela com delete
    // para resolver o problema, guardo o alias temporariamente
    // e uso o nome da tabela como alias. depois, volto o alias como estava
    $old_alias = $this->entity->getAlias();
    $this->entity->setAlias(null);
    
    $s = 'DELETE FROM ';
    $s .= $this->entity;
    
    if ($this->filter) {
      $s .= ' WHERE '. $this->filter;
    }
    
    // volto o alias como estava
    $this->entity->setAlias($old_alias);
    
    return $s;
  }
  
}
