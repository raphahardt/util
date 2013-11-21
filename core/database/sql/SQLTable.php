<?php

/**
 * 
 */
class SQLTable extends SQLEntityBase implements ISQLAliasable, SQLArrayAccess {
  
  private $table;
  private $schema;
  private $fields = array();
  
  // para implementar
  private $partition; // é um objeto sqlexpression ex: FROM table PARTITION (expressao)
  
  protected function hash() {
    return SQLBase::key($this->getAlias());
  }

  public function __construct($name, $alias = null, $schema = null) {
    $this->build($name, $alias, $schema);
    parent::__construct();
  }
  
  public function build($name, $alias = null, $schema = null) {
    $this->table = $name;
    $this->alias = $alias;
    $this->schema = $schema;
  }
  
  public function destroy() {
    $this->table = $this->alias = $this->schema = null;
    // apaga os objetos antes de zerar a propriedade
    foreach ($this->fields as $e) {
      unset($e); // apaga os objetos
    }
    $this->fields = array();
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
      $this->addField($f);
    }
    
  }
  
  public function addField($name, $alias = null, $type = null) {
    if ($name instanceof SQLBase) {
      if (!($name instanceof SQLField)) 
        throw new SQLException('O método '.__METHOD__.' só aceita SQLField como parametro');
      
      $field = $name;
    } else {
      $field = new SQLField($name, $alias, $type);
    }
    
    // define quem é o pai do campo
    //if ($name instanceof SQLField)
    $field->setTable($this);
    
    // aux para inserir o campo sem repetir
    $this->fields[ $field->getHash() ] = $field;
  }
  
  public function addFields() {
    $this->callMethod('add', func_get_args());
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
//    if (is_array($this->fields) && !empty($this->fields)) {
//      if ($name instanceof SQLField || $name instanceof SQLFieldComplex)
//        $name = $name->toKey();
//      else
//        $name = SQLBase::key($name);
//      
//      foreach ($this->fields as $key => $field) {
//        if ($name === $key) {
//          unset($this->fields[ $key ]);
//          return;
//        }
//      }
//    }
  }
  
  public function get() {
    return $this->getFields();
  }
  
  public function getField($offset) {
    return $this->fields[SQLBase::key($offset)];
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
      case 'Fields':
        return $this->fields;
      default:
        throw new SQLException('Propriedade '.$name.' não existe');
    }
  }
  
  public function setAlias($alias) {
    $this->alias = $alias;
  }
  
  public function getAlias() {
    return $this->alias ? $this->alias : $this->table;
  }
  
  public function toAlias() {
    if ($this->alias) {
      return $this->alias;
    } else {
      return (string)$this;
    }
  }

  function __toString() {
    $s = $this->table;
    if ($this->schema)
      $s = $this->schema . '.'.$s;
    return $s;
  }
  
}