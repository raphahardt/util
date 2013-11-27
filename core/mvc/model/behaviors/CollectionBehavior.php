<?php

namespace Djck\mvc\behaviors;

use Djck\Core;
use Djck\mvc\Model;

Core::uses('SingleBehavior', 'Djck\mvc\behaviors');

/**
 * Description of CollectionBehavior
 *
 * @author usuario
 */
class CollectionBehavior extends SingleBehavior {
  
  public $priority = 1;
  
  public function add(Model $Model, $data = null) {
    $Model->Mapper->push($data);
    $Model->Mapper->nullset(); // limpa o registro atual pra evitar inserir duas vezes o ultimo registro (BUG)
  }
  
  public function find(Model $Model, $pointer) {
    return $Model->Mapper->find($pointer);
  }
  
  public function setFilter(Model $Model, $cons) {
    $args = func_get_args();
    array_shift($args); // model
    
    $constraints = array();
    if (count($args) > 1) {
      $constraints = $args;
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
  
  public function setOrderBy(Model $Model, $order) {
    $args = func_get_args();
    array_shift($args); // model
    
    $orders = array();
    if (count($args) > 1) {
      if (count($args) == 2 && is_string($args[1])) {
        $orders[] = array($args[0], $args[1]);
      } else {
        $orders = $args;
      }
    } elseif (count($args) == 1) {
      $order = $args[0];
      if (is_array($order)) {
        if (count($order) == 2 && is_string($order[1])) {
          $orders[] = array($order[0], $order[1]);
        } else {
          $orders = array_values($order);
        }
      } else {
        $orders[] = $order;
      }
    }
    $Model->Mapper->setOrderBy($orders);
    
  }
  
  public function setOffset(Model $Model, $offset) {
    $Model->Mapper->setOffset($offset);
  }
  
  public function setStart(Model $Model, $offset) {
    $Model->Mapper->setStart($offset);
  }
  
  public function setLimit(Model $Model, $limit) {
    $Model->Mapper->setLimit($limit);
  }
  
  public function setMaxLimit(Model $Model, $limit) {
    $Model->Mapper->setMaxLimit($limit);
  }
  
  public function select(Model $Model) {
    if ($Model->Mapper instanceOf \Djck\mvc\DatabaseMapperInterface) {
      $Model->Mapper->select();
    }
    $Model->Mapper->first();
    return true;
  }
  
  /*public function insert(Model $Model) {
    if ($Model->Mapper instanceOf DatabaseItfMapper) {
      $return = $Model->Mapper->insert();
    } elseif ($Model->Mapper instanceof DefaultItfMapper) {
      $Model->Mapper->push();
      $return = $Model->Mapper->commit();
    }
    return $return;
  }
  
  public function delete(Model $Model) {
    // todos mappers tem essa funcao, e nos de database, ele apaga sem alterar o result,
    // diferente do delete() do dbcmapper
    $return = $Model->Mapper->delete();
    return true;
  }*/
  
  // arrayaccess
  public function offsetExists(Model $Model, $offset) {
    return !!$Model->Mapper->get((int)$offset);
  }

  public function offsetGet(Model $Model, $offset) {
    return $Model->Mapper->get((int)$offset);
  }

  public function offsetSet(Model $Model, $offset, $value) {
    throw new \Djck\CoreException('Não é possivel definir dados diretamente no collection. '.
            'Para isso, use push() ou unshift()');
  }

  public function offsetUnset(Model $Model, $offset) {
    throw new \Djck\CoreException('Não é possivel definir dados diretamente no collection. '.
            'Para isso, use push() ou unshift()');
  }
  
  // iterator
  // herda de single
  
}