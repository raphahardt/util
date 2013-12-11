<?php

namespace Djck\database\query\base;

use Djck\Core;
use Djck\database\query\exceptions;
use Djck\database\query\interfaces;

Core::uses('Base', 'Djck\database\query\base');

/**
 * Description of ExpressionBase
 *
 * @author Rapha e Dani
 */
abstract class ExpressionBase 
extends Base 
implements interfaces\Negable, interfaces\HasOperator, interfaces\Expressee {
  
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
    
    throw new exceptions\QueryException('Operador "'.$operator.'" inválido');
  }
  
}
