<?php

namespace Djck\database\query\interfaces;

/**
 * Classes que podem ser negadas ou não.
 * 
 * Útil em expressões complexas.
 */
interface Negable {
  /**
   * Retorna se a expressão está negada.
   * 
   * @return boolean
   */
  function getNegate();
  /**
   * Define a expressão como negada.
   * 
   * @param boolean $neg
   */
  function setNegate($neg);
}