<?php

namespace Djck\mvc\interfaces;

/**
 * Interface que define todos os Mappers que tem ligação com banco de dados.
 * Ela contem mais 4 métodos para a conversação correta com os dados: select, update,
 * delete e insert. Os Behaviors irão verificar se o Mapper implementa essa interface
 * e utilizar os metodos corretos em cada momento.
 */
interface DatabaseMapper extends CommonMapper {
  public function select();
  public function update();
  public function delete();
  public function insert();
}