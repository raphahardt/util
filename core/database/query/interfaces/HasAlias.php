<?php

namespace Djck\database\query\interfaces;

/**
 * Classes que possuem alias.
 */
interface HasAlias {
  /**
   * Retorna o alias.
   * 
   * @return string
   */
  function getAlias();
  /**
   * Define um alias.
   * 
   * @param string $alias
   */
  function setAlias($alias);
  /**
   * Retorna o alias, caso o objeto tenha, ou o nome real dele.
   * 
   * @return string
   */
  function toAlias();
}