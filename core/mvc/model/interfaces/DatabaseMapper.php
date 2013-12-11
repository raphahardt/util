<?php

namespace Djck\mvc\interfaces;

/**
 * Interface que define todos os Mappers que tem ligação com banco de dados.
 * Ela contem mais 4 métodos para a conversação correta com os dados: select, update,
 * delete e insert. Os Behaviors irão verificar se o Mapper implementa essa interface
 * e utilizar os metodos corretos em cada momento.
 * 
 * Changelog
 * ---------
 * 11/12/2013 - Agora todos os Mappers tem suporte a select(), update(), delete() e insert()
 * pois todos serão uma abstração deste modo de manipulação de dados. 
 * 
 */
interface DatabaseMapper extends CommonMapper {
  
}