<?php

namespace Djck\model;

use Djck\system\AbstractSingleton;
use Djck\system\AbstractObject;

class Model extends AbstractSingleton implements \Countable {
  
  //protected $entity;
  public $columns = array();
  public $from = 0;
  public $to = 0;
  public $limit = 0;
  
  /**
   *
   * @var \Djck\mvc\Mapper 
   */
  protected $Mapper;
  
  /**
   *
   * @var ModelRegister[]
   */
  protected $Registers = array();
  
  public function setMapper(\Djck\mvc\Mapper $Mapper) {
    //$this->columns = $Mapper->getFields();
    //$this->entity = $Mapper->getEntity();
    
    $this->Mapper = $Mapper;
  }


  public function destroy() {
    
  }

  public function reinit() {
    
  }
  
  // retorna uma nova instancia de registro
  public function create() {
    $register = new ModelRegister($this);
    $this->Registers[] = $register;
    return $register;
  }
  
  // retorna um registro especifico de registro
  function get($id) {
    
    // pega registro na persistencia
    $result = $this->Mapper->find($id);
    if ($result === false) {
      throw new \Exception('Registro não existe');
    }
    
    if (!isset($this->Registers[ "i$id" ])) {
      // nao existe, criar um novo
      $register = new ModelRegister($this);
      $this->Registers[ "i$id" ] = $register;
    } else {
      // se já existir, usar um que ja exista nos registers
      $register = $this->Registers[ "i$id" ];
    }
    
    // atualiza registro
    $register->setData($this->Mapper->getData());
    
    return $register;
  }
  
  // retorna uma colecão
  //function getAll(\Djck\database\query\Expression $filter) {
    
  //}
  
  // persiste as alterações feitas nos registros ou nas coleções
  public function digest() {
    
    foreach ($this->Registers as $i => $register) {
      if ($register->isDirty()) {// só altera na persistencia se estiver "sujo" (alterado)
        
        // verifica se foram selecionadas colunas para alterar, se não, usar somente colunas
        // que foram alteradas
        if (!empty($this->columns)) {
          $update_columns = $this->columns;
        } else {
          $update_columns = $register->getUpdatedColumns();
        }
        
        // reseta o mapper
        $Mapper =& $this->Mapper;
        $Mapper->setFilter(array());
        //$Mapper->setStart(0);
        //$Mapper->setLimit(0);
        
        if ($register->isPersisted() || $register->isDeleted()) {
          if ($register instanceof ModelCollection) {
            $Mapper->setFilter($register->getFilter());
          } else {
            // FIXME arrumar essa gambiarra
            $Mapper->setFilter(array(new \Djck\database\query\Criteria($Mapper->id, '=', $register['id'])));
          }
          //$Mapper->setStart($register->from);
          //$Mapper->setLimit($register->to >= 0 ? ($register->to - $register->from) : $register->limit);
        }
        
        if ($register->isDeleted()) {
          // se estiver deletado, deleta
          if ($Mapper->delete() > 0) {
            unset($this->Registers[$i]); // apaga ref do register do model (memoria)
          } else {
            throw new \Exception('Não foi possível excluir o registro');
          }
        } else {
          // altera os valores de cada campo (passa o que estava no register pro mapper)
          foreach ($update_columns as $col) {
            $Mapper[$col] = $register[$col];
          }
          
          if ($register->isPersisted()) {
            // se já estiver persistido, fazer update
            if ($Mapper->update($update_columns) == 0) {
              throw new \Exception('Não foi possível alterar o registro');
            }

          } else {
            // se não, fazer insert
            $Mapper->push();
            if ($Mapper->insert() > 0) {
              $Mapper->last();
              $id = $Mapper['id'];
              $register['id'] = $id;
              $register->persisted = true;

              // define um novo key para o register, para ser facilmente achado pelo get()
              unset($this->Registers[$i]);
              $this->Registers["i$id"] = $register;
            } else {
              throw new \Exception('Não foi possível inserir o registro');
            }
          }

          $register->dirty = false;
          $register->dirty_columns = array();
        }
        
        //$Mapper->commit();
      }
    }
    
  }
  
  public function delete(&$register) {
    $register->dirty = true;
    $register->deleted = true;
    $register = null;
  }
  
  // é chamado antes de qualquer metodo para filtrar os campos que eu quero mudar/receber
  // do model
  // ex: $model->columns('cmapo1', 'campo2')->get(12);
  function columns($fields = array()) {
    $this->columns = $fields;
    return $this;
  }
  
  function from($offset) {
    $this->from = $offset < 0 ? 0 : (int)$offset;
    return $this;
  }
  
  function to($offset) {
    $this->to = $offset < 0 ? 0 : (int)$offset;
    return $this;
  }
  
  function limit($offset) {
    $this->limit = $offset < 0 ? 0 : (int)$offset;
    return $this;
  }
  
  public function count() {
    return $this->Mapper->count();
  }

}

class ModelRegister extends AbstractObject implements \ArrayAccess {
  
  public $columns = array();
  public $from = 0;
  public $to = 0;
  public $limit = 0;
  
  protected $data = array();
  
  public $dirty = false;
  public $dirty_columns = array();
  public $persisted = false;
  public $deleted = false;
  
  public function __construct(Model $Model) {
    
    $this->columns = $Model->columns;
    $this->from = $Model->from;
    $this->to = $Model->to;
    $this->limit = $Model->limit;
    
    parent::__construct();
  }
  
  public function offsetExists($offset) {
    return isset($this->data[$offset]);
  }

  public function offsetGet($offset) {
    return $this->data[$offset];
  }

  public function offsetSet($offset, $value) {
    $this->dirty = true;
    $this->dirty_columns[ $offset ] = $offset;
    $this->data[$offset] = $value;
  }

  public function offsetUnset($offset) {
    unset($this->data[$offset]);
  }
  
  public function delete() {
    $this->dirty = true;
    $this->deleted = true;
  }
  
  public function setData($data) {
    $this->data = $data;
  }
  
  public function getData() {
    return $this->data;
  }
  
  public function getUpdatedColumns() {
    return array_values($this->dirty_columns);
  }
  
  public function isDirty() {
    return (bool)$this->dirty;
  }
  
  public function isPersisted() {
    return (bool)$this->persisted;
  }
  
  public function isDeleted() {
    return (bool)$this->deleted;
  }
  
}

/////////////

class ModelCollection {
  
}