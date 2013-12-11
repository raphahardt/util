<?php

namespace Djck\mvc\interfaces;

/**
 * Interface que define todos os Mappers. É o padrão.
 */
interface CommonMapper {
  public function init();
  public function commit();
  public function rollback();
}