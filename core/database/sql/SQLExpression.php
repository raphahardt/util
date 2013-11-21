<?php

/**
 * Expressão SQL.
 */
class SQLExpression extends SQLExpressionBase implements ISQLAliasable,ISQLOperationable,ISQLFunctionable,ISQLOrdenable, ISQLExpressions {
  
  protected $expressees = array();
  protected $functions = array();
  protected $showFunctions = true;
  protected $alias;
  protected $orderDesc = false;
  
  protected function hash() {
    // TODO: mudar pra alias
    return SQLBase::key(__CLASS__.self::$_obj_id++);
  }
  
  public function __construct($operator, $alias, $expr = null) {
    $args = func_get_args();
    // primeiro definir o alias, para gerar o hash corretamente
    if (!($alias instanceof ISQLExpressions) && !is_array($alias)) {
      $this->alias = $alias;
      unset($args[1]);
    }
    
    // depois chama o construct do parent, que cria o hash (entre outros)
    parent::__construct();
    
    $this->callMethod('build', $args);
  }
  
  public function build($operator, $expr = null) {
    $args = func_get_args();
    array_shift($args);
    
    // adiciona os elementos no array interno
    $this->destroy();
    $this->callMethod('add', $args);
    $this->setOperator($operator); // TODO: validacao dos operadores possiveis
    
    if (!$this->operator)
      throw new SQLException('Operador '.$operator.' invalido');
  }
  
  public function destroy() {
    // apaga os objetos antes de zerar a propriedade
    foreach ($this->expressees as $e) {
      unset($e); // apaga os objetos
    }
    $this->expressees = array();
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
      if (is_object($f)) {
        if (!($f instanceof ISQLExpressions)) 
          throw new SQLException('O método '.__METHOD__.' só aceita ISQLExpressions'.
                                ' ou tipos comuns (string, int...) como parametro');
        
        // se a expressao que estiver adicionando for o mesmo que o objeto instanciado,
        // não adicionar, pois irá gerar recursão e entrar em loop infinito
        if ($f instanceof SQLExpression && $f->getHash() === $this->getHash()) {
          throw new SQLException('Você não pode colocar como subexpressão a própria '.
                                  'instancia de expressão');
        }
        
        // adicionando um objeto do tipo ISQLExpression
        $this->expressees[ $f->getHash() ] = $f;
      } else {
        
        // adicionando uma variavel comum (string, int...) no array
        $this->expressees[] = $f;
      }
    }
    
  }
  
  public function addField(SQLField $obj) {
    $this->add($obj);
  }
  
  public function addCriteria(SQLCriteria $obj) {
    $this->add($obj);
  }
  
  public function addSubExpression(SQLExpression $obj) {
    $this->add($obj);
  }
  public function addExpression(SQLExpression $obj) {
    $this->addSubExpression($obj);
  }
  
  public function addConditional(SQLConditional $obj) {
    $this->add($obj);
  }
  
  public function addInstruction(SQLISelect $obj) {
    $this->add($obj);
  }
  
  public function remove($hash) {
    if ($hash instanceof SQLBase)
      $hash = $hash->getHash();
    
    unset($this->expressees[ $hash ]);
  }
  
  public function get() {
    return $this->expressees;
  }
  
  public function getFields() {
    $f = create_function('$v', 'return ($v instanceof SQLField);');
    return array_filter($this->expressees, $f);
  }
  
  public function getCriterias() {
    $f = create_function('$v', 'return ($v instanceof SQLCriteria);');
    return array_filter($this->expressees, $f);
  }
  
  public function getSubExpressions() {
    $f = create_function('$v', 'return ($v instanceof SQLExpression);');
    return array_filter($this->expressees, $f);
  }
  
  public function getExpressions() {
    return $this->getSubExpressions();
  }
  
  public function getConditionals() {
    $f = create_function('$v', 'return ($v instanceof SQLConditional);');
    return array_filter($this->expressees, $f);
  }
  
  public function getInstructions() {
    $f = create_function('$v', 'return ($v instanceof SQLISelect);');
    return array_filter($this->expressees, $f);
  }
  
  public function setAlias($alias) {
    $this->alias = $alias;
  }
  
  public function getAlias() {
    return $this->alias;
  }
  
  public function toAlias() {
    if ($this->alias) {
      return $this->alias;
    } else {
      return (string)$this; // toString
    }
  }
  
  public function setOrder($order) {
    $this->orderDesc = (strtoupper($order) == 'DESC' ? true : false );
  }
  
  public function getOrder() {
    return ($this->orderDesc ? 'DESC' : 'ASC');
  }
  
  public function setFunction($func, $params = null) {
    $args = func_get_args();
    array_shift($args);
    
    $functionParams = array();
    
    if (count($args) > 1) {
      $functionParams = $args;
    } elseif (count($args) == 1) {
      if (is_array($params)) {
        $functionParams = $params;
      } else {
        $functionParams[] = $params;
      }
    }
    
    $this->functions[] = array( 'name' => $func, 'params' => $functionParams );
  }
  
  public function getFunction() {
    $lfunc = end($this->functions);
    return $lfunc['name'];
  }
  
  public function showFunctions($bool) {
    $this->showFunctions = (bool)$bool;
  }
  
  public function __toString() {
    if (empty($this->expressees))
      return '';
    
    $s = '';
    foreach ($this->expressees as $exp) {
      
      $s .= ( empty($s) ? '' : ' '.$this->operator.' ' );
      
      if ($exp instanceof ISQLNegable) {
        $s .= (($exp->getNegate() == true) ? ' NOT ':'');
      }
      if ($exp instanceof SQLBase) {
        $s .= (string)$exp;
      } else {
        $s .= SQLBase::parseValue($exp);
      }
    }
    
    if ($this->showFunctions && $this->functions) {
      foreach ($this->functions as $func) {
        $s = $func['name'] . '('.$s;
        
        if (!empty($func['params'])) {
          array_walk($func['params'], 'SQLBase::parseValueIterator');
          $s .= ', '.implode(', ', $func['params']);
        }
        $s .= ')';
      }
    } else {
      $s = '('.$s.')';
    }
    
    return $s;
    
  }  
}

function _e($operator, $alias, $expr = null) {
  $args = func_get_args();
  array_shift($args);
  array_shift($args);
  
  return new SQLExpression($operator, $alias, $args);
}