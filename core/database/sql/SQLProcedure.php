<?php

/**
 * Campo de uma tabela.
 */
class SQLProcedure extends SQLBase implements SQLArrayAccess {
  
  private $name;
  private $schema;
  private $params = array();
  
  protected function hash() {
    return SQLBase::key(__CLASS__.self::$_obj_id++);
  }

  public function __construct($name, $schema = null) {
    $this->build($name, $schema);
    parent::__construct();
  }
  
  public function build($name, $schema = null) {
    $this->name = $name;
    $this->schema = $schema;
  }
  
  public function destroy() {
    $this->name = null;
    $this->schema = null;
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
      $this->addParam($f);
    }
    
  }
  
  public function addParam($name, $type = null) {
    if ($name instanceof SQLBase) {
      if (!($name instanceof SQLParam)) 
        throw new SQLException('O método '.__METHOD__.' só aceita SQLParam como parametro');
      
      $param = $name;
    } else {
      $param = new SQLParam($name, $type);
    }
    
    // aux para inserir o campo sem repetir
    $this->params[ $param->getHash() ] = $param;
  }
  
  public function addParams() {
    $this->callMethod('add', func_get_args());
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
    return $this->getParams();
  }
  
  public function getParams() {
    return $this->params;
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function getValue() {
    // TODO: escapar os valores devidamente
    return $this->data;
  }
  
  public function setName($f) {
    $this->name = $f;
  }
  
  public function setValue($val) {
    $this->data = $val;
  }
  
  function setType($type) {
    $this->type = strtoupper($type) == 'OUT' ? 'OUT' : 'IN';
  }
  
  function getType() {
    return $this->type;
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
      $param = $this->params[ SQLBase::key($offset) ];
      if (!($param instanceof SQLParam))
        throw new SQLException('Campo '.$offset.' não existe');
      
      $param->setValue($value);
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
    $this->removeParam($offset);
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
      default:
        throw new SQLException('Propriedade '.$name.' não existe');
    }
  }
  
  function __toString() {
    
    $s = $this->name;
    if ($this->schema)
      $s = $this->schema . '.'.$s;
    return $s;
  }
  
}