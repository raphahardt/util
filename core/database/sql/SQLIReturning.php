<?php

fwimport('sql.SQLInstructionBase');

class SQLIReturning extends SQLInstructionBase implements SQLArrayAccess {
  
  private $fields = array();
  private $into = array();
  
  protected function hash() {
    return SQLBase::key(__CLASS__.self::$_obj_id++);
  }
  
  public function __construct($fields, $into, $defs = null) { 
    parent::__construct();
    $this->build($fields, $into, $defs);
  }
  
  public function build($fields, $into, $defs = null) { 
    if (!is_array($fields)) $fields = array($fields);
    if (!is_array($into)) $into = array($into);
    
    if (count($fields) != count($into)) {
      throw new SQLException('O número de campos do RETURNING deve ter o mesmo número '.
              'de campos do INTO');
    }
    
    $this->fields = $fields;
    $this->into = $into;
    $this->filter = null;
    $this->defs = $defs;
  }
  
  public function destroy() {
    $this->fields = array();
    $this->into = array();
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
    return $this->getFields();
  }
  
  public function getFields() {
    return $this->fields;
  }
  
  public function getIntos() {
    return $this->into;
  }
    
  // --------------------- INICIO DOS METODOS DE ACESSO POR ARRAY ----
  
  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para adicionar um valor ao objeto (ex: $obj[] = 'valor').
   */
  public function offsetSet($offset, $value) {
    // nada precisa realmente acontecer
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
    switch ($name) {
      case 'Columns':
      case 'Fields':
        return $this->fields;
      default:
        throw new SQLException('Propriedade '.$name.' não existe');
    }
  }
  
  public function __toString() {
    
    if (empty($this->fields)) {
      return '';
    }
    
    $s = 'RETURNING ';
    $s .= implode(', ', $this->fields);
    $s .= ' INTO ';
    
    $sSel = '';
    foreach ($this->fields as $field) {
      $sSel .= ( empty($sSel) ? '' : ', ' );
      /*$sSel .= SQLBase::parseValue($field->getValue());*/
      $name = ':into_'.$field->getHash();

      $sSel .= $name;
      $this->addBind($name, null);
    }
    $s .= $sSel;
    
    return $s;
  }  
}