<?php

class SQLICall extends SQLInstructionBase implements SQLArrayAccess {
  
  private $params = array();
  private $procedure;
  
  protected function hash() {
    return SQLBase::key(__CLASS__.self::$_obj_id++);
  }
  
  public function __construct(SQLProcedure $procedure, $fields = array()) { 
    parent::__construct();
    $this->build($procedure, $fields);
  }
  
  public function build(SQLProcedure $procedure, $fields = array()) { 
    if (!is_array($fields)) $fields = array($fields);
    
    $this->params = $fields;
    $this->procedure = $procedure;
  }
  
  public function destroy() {
    $this->procedure = null;
    $this->params = array();
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
      if (!($f instanceof SQLParam)) 
        throw new SQLException('O método '.__METHOD__.' só aceita SQLParam como parametro');
      
      $this->params[ $f->getHash() ] = $f;
    }
    
  }
  
  public function addParams() {
    $args = func_get_args();
    $this->callMethod('add', $args);
  }
  
  public function remove($hash) {
    if ($hash instanceof SQLBase)
      $hash = $hash->getHash();
    else
      $hash = SQLBase::key($hash);
    
    unset($this->params[ $hash ]);
  }
  
  public function removeParam($name) {
    $this->remove($name);
  }
  
  public function get() {
    return $this->getTable();
  }
  
  public function getProcedure() {
    return $this->procedure;
  }
  
  public function setProcedure(SQLProcedure $proc) {
    $this->procedure = $proc;
  }
  
  public function getParams() {
    return $this->params;
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
      $field = $this->params[ SQLBase::key($offset) ];
      if (!($field instanceof SQLField))
        throw new SQLException('Parametro '.$offset.' não existe');
      
      $field->setValue($value);
    }
  }
  
  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para verificar se o elemento existe (ex: isset($obj[1]) ).
   */
  public function offsetExists($offset) {
    return isset($this->params[SQLBase::key($offset)]);
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
      foreach ($this->params as $val) {
        if ($counter == $offset) {
          return $val;
        }
        ++$counter;
      }
      return null;
    } else {
      return $this->params[SQLBase::key($offset)];
      //return isset($this->fields[strtolower($offset)]) ? $this->fields[strtolower($offset)] : null;
    }
  }
  
  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para contar os elementos do array (ex: count($var) )
   */
  public function count() {
    return count($this->params);
  }
  // --------------------- FIM DOS METODOS DE ACESSO POR ARRAY ----
  
  function __get($name) {
    switch ($name) {
      case 'Params':
      case 'Parameters':
        return $this->params;
      case 'Entity':
      case 'Procedure':
        return $this->procedure;
      case 'ProcedureParams':
      case 'ProcedureParameters':
        $table = $this->procedure;
        if (!($table instanceof SQLProcedure))
          throw new SQLException('Procedure não definida');
        return $table->Params;
      default:
        throw new SQLException('Propriedade '.$name.' não existe');
    }
  }
  
  public function __toString() {
    
    if (empty($this->params)) {
      return '';
    }
    
    parent::__toString();
    
    $s = 'BEGIN ';
    $s .= $this->procedure;
    $s .= '(';
    
    $sSel = '';
    foreach ($this->params as $param) {
      $sSel .= ( empty($sSel) ? '' : ', ' );
      if ($param->getType() == 'OUT') {
        $name = ':cursor_'.SQLBase::key($param);
        
        $sSel .= $name;
        $this->addBind($name, null);
      } else {
        $sSel .= SQLBase::parseValue($param->getValue());
      }
    }
    $s .= $sSel;
    
    $s .= '); END;';
    
    return $s;
  }  
}