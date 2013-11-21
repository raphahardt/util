<?php

abstract class SQLExpressionBase extends SQLBase implements ISQLNegable, ISQLOperationable {
  
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
    
    $validOperators = $this->_getValidOperators();
    
    if ( in_array($operator, $validOperators['math']) ||
            in_array($operator, $validOperators['logic']) || 
            in_array($operator, $validOperators['concat']) )
      return $operator;
    
    return false;
  }
  
}