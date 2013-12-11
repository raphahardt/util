<?php

namespace Djck\database\query\interfaces;

/**
 * Classes que podem ser envolvidas em uma função.
 * 
 * Útil para campos que precisam ser aplicados funções diretamente na query.
 * 
 */
interface HasFunction {
  /**
   * Retorna as funções definidas.
   * 
   * @return array
   */
  function getFunction();
  /**
   * Define uma função para o objeto.
   * 
   * @param string $function Nome da função
   * @param mixed[] $params Um array de parametros
   */
  function setFunction($function, $params = null);
  /**
   * Define se as funções serão mostradas na string ou não.
   * 
   * @param boolean $bool
   */
  function showFunctions($bool);
}