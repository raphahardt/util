<?php

namespace Djck\database\query\interfaces;

/**
 * Classes, geralmente campos (Field), que podem ser ordenadas num SELECT
 */
interface Ordenable {
  /**
   * Retorna o campo e sua ordenação (asc ou desc)
   * 
   * @return array
   */
  function getOrder();
  /**
   * Define uma ordenação relativo ao campo.
   * 
   * @param array $order
   */
  function setOrder($order);
}