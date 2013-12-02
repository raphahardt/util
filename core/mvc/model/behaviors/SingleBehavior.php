<?php

namespace Djck\mvc\behaviors;

use Djck\mvc\Behavior;
use Djck\mvc\Model;

use Djck\database\query;

/**
 * Description of SingleBehavior
 *
 * @author usuario
 */
class SingleBehavior extends Behavior {
  
  public $priority = 0;
  
  public function find(Model $Model, $pointer) {
    return $Model->Mapper->find($pointer);
  }
  
  public function getFields(Model $Model) {
    return $Model->Mapper->getFields();
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
        $constraints = array(new query\Criteria($args[0], $args[1], $args[2]));
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
    if ($Model->Mapper instanceOf \Djck\mvc\DatabaseMapperInterface) {
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
  
  /**
   * Insere um registro na persistencia.
   * IMPORTANTE! Para inserir mais de um registro na mesma rotina encadeados, não use insert()
   * em cada linha. Use o CollectionBehavior com o método add() pra cada linha nova, e,
   * só no final de tudo, utilize insert().
   * Não fazer a recomendação acima faz com que o mesmo registro (com id também) seja inserido
   * duas vezes na persistencia. Em algumas persistencias como banco de dados, isso vai dar
   * conflito de PRIMARY KEY, e em arquivos podem ocorrer problemas inesperados (registros 
   * duplicados com mesmo id, registros sobreescritos, etc)
   * @param Model $Model
   * @return bool
   */
  public function insert(Model $Model) {
    // se o model também é um collection, chamar o metodo dele sempre primeiro 
    // em vez do single. isso permite que não importa a ordem dos behaviors, o collection
    // sempre terá precedencia no select
    if ($Model->is('Collection')) {
      //return $Model->uses('Collection')->insert();
	  // TODO
    }
    if ($Model->Mapper instanceOf \Djck\mvc\DatabaseMapperInterface) {
      //$return = $Model->Mapper->insertOne(); // TODO
      $return = $Model->Mapper->insert();
    } elseif ($Model->Mapper instanceof \Djck\mvc\DefaultMapperInterface) {
      $Model->Mapper->push();
      $return = $Model->Mapper->autoCommit() ? $Model->Mapper->commit() : true;
    }
    return $return;
  }
  
  public function update(Model $Model) {
    if ($Model->Mapper instanceOf \Djck\mvc\DatabaseMapperInterface) {
      $return = $Model->Mapper->update();
    } elseif ($Model->Mapper instanceof \Djck\mvc\DefaultMapperInterface) {
      $Model->Mapper->refresh();
      $return = $Model->Mapper->autoCommit() ? $Model->Mapper->commit() : true;
    }
    return $return;
  }
  
  public function delete(Model $Model) {
    // todos mappers tem essa funcao, e nos de database, ele apaga sem alterar o result,
    // diferente do delete() do dbcmapper
    $return = $Model->Mapper->remove();
    if ($Model->Mapper instanceof \Djck\mvc\DefaultMapperInterface) {
      $return && $return = $Model->Mapper->autoCommit() ? $Model->Mapper->commit() : true;
    }
    return $return;
  }
  
  public function startTransaction(Model $Model) {
    // TODO: guardar old em algum lugar, para depois quando dar endTrans recuperar o autocommit que estava
    $Model->Mapper->autoCommit(false);
  }
  
  public function endTransaction(Model $Model, $success) {
    if ($success) {
      $Model->Mapper->commit();
    } else {
      $Model->Mapper->rollback();
    }
    // TODO: guardar old em algum lugar, para depois quando dar endTrans recuperar o autocommit que estava
    $Model->Mapper->autoCommit(true);
  }
  
  // arrayaccess
  public function offsetExists(Model $Model, $offset) {
    // se o model também é um collection, chamar o metodo dele sempre primeiro 
    // em vez do single. isso permite que não importa a ordem dos behaviors, o collection
    // sempre terá precedencia no select
    if (is_numeric($offset) && $Model->is('Collection')) {
      return $Model->uses('Collection')->offsetExists($offset);
    }
    // FIXME verificar se o codigo acima dá problema ou não com usar o objeto com [0] e [campo] quando tem 2 behaviors
    return isset($Model->Mapper[$offset]);
  }

  public function offsetGet(Model $Model, $offset) {
    // se o model também é um collection, chamar o metodo dele sempre primeiro 
    // em vez do single. isso permite que não importa a ordem dos behaviors, o collection
    // sempre terá precedencia no select
    if (is_numeric($offset) && $Model->is('Collection')) {
      return $Model->uses('Collection')->offsetGet($offset);
    }
	// FIXME verificar se o codigo acima dá problema ou não com usar o objeto com [0] e [campo] quando tem 2 behaviors
    return $Model->Mapper[$offset];
  }

  public function offsetSet(Model $Model, $offset, $value) {
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