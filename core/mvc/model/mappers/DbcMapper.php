<?php

namespace Djck\mvc\mappers;

use Djck\Core;

use Djck\database\Dbc;
use Djck\database\query;

use Djck\mvc\Mapper;
use Djck\mvc\exceptions;
use Djck\mvc\interfaces;

use Djck\types;

/**
 * Description of DbcMapper
 *
 * @author usuario
 */
// faz com que a persistencia do mapper seja no banco de dados
// depends: SQLBase, Dbc
// aguenta mais ou menos 4000~ instancias criadas
class DbcMapper extends Mapper implements interfaces\DatabaseMapper {
  
  const DEFAULT_ID_NAME = 'id';
  const DEFAULT_DELETE_NAME = 'excluido';
  const DEFAULT_DELETE_DATE_NAME = 'excluido_em';
  const MAX_LIMIT_COLLECTION = 5001; // sempre deixe um número redondo + 1
  
  /**
   *
   * @var \Djck\database\Dbc
   * @access protected
   */
  protected $dbc;
  protected $dbc_config = 'default';
  
  protected $limit = self::MAX_LIMIT_COLLECTION;
  
  protected $permanent_delete = true;
  
  // count que conta só os registros persistentes (que já estão em banco de dados)
  protected $count_persisted = 0;
  // count de todos os registros que tem na tabela, independente de limit. deve-se usar total()
  protected $count_geral = 0;
  
  
  public function init() {
    if (!isset($this->dbc)) {
      $this->dbc = Dbc::getInstance($this->dbc_config);
    }
    
    if (!isset($this->entity)) {
      throw new exceptions\MapperException('Obrigatorio definir uma tabela');
    }
    
    if (empty($this->fields)) {
      $this->setFields($this->entity->getFields());
    }
    
    // inicia os dados já com os campos definidos
    $this->nullset();
    
  }
  
  public function setDbcConfig($config) {
    $this->dbc_config = $config;
    if (isset($this->dbc)) {
      $this->dbc = Dbc::getInstance($this->dbc_config);
    }
  }
  
  public function commit() {
    $this->dbc->commit();
    return parent::commit();
  }
  
  public function rollback() {
    $this->dbc->rollback();
    return parent::rollback();
  }
  
  /**
   * Retorna null, pois mappers de banco tem autoincrement proprio
   * @return null
   */
  protected function autoIncrement() {
    return $this->dbc->insert_id();
  }
  
  // retornará somente os registros persistentes
  public function count() {
    return $this->count_persisted;
  }
  
  // retornará todos os registros, independente de limit
  public function total() {
    return $this->count_geral;
  }

  public function find($pointer) {
    $offset = parent::find($pointer);
    if ($offset === false) {
      $id_field = $this->getPointer();
      $this->setFilter(array(new query\Criteria($this->{$id_field}, '=', $pointer)));
      if ($this->select() > 0) {
        return $this->internal_pointer;
      }
    }
    return $offset;
  }
  
  public function select($fields=array(), $distinct=false) {
    $success = true;
    $num_rows = 0;
    
    $this->clearResult();

    // instancia de conexao com o banco de dados
    $bd =& $this->dbc;
    
    $where = $this->filters;
    if (!$where) $where = array();
    
    // só pega registros não deletados, se a tabela foi configurada para tal
    if ($this->permanent_delete !== true) {
      $where[] = new query\Criteria(new query\Field(self::DEFAULT_DELETE_NAME), '=', '0');
    }
    if ($where) {
      $where = new query\Expression('AND', $where);
    }
    
    if (empty($fields)) {
      $fields = $this->fields;
    }

    $instruction = new query\ISelect($fields, $this->entity, $where, $this->order);
    $instruction->setDistinct($distinct);
    $sql = (string) $instruction;
    $bind_v = $instruction->getBinds();
    
    if (!$sql) return 0;
    
    //$this->_to_dump($sql, $bind_v);
    //print_r(array( $sql, $bind_v));
    
    // prepara o sql
    if ($success = $success && $bd->prepare($sql)) {
      foreach ($bind_v as $k => $value) {
        // binda o valor
        $bd->bind_param($k, $bind_v[$k]);
      }

      // executa a query
      if ($success = $success && $bd->execute()) {

        if ($success = $success && (($num_rows = $bd->num_rows()) > 0)) {
          while ($row = $bd->fetch_assoc()) {
            // TODO: acertar os tipos de variaveis que vem do banco
            
            $row = array_change_key_case($row, CASE_LOWER);
            
            // transforma algum campo data em objeto
            /*foreach ($row as &$col) {
              // parece data..
              if (types\DateTime::seemsDateTime($col)) {
                // lê a data e cria time baseado nisso
                // FIXME arrumar isso aqui, nao deixar desse jeito
                list($date, $time) = explode(' ', $col, 2);
                list($day, $month, $year) = explode('/', $date);
                list($hour, $minute, $second) = explode(':', $time);
                $col = new types\DateTime(mktime($hour, $minute, $second, $month, $day, $year));
              }
            }
            unset($col);*/
            
            // adiciona cada registro no collection interno
            $this->push($row, self::PERSISTED);
          }

          // retira os campos internos
          //unset($row['TOTAL'], $row['R_N']);
          
          // salva os dados nos campos
          // define que o registro selecionado para edicao é o primeiro 
          $this->first();
          /*foreach ($row as $col => $val) {
            $this->fields[SQLBase::key($col)]->setValue($val);
          }*/
          
        }

        //$this->total = $success ? 1 : 0;
      }
    }
    // sempre limpar o prepare, não importa se retornou true ou false
    $bd->free();
    
    // guarda os valores atuais para log de alteracao
    if ($success) {
      $this->saveState();
    }

    return $num_rows; // retorna o registro fetchado
  }
  
  public function update($fields=array()) {
    $success = true;

    // instancia de conexao com o banco de dados
    $bd =& $this->dbc;
    
    $where = $this->filters;
    if (!$where) $where = array();
    
    // primeiro tenta ver se tem id para alterar
    if ($this->_is_dirty && $this->_dirties == 0 && empty($where) && ($id = $this->getPointerValue())) {
      $id_field = $this->getPointer();
      $where[] = new query\Criteria($this->{$id_field}, '=', $id);
    }
    
    // se não tiver nenhum filtro, fazer update só nos dirty (AINDA NÃO IMPLEMENTADO)
    if (empty($where)) {
      if ($this->_dirties > 0) {
        // o dirty no caso de um mapper temporario funciona da mesma forma que
        // o insert: ele apenas seta as flags para PERSISTED de novo, pois os registros
        // já estão, teoricamente, persistidos nele mesmo. cada mapper deve implementar
        // essa parte da sua maneira
        throw new \Djck\mvc\exceptions\MapperException('Update com dirties ainda não implementado');
        /*$affected = 0;
        foreach ($this->result as $i => $_) {
          if (!$this->_isFlag($i, self::DIRTY)) { // registro que nao tiver dirty, ignorar
            continue;
          }
          ++$affected;
          $this->_removeFlag($i, self::DIRTY);
          
          // se acabou os registros para alterar, já parar daqui (performance)
          if ($this->_dirties == 0) break;
          --$this->_dirties;
        }
        $this->_is_dirty = ($this->_dirties > 0);
        return $affected;*/
      }
      return 0; // se não tiver filtro e nem campo pra alterar (dirty), nao fazer nada
    }
    
    // só pega registros não deletados, se a tabela foi configurada para tal
    if ($this->permanent_delete !== true) {
      $where[] = new query\Criteria(new query\Field(self::DEFAULT_DELETE_NAME), '=', '0');
    }
    $where = new query\Expression('AND', $where);
    
    // cria o SQL
    $instruction = new query\IUpdate($this->entity, $this->_getUpdatedValues($fields), $where);
    $sql = (string)$instruction;
    $bind_v = $instruction->getBinds();
    
    $affected = 0;
    
    // prepara o sql
    if ($success = $success && $bd->prepare($sql)) {
      foreach ($bind_v as $k => $value) {
        // binda o valor
        $bd->bind_param($k, $bind_v[$k]);
      }

      // executa a query
      if ($success = $success && $bd->execute()) {

        $affected = $bd->affected_rows();
        
        if ($affected > 0) {
          // limpa o objeto, setando todos os valores e criterias-igual para null
          //$this->recordset_data = array();
          //$this->nullset();
          //$this->clearResult();
          
        }
        
        //$this->total = count($this->recordset_data);
        
        //if ($this->log)
          //Logger::delete($this->_getFirstTable(), $success);
        
      }
    }
    // sempre limpar o prepare, não importa se retornou true ou false
    $bd->free();
    
    if ($success) {
      $this->saveState();
    }

    return $affected;
  }
  
  public function insert() {
    $success = true;
    
    if ($this->_inserts == 0) { // performance
      return 0;
    }

    // instancia de conexao com o banco de dados
    $bd =& $this->dbc;
    
    $pointer = $this->getPointer();
    $affected = 0;
    //foreach ($this->result as $i => $_) {
    for ($i = $this->result->count()-1;$i>= 0; $i--) { // faço invertido pois é mais provavel que seja usado push() do que unshift()
      
      if ($this->_isFlag($i, (self::PERSISTED | self::DIRTY))) {
        continue;
      }
      
      // insert
      $instruction = new query\IInsert($this->entity, $this->_getUpdatedValues());
      $sql = (string) $instruction;
      $bind_v = $instruction->getBinds();
      
      // prepara o sql
      if ($success = $success && $bd->prepare($sql)) {
        foreach ($bind_v as $k => $value) {
          // binda o valor
          $bd->bind_param($k, $bind_v[$k]);
        }

        // executa a query
        if ($success = $success && $bd->execute()) {
          if ($bd->affected_rows() > 0) {
            ++$affected;

            //$this->result[$i]['data'][$pointer] = $this->autoIncrement();
            //$this->result[$i]['flag'] |= self::PERSISTED;
            // tive que mudar pq o PHP 5.3 não suporta a sintaxe acima com offsetGet passado por referencia direto
            // funções passadas por referencia requerem o & nos dois lugares
            // ver: http://www.php.net/manual/en/language.references.return.php
            $result = &$this->result->offsetGet( $i );
            //$result['data'] = $data;
            $result['data'][$pointer] = $this->autoIncrement();
            $result['flag'] |= self::PERSISTED;
            //$this->_addFlag($i, self::PERSISTED); // só não mudei acima pois é mais simples e rapido fazer do jeito q está
          }
          
        }
        
      }
      // sempre limpar o prepare, não importa se retornou true ou false
      $bd->free();
      
      // se acabou os registros para inserir, já parar daqui (performance)
      if ($this->_inserts == 0) break;
      
      --$this->_inserts;
    }
    
    // aqui eu seto para zero pois pode haver casos de dar um push(), e, logo em seguida,
    // um pop(), fazendo com que o _inserts fique igual a 1, sendo que deveria estar 0.
    // nao verifico no shift() ou no pop() pois é meio pesado ficar verificando isso,
    // então prefiro zerar no final essa var toda vez que der um insert
    $this->_inserts = 0;
    
    // jogo o ponteiro pro final automaticamente, para o registro atual ficar preenchido com o last insert id
    // esse hack serve apenas para mappers temporarios, em dbcmapper isso nao é necessario
    $this->last();
    
    return $affected;
    
  }
  
  public function delete() {
    $success = true;

    // instancia de conexao com o banco de dados
    $bd =& $this->dbc;
    
    $where = $this->filters;
    if (!$where) $where = array();
    
    // primeiro tenta ver se tem id para alterar
    if (empty($where) && ($id = $this->getPointerValue())) {
      $id_field = $this->getPointer();
      $where[] = new query\Criteria($this->{$id_field}, '=', $id);
    }
    
    // se não tiver nenhum filtro, não fazer delete
    if (empty($where)) {
      return 0;
    }
    
    // só pega registros não deletados, se a tabela foi configurada para tal
    if ($this->permanent_delete !== true) {
      $where[] = new query\Criteria(new query\Field(self::DEFAULT_DELETE_NAME), '=', '0');
    }
    $where = new query\Expression('AND', $where);
    
    // cria o SQL
    if ($this->permanent_delete === true) {
      // se for permanente, deleta
      $instruction = new query\IDelete($this->entity, $where);
    } else {
      // se não for permanente, só fazer update
      $exc_field = new query\Field(self::DEFAULT_DELETE_NAME);
      $exc_field->setValue('1');
      $excem_field = new query\Field(self::DEFAULT_DELETE_DATE_NAME);
      $excem_field->setValue(new types\DateTime());

      $instruction = new query\IUpdate($this->entity, array($exc_field, $excem_field), $where);
    }
    $sql = (string)$instruction;
    $bind_v = $instruction->getBinds();
    
    $affected = 0;
    
    // prepara o sql
    if ($success = $success && $bd->prepare($sql)) {
      foreach ($bind_v as $k => $value) {
        // binda o valor
        $bd->bind_param($k, $bind_v[$k]);
      }

      // executa a query
      if ($success = $success && $bd->execute()) {

        $affected = $bd->affected_rows();
        
        if ($affected > 0) {
          // limpa o objeto, setando todos os valores e criterias-igual para null
          //$this->recordset_data = array();
          $this->nullset();
          $this->clearResult();
          
        }
        
        //$this->total = count($this->recordset_data);
        
        //if ($this->log)
          //Logger::delete($this->_getFirstTable(), $success);
        
      }
    }
    // sempre limpar o prepare, não importa se retornou true ou false
    $bd->free();
    
    if ($success) {
      $this->saveState();
    }

    return $affected;
  }
  
}