<?php

class SQLIInsert extends SQLInstructionBase implements SQLArrayAccess {
  
  private $fields = array();
  private $entity;
  
  protected function hash() {
    return SQLBase::key(__CLASS__.self::$_obj_id++);
  }
  
  public function __construct(SQLTable $table, $fields = array(), $defs = null) { 
    parent::__construct();
    $this->build($table, $fields, $defs);
  }
  
  public function build(SQLTable $table, $fields = array(), $defs = null) { 
    if (!is_array($fields)) $fields = array($fields);
    
    $this->fields = $fields;
    $this->entity = $table;
    $this->filter = null;
    $this->defs = $defs;
 }
  
  public function destroy() {
    $this->entity = null;
  }
  
  public function addField($name, $alias = null) {
    if (!($name instanceof SQLBase)) {
      $alias_tmp = is_null($alias) ? $name : $alias;
      
      if (!$this->entity->getField($alias_tmp))
        $this->entity->addField($name, $alias);

      $f = $this->entity->getField($alias_tmp);
    } else {
      
      $f = $name;
    }
    $this->fields[ $f->getHash() ] = $f;
  }
  
  public function getField($alias) {
    return $this->fields[SQLBase::key($alias)];
  }
  
  public function setValue($alias, $value) {
    $field = $this->getField($alias);
    $field->setValue($value);
  }
  
  public function getValue($alias) {
    $field = $this->getField($alias);
    return $field->getValue();
  }
  
  public function add($expr) {
    $args = func_get_args();
    
    $fs = array();
    
    if (count($args) > 1) {
      $fs = $args;
    } elseif (count($args) == 1) {
      $expr = $args[0];
      if (is_array($expr)) {
        $fs = $expr;
      } else {
        $fs[] = $expr;
      }
    }
    
    foreach ($fs as $f) {
      if (!($f instanceof SQLField)) 
        throw new SQLException('O método '.__METHOD__.' só aceita SQLField como parametro');
      
      $this->fields[ $f->getHash() ] = $f;
    }
    
  }
  
  public function addFields() {
    $args = func_get_args();
    $this->callMethod('add', $args);
  }
  
  public function remove($hash) {
    if ($hash instanceof SQLBase)
      $hash = $hash->getHash();
    else
      $hash = SQLBase::key($hash);
    
    unset($this->fields[ $hash ]);
  }
  
  public function removeField($name) {
    $this->remove($name);
  }
  
  public function get() {
    return $this->getTable();
  }
  
  public function getTable() {
    return $this->entity;
  }
  
  public function getFields() {
    return $this->fields;
  }
    
  // --------------------- INICIO DOS METODOS DE ACESSO POR ARRAY ----
  
  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para adicionar um valor ao objeto (ex: $obj[] = 'valor').
   */
  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      //$this->data[] = $value;
      throw new SQLException('Não é possível definir valores para um campo sem nome');
      // TODO: deixar ele acrescentar valores, desde que os fields tenham sido definidos
      // e que o valor a ser adicionado não ultrapasse o numero de campos definidos
    } else {
      $field = $this->fields[ SQLBase::key($offset) ];
      if (!($field instanceof SQLField))
        throw new SQLException('Campo '.$offset.' não existe');
      
      $field->setValue($value);
    }
  }
  
  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para verificar se o elemento existe (ex: isset($obj[1]) ).
   */
  public function offsetExists($offset) {
    return isset($this->fields[SQLBase::key($offset)]);
  }

  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para deletar um elemento do objeto (ex: unset($obj[1]) ).
   */
  public function offsetUnset($offset) {
    $this->removeField($offset);
  }

  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para retornar o valor de um elemento existente (ex: $var = $obj[1] ).
   */
  public function offsetGet($offset) {
    if (is_numeric($offset)) {
      // TODO: procurar uma função ou metodo mais eficiente de busca (talvez busca binaria)
      $counter = 0;
      foreach ($this->fields as $val) {
        if ($counter == $offset) {
          return $val;
        }
        ++$counter;
      }
      return null;
    } else {
      return $this->fields[SQLBase::key($offset)];
      //return isset($this->fields[strtolower($offset)]) ? $this->fields[strtolower($offset)] : null;
    }
  }
  
  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para contar os elementos do array (ex: count($var) )
   */
  public function count() {
    return count($this->fields);
  }
  // --------------------- FIM DOS METODOS DE ACESSO POR ARRAY ----
  
  function __get($name) {
    
    if (isset($this->fields[$name])) {
      return $this->fields[$name];
    }
    
    switch ($name) {
      case 'Columns':
      case 'Fields':
        return $this->fields;
      case 'Entity':
      case 'Table':
        return $this->entity;
      case 'TableFields':
      case 'EntityFields':
        $table = $this->entity;
        if (!($table instanceof SQLEntityBase))
          throw new SQLException('Tabela ou Join não definido');
        return $table->Fields;
      default:
        throw new SQLException('Propriedade '.$name.' não existe');
    }
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
      $sSel .= SQLBase::parseValue($field->getValue());
    }
    $s .= $sSel;
    $s .= ')';
    
    if ($this->defs) {
      $s .= ' '.$this->defs;
    }
    
    return $s;
  }  
}