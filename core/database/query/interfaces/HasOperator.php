<?php

namespace Djck\database\query\interfaces;

/**
 * Classes que possuem operadores de ligação.
 * 
 * Para expressões lógicas, podem ser AND ou OR.
 * Para expressões comparativas, podem ser =, !=, >, <, etc...
 * Para expressões matemáticas, podem ser +, -, *, /...
 */
interface HasOperator {
  /**
   * Retorna o operador de ligação da expressão.
   * 
   * @return string
   */
  function getOperator();
  /**
   * Define um operador de ligação para expressão.
   * 
   * @param string $operator
   */
  function setOperator($operator);
}