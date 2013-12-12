<?php

namespace Djck\mvc\mappers;

use Djck\mvc\Mapper;
use Djck\mvc\interfaces;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TempMapper
 *
 * @author usuario
 */
class TempMapper extends Mapper implements interfaces\CommonMapper {
  
  protected $autoincrement = 1;
  
  protected function autoIncrement() {
    // os mappers temporarios não tem problema de persistencia, portanto
    // não precisam de se preocuparem em serem unicos, apenas terem id sequencial
    return $this->autoincrement++;
  }
  
  public function init() {
    
  }  
}