<?php

namespace Djck\mvc\interfaces;

/**
 * Interface que define todos os Mappers. É o padrão.
 * 
 * Changelog
 * ---------
 * 11/12/2013 - Agora todos os Mappers tem suporte a select(), update(), delete() e insert()
 * pois todos serão uma abstração deste modo de manipulação de dados. Também foi incorporado
 * ao Mapper o uso das classes database\query\* para filtragem, ordernação e muitas
 * outras coisas.
 * 
 */
interface CommonMapper {
  public function init();
  public function commit();
  public function rollback();
  
  public function select();
  public function update();
  public function delete();
  public function insert();
}