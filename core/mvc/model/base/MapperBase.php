<?php

namespace Djck\mvc\base;

use Djck\Core;
use Djck\system\AbstractObject;
use Djck\database\query;

use Djck\mvc\exceptions;

abstract class MapperBase extends AbstractObject {
  
  /**
   * Função auxiliar para comparação de valores (usado no _quicksort)
   * @param type $val1
   * @param type $val2
   * @return type
   */
  protected function _compare($val1, $val2) {
    if (is_string($val1) && is_string($val2)) {
      return strnatcasecmp($val1, $val2);
    }
    return $val1 < $val2 ? -1 : ($val1 > $val2 ? 1 : 0);
  }
  
  /**
   * Funçao auxiliar que usa o algoritmo QuickSort para ordernar os registros do result
   * @param type $col
   * @param type $left
   * @param type $right
   * @param type $inverse
   */
  protected function _quicksort(&$array, $col, $left, $right, $inverse = false) {
    $i = $left;
    $j = $right;
    $pivot = (int)(($i + $j) / 2);
    $val_pivot = $array[$pivot]['data'][$col];
    while ($i < $j) {
      if ($inverse) {
        while ($this->_compare($array[$i]['data'][$col], $val_pivot) > 0) { // menor
          ++$i;
        }
        while ($this->_compare($array[$j]['data'][$col], $val_pivot) < 0) { // maior
          --$j;
        }
      } else {
        while ($this->_compare($array[$i]['data'][$col], $val_pivot) < 0) { // menor
          ++$i;
        }
        while ($this->_compare($array[$j]['data'][$col], $val_pivot) > 0) { // maior
          --$j;
        }
      }
      if ($i <= $j) {
        $aux = $array[$i];
        $array[$i] = $array[$j];
        $array[$j] = $aux;
        ++$i;
        --$j;
      }
    }
    if ($j > $left) $this->_quicksort($array, $col, $left, $j, $inverse);
    if ($i < $right) $this->_quicksort($array, $col, $i, $right, $inverse);
  }
  
  /**
   * Função auxiliar que seleciona apenas as colunas do $array_default com os valores do 
   * $array (só os que os dois tiverem em comum
   * @param array $array_default
   * @param array $array
   * @return array
   */
  protected function _diff($array_default, $array) {
    $diff = array_diff_key($array, $array_default);
    $result = array_merge($array_default, $array);
    return array_diff_key($result, $diff);
  }
  
  /**
   * Função auxiliar que valida um critério como se fosse um parser de banco de dados.
   * 
   * Serve para mappers que não são DatabaseMapperInterface.
   * 
   * @param array $data Dados a serem testados
   * @param query\Field $field
   * @param string $operator
   * @param mixed|query\Field $value
   * @return boolean
   */
  protected function _evalCriteria($data, $field, $operator, $value) {
    $comp1 = $data[ $field->getAlias() ];
    if ($value instanceof query\Field) {
      $comp2 = $data[ $value->getAlias() ];
    } else {
      $comp2 = $value;
    }
    switch ($operator) {
      case '=':
        return $comp1 == $comp2; // comparação normal == porque banco também faz assim
      case '!=':
      case '<>':
        return $comp1 != $comp2;
      case '>':
        return $comp1 > $comp2;
      case '<':
        return $comp1 < $comp2;
      case '>=':
        return $comp1 >= $comp2;
      case '<=':
        return $comp1 <= $comp2;
      // TODO: fazer LIKE, REGEXP, BETWEEN, etc...
    }
    return false;
  }
  
  /**
   * Função auxiliar que valida um critério como se fosse um parser de banco de dados.
   * 
   * Serve para mappers que não são DatabaseMapperInterface.
   * 
   * @param type $data Dados a serem testados
   * @param \Djck\database\query\Expression $expression
   * @return boolean
   */
  protected function _evalExpression($data, query\Expression $expression) {
    
    // pega o operador e as subexpressoes
    $operator = $expression->getOperator();
    $expressions = $expression->getExpressees();

    // definindo elemento neutro inicial
    // se o operador for OR, começar o resultado com FALSE (0 | teste = teste)
    // se não (AND), começar com TRUE (1 & teste = teste)
    if ($operator == 'OR') {
      $result = false;
    } else {
      $result = true;
    }
    // corre por cada subexpressao
    foreach ($expressions as $e) {
      // se o elemento for outra expressão, recursivamente testa-las
      if ($e instanceof query\Expression) {
        // mesma logica do elemento neutro acima
        if ($operator == 'OR') {
          $result || $result = $this->_evalExpression($data, $e);
        } else {
          $result && $result = $this->_evalExpression($data, $e);
        }
      } else {
        // se chegou até aqui, é pq é um criteria, e deve ser testado
        $result_criteria = $this->_evalCriteria($data, 
                $e->getField(), $e->getOperator(), $e->getValue());
        
        if ($operator == 'OR') {
          $result || $result = $result_criteria;
        } else {
          $result && $result = $result_criteria;
        }
      }
    }
    return $result;
  }
  
  /**
   * Função auxiliar que valida um critério como se fosse um parser de banco de dados.
   * 
   * Serve para mappers que não são DatabaseMapperInterface.
   * 
   * @param type $data Dados a serem testados
   * @param \Djck\database\query\Expression $expression
   * @return boolean
   */
  protected function _evalMathExpression($data, query\Expression $expression) {
    
    // pega o operador e as subexpressoes
    $operator = $expression->getOperator();
    
    if (!in_array($operator, array('+', '-', '*', '/', '%'))) {
      throw new exceptions\MapperInvalidPropertyException('O valor deve ser obrigatóriamente uma expressão aritmética.');
    }
    
    $expressions = $expression->getExpressees();

    // corre por cada subexpressao
    $result = null;
    foreach ($expressions as $e) {
      
      if ($e instanceof query\Expression) {
        // se o elemento for outra expressão, recursivamente testa-las
        $result_math = $this->_evalMathExpression($data, $e);
      } elseif ($e instanceof query\Field) {
        $result_math = $data[ $e->getAlias() ];
      } elseif (is_scalar($e)) {
        $result_math = $e;
      } else {
        throw new exceptions\MapperInvalidPropertyException('O valor deve ser ou um campo do mapper ou um escalar.');
      }

      switch ($operator) {
        case '+':
          if ($result === null) $result = 0;
          $result += $result_math;
          break;
        case '-':
          if ($result === null) {
            $result = $result_math;
          } else {
            $result -= $result_math;
          }
          break;
        case '*':
          if ($result === null) $result = 1;
          $result *= $result_math;
          break;
        case '/':
          if ($result === null) {
            $result = $result_math;
          } else {
            $result /= $result_math;
          }
          break;
        case '%':
          if ($result === null) {
            $result = $result_math;
          } else {
            $result %= $result_math;
          }
          break;
      }
    }
    return $result;
  }
  
}