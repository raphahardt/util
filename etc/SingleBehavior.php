<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SingleBehavior
 *
 * @author usuario
 */
class SingleBehavior extends Behavior {
  
  public $priority = 0;
  
  public function setFields(Model $Model, $fld) {
    $args = func_get_args();
    array_shift($args); // model
    
    $fields = array();
    if (count($args) > 1) {
      $fields = $args;
    } elseif (count($args) == 1) {
      $fld = $args[0];
      if (is_array($fld)) {
        $fields = $fld;
      } else {
        $fields[] = $fld;
      }
    }
    
    $Model->Mapper->setFields($fields);
  }
  
  public function setFilter(Model $Model, $cons) {
    $args = func_get_args();
    array_shift($args); // model
    
    $constraints = array();
    if (count($args) > 1) {
      $constraints = $args;
      
      // alias para filter: 
      // setFilter(campo, sinal, valor) 
      // em vez de 
      // setFilter(new SQLCriteria(campo, sinal, valor))
      if (count($args) == 3 && is_string($args[1])) {
        $constraints = array(new SQLCriteria($args[0], $args[1], $args[2]));
      }
    } elseif (count($args) == 1) {
      $cons = $args[0];
      if (is_array($cons)) {
        $constraints = $cons;
      } else {
        $constraints[] = $cons;
      }
    }
    
    $Model->Mapper->setFilter($constraints);
  }
  
  public function select(Model $Model) {
    // se o model também é um collection, chamar o metodo dele sempre primeiro 
    // em vez do single. isso permite que não importa a ordem dos behaviors, o collection
    // sempre terá precedencia no select
    if ($Model->is('Collection')) {
      return $Model->uses('Collection')->select();
    }
    // se for database, faz select no banco primeiro
    if ($Model->Mapper instanceOf DatabaseItfMapper) {
      $Model->Mapper->select();
    }
    // só atualiza o registro atual se encontrar apenas 1 registro
    if ($Model->Mapper->count() != 1) {
      $Model->Mapper->nullset();
      $Model->Mapper->clearResult();
      return false;
    } else {
      $Model->Mapper->first();
    }
    return true;
  }
  
  public function insert(Model $Model) {
    // se o model também é um collection, chamar o metodo dele sempre primeiro 
    // em vez do single. isso permite que não importa a ordem dos behaviors, o collection
    // sempre terá precedencia no select
    if ($Model->is('Collection')) {
      return $Model->uses('Collection')->insert();
    }
    if ($Model->Mapper instanceOf DatabaseItfMapper) {
      $return = $Model->Mapper->insertOne();
    } elseif ($Model->Mapper instanceof DefaultItfMapper) {
      $Model->Mapper->push();
      $return = $Model->Mapper->commit();
    }
    return $return;
  }
  
  public function update(Model $Model) {
    if ($Model->Mapper instanceOf DatabaseItfMapper) {
      $return = $Model->Mapper->update();
    } elseif ($Model->Mapper instanceof DefaultItfMapper) {
      $Model->Mapper->refresh();
      $return = $Model->Mapper->commit();
    }
    return $return;
  }
  
  public function delete(Model $Model) {
    // se o model também é um collection, chamar o metodo dele sempre primeiro 
    // em vez do single. isso permite que não importa a ordem dos behaviors, o collection
    // sempre terá precedencia no select
    if ($Model->is('Collection')) {
      return $Model->uses('Collection')->delete();
    }
    // todos mappers tem essa funcao, e nos de database, ele apaga sem alterar o result,
    // diferente do delete() do dbcmapper
    //$return = $Model->Mapper->remove();  DÁ PROBLEMA QUANDO UM REG ATUAL NÃO TEM SETADO O ID DIRETO NO CAMPO
    //                                    , SÓ NO FILTER. ARRUMAR DEPOIS
    $return = $Model->Mapper->delete(); 
    return true;
  }
  
  public function getArray(Model $Model) {
    if ($Model->is('Collection')) {
      return $Model->uses('Collection')->getArray();
    }
    return $Model->Mapper->getData();
  }
  
  public function clear(Model $Model) {
    return $Model->Mapper->nullset();
  }
  
  public function startTransaction(Model $Model) {
    if ($Model->Mapper instanceof BDMapper) {
      $bd = BD::getInstance();
      $bd->autocommit(false);
    }
  }
  
  public function endTransaction(Model $Model, $success = false) {
    if ($Model->Mapper instanceof BDMapper) {
      $bd = BD::getInstance();
      if ($success) {
        $bd->commit();
      } else {
        $bd->rollback();
      }
      $bd->autocommit(true);
    }
  }
  
  // arrayaccess
  public function offsetExists(Model $Model, $offset) {
    if (is_numeric($offset) && $Model->is('Collection')) {
      return $Model->uses('Collection')->offsetExists($offset);
    }
    return isset($Model->Mapper[$offset]);
  }

  public function offsetGet(Model $Model, $offset) {
    if (is_numeric($offset) && $Model->is('Collection')) {
      return $Model->uses('Collection')->offsetGet($offset);
    }
    return $Model->Mapper[$offset];
  }

  public function offsetSet(Model $Model, $offset, $value) {
    if (is_numeric($offset) && $Model->is('Collection')) {
      $Model->uses('Collection')->offsetSet($offset, $value);
      return;
    }
    $Model->Mapper[$offset] = $value;
  }

  public function offsetUnset(Model $Model, $offset) {
    // TODO passar pro collection deletar o registro
    $Model->Mapper[$offset] = null;
  }
  
  // iterator
  static public $in_iteration = false; // flag que guarda se o model está em iteração (foreach)
  public function current(Model $Model) {
    return $Model->Mapper->get();
  }

  public function key(Model $Model) {
    if ($Model->is('Collection')) {
      return $Model->uses('Collection')->key();
    }
    return $Model->Mapper->getPointerValue();
  }

  public function next(Model $Model) {
    return $Model->Mapper->next();
  }
  
  public function rewind(Model $Model) {
    self::$in_iteration = true; // marca como dentro de iteração
    return $Model->Mapper->first();
  }

  public function valid(Model $Model) {
    $valid = !!$Model->Mapper->get();
    self::$in_iteration = $valid; // continua numa iteração se o registro ainda for valido
    return $valid;
  }
  
  public function count(Model $Model) {
    return $Model->Mapper->count();
  }
  
}