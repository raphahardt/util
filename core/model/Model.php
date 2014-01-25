<?php

namespace Djck\model;

use Djck\Core;
use Djck\database\query;
use Djck\system\AbstractSingleton;
use Djck\model\exceptions;

Core::uses('ModelRegister', 'Djck\model');
Core::uses('ModelCollection', 'Djck\model');
Core::registerPackage('Djck\model\exceptions');

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

  /**
   * Define o Mapper para o Model
   *
   * @param \Djck\mvc\Mapper $Mapper
   */
  public function setMapper(\Djck\mvc\Mapper $Mapper) {
    //$this->columns = $Mapper->getFields();
    //$this->entity = $Mapper->getEntity();
    
    $this->Mapper = $Mapper;
    $this->Mapper->init();
  }

  public function destroy() {
    $this->Mapper = null;
    $this->Registers = array();
  }

  public function reinit() {
    $this->reset();
  }

  /**
   * Reinicia a instancia do model ao seu estado original
   *
   */
  public function reset() {
    $this->columns = array();
    $this->from = 0;
    $this->to = 0;
    $this->limit = 0;
    $this->orders = array();
  }

  /**
   * Retorna um novo registro para o Model.
   *
   * @return ModelRegister
   */
  public function create() {
    
    $register = new ModelRegister($this);
    $this->Registers[] = $register;
    
    $this->reset();
    return $register;
  }

  /**
   * Retorna um registro existente no Model por seu "pointer" (id).
   *
   * @param int $id Valor do pointer do registro a ser procurado
   * @return ModelRegister
   * @throws exceptions\ModelException
   */
  function get($id) {
    
    // pega registro na persistencia
    $result = $this->Mapper->find($id);
    if ($result === false) {
      throw new exceptions\ModelException('Registro não existe');
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

  /**
   * Retorna uma coleção com registros existentes no Model, selecionados pelo filtro.
   *
   * @param query\base\ExpressionBase[] $filter Array de expressões
   * @param bool $distinct
   * @return ModelCollection
   */
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

  /**
   * Retorna o mesmo que get(), porém não faz busca na persistência para verificar
   * se o registro realmente existe. Essa verificação só acontece no próximo digest().
   *
   * Utilize esse método se você precisa editar um registro e não precisa de seus
   * dados no momento. A vantagem de usar edit() é que economiza um request no banco
   *
   * @see Model::get()
   * @param int $id
   * @return ModelRegister
   */
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

  /**
   * Retorna o mesmo que getAll(), porém não faz busca na persistência para verificar
   * se os registros existem.
   *
   * Utilize esse método se você precisar editar vários registros e não precisa de seus
   * valores no momento. A vantagem de usar edit() é que economiza um request no banco
   *
   * @see Model::getAll()
   * @param query\base\ExpressionBase[] $filter
   * @return ModelCollection
   */
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

  /**
   * Faz a persistência de todas as alterações do model.
   *
   * @throws \Exception
   */
  public function digest() {
    
    $Mapper =& $this->Mapper;
    $Mapper->beginTransaction();
    $id_field = $Mapper->getPointer();
    
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
              $Mapper->setFilter(array(new \Djck\database\query\Criteria($Mapper->{$id_field}, '=', $register[ $id_field ])));
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
                $id = $Mapper[ $id_field ];
                
                $register[ $id_field ] = $id;
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
