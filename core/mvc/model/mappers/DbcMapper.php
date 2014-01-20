<?php

namespace Djck\mvc\mappers;

use Djck\Core;

use Djck\database\Dbc;
use Djck\database\query;

use Djck\mvc\Mapper;
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
   * @var Djck\database\Dbc 
   * @access protected
   */
  protected $dbc;
  
  protected $limit = self::MAX_LIMIT_COLLECTION;
  protected $max_limit = self::MAX_LIMIT_COLLECTION;
  
  protected $permanent_delete = true;
  
  // count que conta só os registros persistentes (que já estão em banco de dados)
  protected $count_persisted = 0;
  // count de todos os registros que tem na tabela, independente de limit. deve-se usar total()
  protected $count_geral = 0;
  
  
  public function init() {
    if (!isset($this->dbc)) {
      $this->dbc = Dbc::getInstance();
    }
    
    if (!isset($this->entity)) {
      throw new \Djck\CoreException('Obrigatorio definir uma tabela');
    }
    
    if (!isset($this->fields)) {
      $this->setFields($this->entity->getFields());
    }
    
    // inicia os dados já com os campos definidos
    $this->nullset();
    
  }
  
  public function commit() {
    $this->dbc->commit();
  }
  
  public function rollback() {
    $this->dbc->rollback();
  }
  
  /**
   * Retorna null, pois mappers de banco tem autoincrement proprio
   * @return null
   */
  protected function autoIncrement() {
    return null;
  }
  
  // retornará somente os registros persistentes
  public function count() {
    return $this->count_persisted;
  }
  
  // retornará todos os registros, independente de limit
  public function total() {
    return $this->count_geral;
  }
  
  public function select() {
    $success = true;
    $num_rows = 0;
    
    $this->clearResult();

    // instancia de conexao com o banco de dados
    $bd = & $this->dbc;
    
    $where = $this->filters;
    if (!$where) $where = array();
    
    // só pega registros não deletados, se a tabela foi configurada para tal
    if ($this->permanent_delete !== true) {
      $where[] = new query\Criteria(new query\Field(self::DEFAULT_DELETE_NAME), '=', '0');
    }
    if ($where) {
      $where = new query\Expression('AND', $where);
    }

    $instruction = new query\ISelect($this->fields, $this->entity, $where, $this->order);
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

        if ($success = $success && (($num_rows = $bd->num_rows()) >= 1)) {
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
    
  public function insert() {
    $success = true;
    //$this->total = 0;

    // instancia de conexao com o banco de dados
    $bd = & $this->dbc;
    
    // se o registro atual não tiver id ainda, inserir ele também
    $need_id = false;
    if (/*!$this->data[$this->getPointer()] && */$this->isDirty()) {
      $need_id = true;
      $this->push($this->data);
    }
    
    // separa os registros em 'chunks' de 100 registros
    $all_data = array_chunk($this->result, 100, true);
    
    $count = 0;
    
    foreach ($all_data as $block) {
      
      $data = array();
      foreach ($block as $i => $row) {
        if ($row['flag'] === self::FRESH)
          $data[$i] = $row['data'];
      }
      
      $instr = new SQLIInsertAll($this->entity, $data);

      // pega as variaveis criadas do buildSQL
      $sql = (string) $instr;
      $bind_v = SQLBase::getBinds();
      
      $this->_to_dump($sql, $bind_v);
      if (!$sql) return 0;
      
      // prepara o sql
      if ($success = $success && $bd->prepare($sql)) {
        foreach ($bind_v as $k => $value) {
          // binda o valor
          $bd->bind_param($k, $bind_v[$k]);
        }

        // executa a query
        if ($success = $success && $bd->execute()) {
          // seta o id
          //$id = $bd->insert_id();
          $affected = $bd->affected_rows();
          
          // volta os campos retornados para os criterias
          if ($affected > 0) {
            /*while (list($key) = each($data)) {
              //$this->recordset_data[] = $this->add_data[$key];
              unset($this->result[$key]);
            }*/
            
            if ($need_id) {
              $id = $bd->insert_id();
              // atualiza o registro atual com o id retornado
              $this->data[$this->getPointer()] = $id;
              // truque: retira o ultimo elemento do result (que é o proprio registro atual)
              // e depois adiciona de novo com o push.
              $this->pop();
              $this->push();
            }
            
            /*if ($affected == 1) {
              $id = $bd->insert_id();
              $this->result[$index]['data'][$this->getPointer()] = $id;
              
            } elseif ($affected > 1) {
              // TODO: fazer um select para pegar os registros e atualizar o result
            }*/
            
            foreach ($data as $index => $row) {
              // marca que o registro ja foi modificado
              $this->result[$index]['flag'] = self::PERSISTED;
              ++$this->count_persisted;
              
            }
            /*foreach ($criteria_fields as $key => $field) {
              $field->setValue( $criteria_values[$key] ); // seta o campo também para o novo valor
              foreach ($this->_criterias_from_constraints as $criteria) {
                if ($field === $criteria->getField() && $criteria->getOperator() === '=') {
                  $criteria->setValue( $criteria_values[$key] );
                }
              }
            }*/
          }

          $success = $success && ($affected > 0);
          $count += $affected;

          //$this->total += $affected;

          /*if ($this->log)
            Logger::insert($this->_getFirstTable(), array('cnpj' => $this->fields['cnpj']->getValue(), 'times' => $this->fields['times']->getValue()), $success);*/

        }
      }
      // sempre limpar o prepare, não importa se retornou true ou false
      $bd->free();
      
    }
    unset($block); // apaga referencia
    
    if ($success)
      $this->saveState();

    return $count;
    
  }
  
  public function delete() {
    return $this->_delete();
  }
  
  protected function _delete($custom_filter=null) {
    $success = true;

    // instancia de conexao com o banco de dados
    $bd = & $this->dbc;
    
    $where = $this->filters;
    if (!$where) $where = array();
    
    // tenta usar o pointer, se possivel
    if (empty($where) && $this->getPointerValue()) {
      $id = $this->getPointer();
      $where[] = new query\Criteria($this->{$id}, '=', $this->getPointerValue());
    }
    
    $has_filter = count($where) > 0;
    
    // só pega registros não deletados, se a tabela foi configurada para tal
    if ($this->permanent_delete !== true) {
      $where[] = new query\Criteria(new query\Field(self::DEFAULT_DELETE_NAME), '=', '0');
    }
    // filtro customizado para apagar por id
    if ($custom_filter) {
      $pointer = $this->getPointer();
      $where[] = new query\Criteria($this->entity->{$pointer}, '=', $custom_filter);
    }
    $where = new query\Expression('AND', $where);
    
    // se não tiver nenhum filtro, não fazer update
    if (!$has_filter) {
      return 0;
    }
    
    // cria o SQL
    if ($this->permanent_delete === true) {
      // se for permanente, deleta
      $instr = new query\IDelete($this->entity, $where);
    } else {
      // se não for permanente, só fazer update
      $exc_field = new query\Field(self::DEFAULT_DELETE_NAME);
      $exc_field->setValue('1');
      $excem_field = new query\Field(self::DEFAULT_DELETE_DATE_NAME);
      $excem_field->setValue(new types\DateTime());

      $instr = new query\IUpdate($this->entity, array($exc_field, $excem_field), $where);
    }

    // pega as variaveis criadas do buildSQL
    $sql = (string)$instr;
    $bind_v = $instr->getBinds();
    
    $this->_to_dump($sql, $bind_v);
    if (!$sql) return 0;
    
    //print_r(array( $sql, $bind_v));

    // evita deletar toda a tabela (questoes de seguranca 12/03/2013)
    /*if (empty($bind_v)) {
      throw new CoreException('Alerta: tentativa de excluir toda a tabela!');
    }*/

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
          if (empty($custom_filter)) {
            // como não se sabe que filtro foi feito, é mais seguro apagar os registros internos
            // para evitar perda de consistencia de dados entre aplicação e banco de dados
            $this->nullset();
            $this->clearResult();
          }
          
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
  
  public function update() {
    $success = true;

    // instancia de conexao com o banco de dados
    $bd = & $this->dbc;
    
    $where = $this->filters;
    if (!$where) $where = array();
    
    // tenta usar o pointer, se possivel
    if (empty($where) && $this->getPointerValue()) {
      $id = $this->getPointer();
      $where[] = new query\Criteria($this->{$id}, '=', $this->getPointerValue());
    }
    
    $has_filter = count($where) > 0;
    
    // só pega registros não deletados, se a tabela foi configurada para tal
    if ($this->permanent_delete !== true) {
      $where[] = new query\Criteria(new query\Field(self::DEFAULT_DELETE_NAME), '=', '0');
    }
    $where = new query\Expression('AND', $where);
    
    // se não tiver nenhum filtro, não fazer update
    if (!$has_filter) {
      return 0;
    }

    // cria o SQL
    $instr = new query\IUpdate($this->entity, $this->_getUpdatedValues(), $where);

    // pega as variaveis criadas do buildSQL
    $sql = (string)$instr;
    $bind_v = $instr->getBinds();
    
    $this->_to_dump($sql, $bind_v);
    if (!$sql) return 0;
    
    //print_r(array( $sql, $bind_v));
    
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
  
  
  /**
   * Função para retorno dos campos do Model como propriedades do objeto.
   * Exemplo:
   * $model->nome;  // retorna o campo 'nome' da tabela do model
   * $model->nome->getValue(); // retorna o valor do campo 'nome'; ou
   * $model['nome'];
   * 
   * Também é possível retornar outras propriedades do Model
   * $model->Table // retorna a primeira tabela do model
   * $model->Tables // retorna as tabelas definidas do model
   * $model->Fields // retorna os campos definidos do model
   * 
   * Uso:
   * $model->Table->Fields['nome'] // retorna o campo 'nome' da primeira tabela do model
   * 
   * @param type $name
   * @return type
   * @throws ModelException
   */
  function __get($name) {
    
    // sanitize
    $field = strtolower($name);
    
    if (isset($this->fields[$field])) {
      return $this->fields[$field];
    }
    
    $fieldobj = $this->entity->getField($field);
    if ($fieldobj) {
      return $fieldobj;
    }
    
  }
  
  /**
   * Função auxiliar que retorna apenas os campos que foram definidos valores.
   * É usado para as instruções de UPDATE e INSERT só alterarem os campos alterados
   * @return type
   */
  public function isDirty() {
    if (!$this->data) return false;
    foreach ($this->data as $k => $v) {
      if ($v != $this->_pristine_data[$k]) {
        return true;
      }
    }
    return false;
  }
  
}