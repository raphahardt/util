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
  public $orders = array();
  
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
    $this->Mapper = null;
    $this->Registers = array();
  }

  public function reinit() {
    $this->reset();
  }
  
  public function reset() {
    $this->columns = array();
    $this->from = 0;
    $this->to = 0;
    $this->limit = 0;
    $this->orders = array();
  }
  
  // retorna uma nova instancia de registro
  public function create() {
    
    $register = new ModelRegister($this);
    $this->Registers[] = $register;
    
    $this->reset();
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
    
    $this->reset();
    return $register;
  }
  
  // retorna uma colecão
  function getAll($filter = array(), $distinct = false) {
    
    // pega registro na persistencia
    $_orders = array();
    foreach ($this->orders as $o) {
      $_orders[] = array( $this->Mapper->getField($o[0]), $o[1] ? 'desc' : 'asc' );
    }
    $this->Mapper->setOrderBy($_orders);
    $this->Mapper->setFilter($filter);
    $nrows = $this->Mapper->select($this->columns, $distinct);
    
    $register = new ModelCollection($this, $filter);
    $this->Registers[] = $register;
    
    // atualiza collection
    $register->setData(array());
    if ($nrows > 0) {
      $register->setCollection($this->Mapper->getResult());
    }
    
    $this->reset();
    return $register;
  }
  
  // abre um registro persistido para alteração, independente dele existir ou nao
  // a verificação se ele existe só acontece no digest()
  // é como se fosse o get(), porem não faz select no banco (-1 request pro banco)
  function edit($id) {
    
    if (!isset($this->Registers[ "i$id" ])) {
      // nao existe, criar um novo
      $register = new ModelRegister($this);
      $this->Registers[ "i$id" ] = $register;
      
      // deixa registro em branco
      $register->setData(array());
      
    } else {
      // se já existir, usar um que ja exista nos registers
      // neste caso, irá funcionar igual ao get()
      $register = $this->Registers[ "i$id" ];
    }
    
    $this->reset();
    return $register;
  }
  
  // retorna uma colecão
  function editAll($filter = array()) {
    
    // pega registro na persistencia
    $this->Mapper->setFilter($filter);
    
    $register = new ModelCollection($this, $filter);
    $this->Registers[] = $register;
    
    // atualiza collection
    $register->setData(array());
    $register->setCollection(array());
    
    $this->reset();
    return $register;
  }
  
  // persiste as alterações feitas nos registros ou nas coleções
  public function digest() {
    
    $Mapper =& $this->Mapper;
    $Mapper->beginTransaction();
    
    try {
      foreach ($this->Registers as $i => $register) {
        if ($register->isDirty()) {// só altera na persistencia se estiver "sujo" (alterado)
        // 
          // verifica se foram selecionadas colunas para alterar, se não, usar somente colunas
          // que foram alteradas
          if (!empty($this->columns)) {
            $update_columns = $this->columns;
          } else {
            $update_columns = $register->getUpdatedColumns();
          }

          // reseta o mapper
          $Mapper->reset(); // limpa os filtros, limits, orderbys, etc..

          if ($register->isPersisted() || $register->isDeleted()) {
            if ($register instanceof ModelCollection) {
              $Mapper->setFilter($register->getFilter());
              $Mapper->setStart($register->from);
              $Mapper->setLimit($register->to >= 0 ? ($register->to - $register->from) : $register->limit);
              // TODO: ver se o order by é realmente necessario aqui
              
            } else {
              // FIXME arrumar essa gambiarra
              $Mapper->setFilter(array(new \Djck\database\query\Criteria($Mapper->id, '=', $register['id'])));
            }
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
            $Mapper->nullset();
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
                $id = $Mapper['id'];
                
                $register['id'] = $id;
                $register->setPersisted();
                
                // define um novo key para o register, para ser facilmente achado pelo get()
                unset($this->Registers[$i]);
                $this->Registers["i$id"] = $register;
              } else {
                throw new \Exception('Não foi possível inserir o registro');
              }
            }

            // já alterou, tirar status de "sujo" (aterado)
            $register->setPristine();
          }

        }
      }
      
      // tudo certo, commit
      $Mapper->commit();
      
    } catch (\Exception $e) {
      // erros, rollback
      $Mapper->rollback();
      throw $e;
    }
    
  }
  
  // deleta um registro especifico do model
  public function delete(&$register) {
    $register->setDirty();
    $register->setDeleted();
    $register = null;
  }
  
  // deleta todos os registros em um filtro
  //public function deleteAll(Expression $exp) {
    
  //}
  
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
  
  function orderBy($column, $reverse = false) {
    $this->orders[] = array($column, $reverse);
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
  public $orders = array();
  
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
  
  // deprecated: usar model->delete(reg) em vez de reg->delete()
  /*public function delete() {
    $this->dirty = true;
    $this->deleted = true;
  }*/
  
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
  
  public function setPersisted() {
    $this->persisted = true;
  }
  
  public function setDirty($dirty_columns = array()) {
    $this->dirty = true;
    if (!empty($dirty_columns)) {
      foreach ($dirty_columns as $col) {
        $this->dirty_columns[$col] = $col;
      }
    }
  }
  
  public function setPristine() {
    $this->dirty = false;
    $this->dirty_columns = array();
  }
  
  public function setDeleted() {
    $this->deleted = true;
  }
  
}

/////////////

class ModelCollection extends ModelRegister implements \ArrayAccess, \Countable, \SeekableIterator {
  
  public $filters = array();
  public $orders = array();
  
  protected $data_collection;
  
  public function __construct(Model $Model, $filters) {
    
    $this->filters = $filters;
    $this->orders = $Model->orders;
    
    $this->data_collection = new \Djck\types\StorageArray();
    
    parent::__construct($Model);
  }
  
  public function setCollection($data) {
    if (!is_array($data) && !($data instanceof \Iterator)) {
      throw new \Exception('Os dados do collection precisam ser um array/iterator');
    }
    if ($data instanceof \Djck\types\StorageArray) {
      $this->data_collection = $data;
    } else {
      $this->data_collection->clean();
      if (count($data) > 0) {
        foreach ($data as $row) {
          $this->data_collection->push($row);
        }
      }
    }
  }
  
  public function count() {
    return $this->data_collection->count();
  }

  public function current() {
    $data = $this->data_collection->current();
    if (isset($data['data'])) {
      $data = $data['data'];
    }
    return $data;
  }

  public function key() {
    return $this->data_collection->key();
  }

  public function next() {
    return $this->data_collection->next();
  }

  public function offsetExists($offset) {
    if (!is_numeric($offset)) {
      return parent::offsetExists($offset);
    }
    if (isset($this->data_collection[$offset]['data'])) {
      return isset($this->data_collection[$offset]['data']);
    }
    return isset($this->data_collection[$offset]);
  }

  public function offsetGet($offset) {
    if (!is_numeric($offset)) {
      return parent::offsetGet($offset);
    }
    if (isset($this->data_collection[$offset]['data'])) {
      return $this->data_collection[$offset]['data'];
    }
    return $this->data_collection[$offset];
  }

  public function offsetSet($offset, $value) {
    if (!is_numeric($offset)) {
      parent::offsetSet($offset, $value);
      return;
    }
    throw new \Exception('$collection[] = val não suportado');
  }

  public function offsetUnset($offset) {
    if (!is_numeric($offset)) {
      parent::offsetUnset($offset);
      return;
    }
    throw new \Exception('unset($collection[key]) não suportado');
  }

  public function rewind() {
    $this->data_collection->rewind();
  }

  public function seek($position) {
    $this->data_collection->seek($position);
  }

  public function valid() {
    return $this->data_collection->valid();
  }

}