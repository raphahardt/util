<?php

namespace Djck\database\query\base;

use Djck\Core;

Core::uses('Base', 'Djck\database\query\base');

/**
 * Description of ExpressionBase
 *
 * @author Rapha e Dani
 */
abstract class ExpressionBase extends Base implements Negable, HasOperator, Expressee {
  
  protected $operator;
  protected $negate = false;
  
  public function setOperator($op) {
    $this->operator = $this->_validateOperator($op);
  }
  
  public function getOperator() {
    return $this->operator;
  }
  
  public function setNegate($neg) {
    $this->negate = (bool)$neg;
  }
  
  public function getNegate() {
    return $this->negate;
  }
  
  protected function _validateOperator($operator) {
    // TODO: passar essa validação pro SQLBase
    $operator = strtoupper($operator);
    
    $validOperators = self::$validOperators;
    
    if ( isset($validOperators['math'][$operator]) ||
            isset($validOperators['logic'][$operator]) || 
            isset($validOperators['concat'][$operator]) ) {
      return $operator;
    }
    
    throw new QueryException('Operador "'.$operator.'" inválido');
  }
  
}
