<?php

/**
 * Sistema de ijfisdojfs
 */

require 'core/bootstrap.php';

Core::uses('Cte', 'model/testes');

$cliente = new Cte();
$expression = new SQLExpression('AND', new SQLCriteria($cliente->modelocte, '=', 1200));


function validateExpression($data, SQLExpression $expression) {
  
  $operator = $expression->getOperator();
  $expressions = $expression->get();
  
  if ($operator == 'OR') {
    $return = false;
  } else {
    $return = true;
  }
  foreach ($expressions as $e) {
    if ($e instanceof SQLExpression) {
      if ($operator == 'OR') {
        $return || $return = validateExpression($data, $e);
      } else {
        $return && $return = validateExpression($data, $e);
      }
    } else {
      $field = $e->getField();
      $op = $e->getOperator();
      $value = $e->getValue();
      if ($value instanceof SQLField) {
        $value = $value->getValue();
      }
      switch ($op) {
        case '=':
          $validate_criteria = $data[ $field->getAlias() ] == $value;
          break;
        case '!=':
        case '<>':
          $validate_criteria = $data[ $field->getAlias() ] != $value;
          break;
        case '>':
          $validate_criteria = $data[ $field->getAlias() ] > $value;
          break;
        case '<':
          $validate_criteria = $data[ $field->getAlias() ] < $value;
          break;
        case '>=':
          $validate_criteria = $data[ $field->getAlias() ] >= $value;
          break;
        case '<=':
          $validate_criteria = $data[ $field->getAlias() ] <= $value;
          break;
        default:
          $validate_criteria = false;
      }
      if ($operator == 'OR') {
        $return || $return = $validate_criteria;
      } else {
        $return && $return = $validate_criteria;
      }
    }
  }
  return $return;
  
}

$data = array();
$data[] = array('modelocte' => 4234);
$data[] = array('modelocte' => 26456);
$data[] = array('modelocte' => 123);
$data[] = array('modelocte' => 545);
$data[] = array('modelocte' => 15616);
$data[] = array('modelocte' => 51);
$data[] = array('modelocte' => 42315614);
$data[] = array('modelocte' => 7897);
$data[] = array('modelocte' => 7);

foreach ($data as $i => $d) {
  if (!validateExpression($d, $expression)) {
    unset($data[$i]);
  }
}
//$data = array_filter($data, 'validateExpression');

dump($data);