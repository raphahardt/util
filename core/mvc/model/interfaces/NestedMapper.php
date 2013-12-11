<?php

namespace Djck\mvc\interfaces;

/**
 * Interface que define todos os Mappers que precisam manipular seus dados de forma
 * aninhada. Por exemplo, os xmls.
 * Ela tem mais alguns metodos essenciais para essa manipulação. Os Behaviors irão 
 * verificar se o Mapper implementa essa interface
 * e utilizar os metodos corretos em cada momento.
 */
interface NestedMapper extends CommonMapper {
  // addnode, removenode, etc...
}