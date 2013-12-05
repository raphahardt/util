<?php

namespace Djck\database\query;

use Djck\Core;

Core::uses('ExpressionBase', 'Djck\database\query\base');

/**
 * Description of Expression
 *
 * @author Rapha e Dani
 */
class Expression 
extends base\ExpressionBase 
implements base\HasAlias, base\HasOperator, 
        base\HasFunction, base\Ordenable {
  
  protected $expressees = array();
  protected $functions = array();
  protected $showFunctions = true;
  protected $alias;
  protected $orderDesc = false;
  
  /**
   * Cria uma expressão para filtragem de registros de um Model (ou Mapper)
   * 
   * @param string $operator Operador de ligação a ser usada na expressão. Pode ser OR ou AND
   * @param string|base\Expressee $alias Alias para expressão (opcional)
   * @param base\Expressee $expr Uma ou mais subexpressões que serão ligadas pelo operador
   * @param base\Expressee $expr2 ... 
   * @throws base\QueryException
   */
  public function __construct($operator, $alias = null, $expr = null) {
    $args = func_get_args();
    
    // validando operador
    if (!is_string($operator)) {
      throw new base\QueryException('Defina um operador para a expressão');
    }
    // primeiro definir o alias, para gerar o hash corretamente
    if (!($alias instanceof base\Expressee) && !is_array($alias)) {
      $this->alias = $alias;
      if ($alias !== null) {
        unset($args[1]);
      }
    }
    
    // depois chama o construct do parent, que cria o hash (entre outros)
    parent::__construct();
    
    // define a expressao
    array_shift($args);
    
    // adiciona os elementos no array interno
    $this->callMethod('add', $args);
    $this->setOperator($operator); // TODO: validacao dos operadores possiveis
    
  }
  
  /**
   * Adiciona subexpressões a expressão atual.
   * 
   * @param base\Expressee|scalar $expr Uma ou mais subexpressões a serem adicionadas
   * @param base\Expressee|scalar $expr2 ...
   * @throws base\QueryException
   */
  public function add($expr, $expr_ = null) {
    $expressions = func_get_args();
    
    foreach ($expressions as $expression) {
      if ($expression instanceof base\Base) {
        if (!($expression instanceof base\Expressee)) {
          throw new base\QueryException('O método '.__METHOD__.' só aceita ISQLExpressions'.
                                ' ou tipos comuns (string, int...) como parametro');
        }
        
        // se a expressao que estiver adicionando for o mesmo que o objeto instanciado,
        // não adicionar, pois irá gerar recursão e entrar em loop infinito
        if ($expression instanceof Expression && $expression === $this) {
          throw new base\QueryException('Você não pode colocar como subexpressão a própria '.
                                  'instancia de expressão');
        }
        
        // adicionando um objeto do tipo ISQLExpression
        $this->expressees[ $expression->getHash() ] = $expression;
      } else {
        
        // adicionando uma variavel comum (string, int...) no array
        $this->expressees[] = $expression;
      }
    }
  }
  
  public function getExpressees() {
    return $this->expressees;
  }
  
  // === base\HasAlias ===
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
  
  public function getFunction() {
    $last_function = end($this->functions);
    return $last_function['name'];
  }


  public function setFunction($func, $params = array()) {
    $args = func_get_args();
    array_shift($args);
    
    $function_params = array();
    
    if (count($args) > 1) {
      $function_params = $args;
    } elseif (count($args) == 1) {
      if (is_array($params)) {
        $function_params = $params;
      } else {
        $function_params[] = $params;
      }
    }
    
    $this->functions[] = array( 'name' => $func, 'params' => $function_params );
  }
  
  public function unsetFunction($func = null) {
    if (!$func) {
      $this->functions = array();
      return;
    }
    foreach ($this->functions as $index => $func) {
      if ($func['name'] == $func) {
        unset($this->functions[$index]);
      }
    }
  }
  
  public function showFunctions($bool) {
    $this->showFunctions = (bool)$bool;
  }
  
  
  
  public function __toString() {
    if (empty($this->expressees)) {
      return '';
    }
    
    $s = '';
    foreach ($this->expressees as $exp) {
      
      $s .= ( empty($s) ? '' : ' '.$this->operator.' ' );
      
      if ($exp instanceof base\Negable) {
        $s .= (($exp->getNegate() == true) ? ' NOT ':'');
      }
      
      if ($exp instanceof base\Base) {
        $s .= (string)$exp;
      } else {
        $s .= self::parseValue($exp);
      }
    }
    
    if ($this->showFunctions && $this->functions) {
      foreach ($this->functions as $func) {
        $s = $func['name'] . '('.$s;
        
        if (!empty($func['params'])) {
          array_walk($func['params'], array(self, 'parseValueWalk'));
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
