<?php

namespace Djck\model;

use Djck\system\AbstractSingleton;
use Djck\system\AbstractObject;

class Model extends AbstractSingleton {
  
  protected $entity;
  public $columns = array();
  public $from = -1;
  public $to = -1;
  public $limit = -1;
  
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
    $this->columns = $Mapper->getFields();
    $this->entity = $Mapper->getEntity();
    
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
            $Mapper->setFilter(new \Djck\database\query\Criteria($Mapper->id, '=', $register['id']));
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
            $Mapper[$col->getAlias()] = $register[$col->getAlias()];
          }
        }
        
        if ($register->isPersisted()) {
          // se já estiver persistido, fazer update
          if ($Mapper->update($update_columns) == 0) {
            //throw new \Exception('Não foi possível alterar o registro');
          }
          
        } else {
          // se não, fazer insert
          $Mapper->push();
          if ($Mapper->insert() > 0) {
            $Mapper->last();
            $register['id'] = $Mapper['id'];
            $register->persisted = true;
          } else {
            throw new \Exception('Não foi possível inserir o registro');
          }
        }
        
        $register->dirty = false;
        //$Mapper->commit();
      }
    }
    
  }
  
  // é chamado antes de qualquer metodo para filtrar os campos que eu quero mudar/receber
  // do model
  // ex: $model->columns('cmapo1', 'campo2')->get(12);
  function columns($fields = array()) {
    $this->columns = $fields;
    return $this;
  }
  
  function from($offset) {
    $this->from = $offset >= 0 ? (int)$offset : -1;
    return $this;
  }
  
  function to($offset) {
    $this->to = $offset >= 0 ? (int)$offset : -1;
    return $this;
  }
  
  function limit($offset) {
    $this->limit = $offset >= 0 ? (int)$offset : -1;
    return $this;
  }

}

class ModelRegister extends AbstractObject implements \ArrayAccess {
  
  public $columns = array();
  public $from = -1;
  public $to = -1;
  public $limit = -1;
  
  protected $data = array();
  
  public $dirty = false;
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

class Model2 extends AbstractSingleton {
  
  // cada chamada de metodo gera uma statement, que é uma referencia para os registros
  // do model. cada statement pode ser tanto um registro quanto uma coleção, e tudo
  // que forem alteradas nelas serão automaticamente alteradas no model (persistencia)
  protected $statements = array();
  protected $_opened_stmt;
  
  protected $entity;
  protected $columns = array();
  
  
  // teste da ideia (fazer de outro jeito depois)
  protected $result = array();
  
  // auxiliares para aumentar performance
  protected $_internal_index = 0;
  protected $_count = 0;
  protected $_id_hashtable = array();
  
  static protected $auto_increment = 1;
  
  public function destroy() {
    
  }

  public function reinit() {
    
  }
  
  // retorna uma nova instancia de registro
  function create() {
    $reg = array();
    $reg['id'] = null;
    foreach ($this->columns as $col) {
      $reg[$col] = null;
    }
    
    $this->_internal_index = $this->_count;
    $this->result[$this->_count++] = array(
        'data' => $reg,
        'flag' => 'fresh'
    );
    
    $stmt = $this->_openStatement();
    $stmt['register'] = &$this->result[$this->_internal_index];
    $stmt['index'] = $this->_internal_index;
    $stmt['id'] = mt_rand(100,999);
    $this->statements[$stmt['id']] = $stmt;
    unset($this->_opened_stmt);
    
    $model_reg = new ModelRegister();
    $model_reg->stmt = &$stmt;
    return $model_reg;
  }
  
  // retorna um registro especifico de registro
  function get($id) {
    $index = $this->_id_hashtable[$id];
    $this->_internal_index = $index;
    
    $stmt = $this->_openStatement();
    $stmt['register'] = &$this->result[$this->_internal_index];
    $stmt['index'] = $this->_internal_index;
    $stmt['id'] = mt_rand(100,999);
    $this->statements[$stmt['id']] = $stmt;
    unset($this->_opened_stmt);
    
    $model_reg = new ModelRegister();
    $model_reg->stmt = &$stmt;
    return $model_reg;
  }
  
  // retorna uma colecão
  //function getAll(\Djck\database\query\Expression $filter) {
    
  //}
  
  // persiste as alterações feitas nos registros ou nas coleções
  function save() {
    foreach ($this->result as $index => &$reg) {
      if ($reg['flag'] == 'fresh') {
        $reg['data']['id'] = self::$auto_increment++;
        $this->_id_hashtable[$reg['data']['id']] = $index;
        $reg['flag'] = 'persisted';
      }
    }
    unset($reg);
  }
  
  // é chamado antes de qualquer metodo para filtrar os campos que eu quero mudar/receber
  // do model
  // ex: $model->columns('cmapo1', 'campo2')->get(12);
  function columns($fields = array()) {
    $stmt =& $this->_openStatement();
    $stmt['columns'] = $fields;
    return $this;
  }
  
  function from($offset) {
    $stmt =& $this->_openStatement();
    $stmt['from'] = $offset;
    return $this;
  }
  
  function to($offset) {
    $stmt =& $this->_openStatement();
    $stmt['to'] = $offset;
    return $this;
  }
  
  function limit($offset) {
    $stmt =& $this->_openStatement();
    $stmt['limit'] = $offset;
    return $this;
  }
  
  
  protected function &_openStatement() {
    if (!isset($this->_opened_stmt)) {
      $this->_opened_stmt = array();
    }
    return $this->_opened_stmt;
  }

}

class ModelRegister2 extends AbstractObject implements \ArrayAccess {
  
  public $stmt;
  
  public function __destruct() {
    ;
  }
  
  public function offsetExists($offset) {
    return isset($this->stmt['register']['data'][$offset]);
  }

  public function offsetGet($offset) {
    return $this->stmt['register']['data'][$offset];
  }

  public function offsetSet($offset, $value) {
    if ($this->stmt['register']['flag'] == 'persisted') {
      $this->stmt['register']['flag'] = 'changed';
    }
    $this->stmt['register']['data'][$offset] = $value;
  }

  public function offsetUnset($offset) {
    unset($this->stmt['register']['data'][$offset]);
  }

  public function __call($name, $arguments) {
    switch ($name) {
      case 'create':
      case 'get':
      case 'getAll':
      case 'columns':
        throw new \Exception('Método não existe');
      default:
        //return $this->model->callMethod($name, $arguments);
    }
  }
  
}

class ModelCollection {
  
}