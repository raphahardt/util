<?php

namespace Djck\database\query;

use Djck\Core;

Core::uses('EntityBase', 'Djck\database\query\base');

/**
 * Description of Table
 *
 * @author Rapha e Dani
 */
class Table
extends base\EntityBase {
  
  private $table;
  private $schema;
  private $fields = array();
  
  // para implementar
  private $partition; // é um objeto sqlexpression ex: FROM table PARTITION (expressao)
  
  protected function makeHash() {
    return $this->getAlias();
  }
  
  public function __construct($name, $alias = null, $schema = null) {
    parent::__construct();
    
    $this->table = $name;
    $this->alias = $alias;
    $this->schema = $schema;
  }
  
  public function setName($name) {
    $this->table = $name;
  }
  
  public function getName() {
    return $this->table;
  }
  
  public function addField($name, $alias = null, $type = null) {
    if ($name instanceof Field) {
      $field = $name;
    } elseif (is_string($name)) {
      $field = new Field($name, $alias, $type);
    } else {
      throw new base\QueryException('O método '.__METHOD__.' só aceita Field como parâmetro');
    }
    
    // define quem é o pai do campo
    //if ($name instanceof SQLField)
    $field->setTable($this);
    
    // aux para inserir o campo sem repetir
    $this->fields[ $field->getHash() ] = $field;
  }
  
  public function getField($offset) {
    return $this->fields[$offset];
  }
  
  public function getFields() {
    return $this->fields;
  }
  
  public function getAlias() {
    return $this->alias ?: $this->table;
  }
  
  
  function __toString() {
    $s = $this->table;
    if ($this->schema) {
      $s = $this->schema . '.'.$s;
    }
    return $s;
  }
  
}
