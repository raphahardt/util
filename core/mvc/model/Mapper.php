<?php

namespace Djck\mvc;

use Djck\Core;
use Djck\system\AbstractObject;
use Djck\types;
use Djck\database\query;

// registra os mappers principais
Core::registerPackage('Djck\mvc:model\mappers');

// interfaces /////////////////////////////////////
Core::usesPackage('Djck\mvc:model\interfaces');

abstract class MapperBase extends AbstractObject {
  
  /**
   * Função auxiliar para comparação de valores (usado no _quicksort)
   * @param type $val1
   * @param type $val2
   * @return type
   */
  protected function _compare($val1, $val2) {
    if (is_string($val1) && is_string($val2)) {
      return strnatcasecmp($val1, $val2);
    }
    return $val1 < $val2 ? -1 : ($val1 > $val2 ? 1 : 0);
  }
  
  /**
   * Funçao auxiliar que usa o algoritmo QuickSort para ordernar os registros do result
   * @param type $col
   * @param type $left
   * @param type $right
   * @param type $inverse
   */
  protected function _quicksort(&$array, $col, $left, $right, $inverse = false) {
    $i = $left;
    $j = $right;
    $pivot = (int)(($i + $j) / 2);
    $val_pivot = $array[$pivot]['data'][$col];
    while ($i < $j) {
      if ($inverse) {
        while ($this->_compare($array[$i]['data'][$col], $val_pivot) > 0) { // menor
          ++$i;
        }
        while ($this->_compare($array[$j]['data'][$col], $val_pivot) < 0) { // maior
          --$j;
        }
      } else {
        while ($this->_compare($array[$i]['data'][$col], $val_pivot) < 0) { // menor
          ++$i;
        }
        while ($this->_compare($array[$j]['data'][$col], $val_pivot) > 0) { // maior
          --$j;
        }
      }
      if ($i <= $j) {
        $aux = $array[$i];
        $array[$i] = $array[$j];
        $array[$j] = $aux;
        ++$i;
        --$j;
      }
    }
    if ($j > $left) $this->_quicksort($array, $col, $left, $j, $inverse);
    if ($i < $right) $this->_quicksort($array, $col, $i, $right, $inverse);
  }
  
  /**
   * Função auxiliar que seleciona apenas as colunas do $array_default com os valores do 
   * $array (só os que os dois tiverem em comum
   * @param array $array_default
   * @param array $array
   * @return array
   */
  protected function _diff($array_default, $array) {
    $diff = array_diff_key($array, $array_default);
    $result = array_merge($array_default, $array);
    return array_diff_key($result, $diff);
  }
  
  /**
   * Função auxiliar que valida um critério como se fosse um parser de banco de dados.
   * 
   * Serve para mappers que não são DatabaseMapperInterface.
   * 
   * @param array $data Dados a serem testados
   * @param query\Field $field
   * @param string $operator
   * @param mixed|query\Field $value
   * @return boolean
   */
  protected function _evalCriteria($data, $field, $operator, $value) {
    $comp1 = $data[ $field->getAlias() ];
    if ($value instanceof query\Field) {
      $comp2 = $data[ $value->getAlias() ];
    } else {
      $comp2 = $value;
    }
    switch ($operator) {
      case '=':
        return $comp1 == $comp2; // comparação normal == porque banco também faz assim
      case '!=':
      case '<>':
        return $comp1 != $comp2;
      case '>':
        return $comp1 > $comp2;
      case '<':
        return $comp1 < $comp2;
      case '>=':
        return $comp1 >= $comp2;
      case '<=':
        return $comp1 <= $comp2;
      // TODO: fazer LIKE, REGEXP, BETWEEN, etc...
    }
    return false;
  }
  
  /**
   * Função auxiliar que valida um critério como se fosse um parser de banco de dados.
   * 
   * Serve para mappers que não são DatabaseMapperInterface.
   * 
   * @param type $data Dados a serem testados
   * @param \Djck\database\query\Expression $expression
   * @return boolean
   */
  protected function _evalExpression($data, query\Expression $expression) {
    
    // pega o operador e as subexpressoes
    $operator = $expression->getOperator();
    $expressions = $expression->getExpressees();

    // definindo elemento neutro inicial
    // se o operador for OR, começar o resultado com FALSE (0 | teste = teste)
    // se não (AND), começar com TRUE (1 & teste = teste)
    if ($operator == 'OR') {
      $result = false;
    } else {
      $result = true;
    }
    // corre por cada subexpressao
    foreach ($expressions as $e) {
      // se o elemento for outra expressão, recursivamente testa-las
      if ($e instanceof query\Expression) {
        // mesma logica do elemento neutro acima
        if ($operator == 'OR') {
          $result || $result = $this->_evalExpression($data, $e);
        } else {
          $result && $result = $this->_evalExpression($data, $e);
        }
      } else {
        // se chegou até aqui, é pq é um criteria, e deve ser testado
        $result_criteria = $this->_evalCriteria($data, 
                $e->getField(), $e->getOperator(), $e->getValue());
        
        if ($operator == 'OR') {
          $result || $result = $result_criteria;
        } else {
          $result && $result = $result_criteria;
        }
      }
    }
    return $result;
  }
  
  /**
   * Função auxiliar que valida um critério como se fosse um parser de banco de dados.
   * 
   * Serve para mappers que não são DatabaseMapperInterface.
   * 
   * @param type $data Dados a serem testados
   * @param \Djck\database\query\Expression $expression
   * @return boolean
   */
  protected function _evalMathExpression($data, query\Expression $expression) {
    
    // pega o operador e as subexpressoes
    $operator = $expression->getOperator();
    
    if (!in_array($operator, array('+', '-', '*', '/', '%'))) {
      throw new \Djck\CoreException('O valor deve ser obrigatóriamente uma expressão aritmética.');
    }
    
    $expressions = $expression->getExpressees();

    // corre por cada subexpressao
    $result = null;
    foreach ($expressions as $e) {
      
      if ($e instanceof query\Expression) {
        // se o elemento for outra expressão, recursivamente testa-las
        $result_math = $this->_evalMathExpression($data, $e);
      } elseif ($e instanceof query\Field) {
        $result_math = $data[ $e->getAlias() ];
      } elseif (is_scalar($e)) {
        $result_math = $e;
      } else {
        throw new \Djck\CoreException('O valor deve ser ou um campo do mapper ou um escalar.');
      }

      switch ($operator) {
        case '+':
          if ($result === null) $result = 0;
          $result += $result_math;
          break;
        case '-':
          if ($result === null) {
            $result = $result_math;
          } else {
            $result -= $result_math;
          }
          break;
        case '*':
          if ($result === null) $result = 1;
          $result *= $result_math;
          break;
        case '/':
          if ($result === null) {
            $result = $result_math;
          } else {
            $result /= $result_math;
          }
          break;
        case '%':
          if ($result === null) {
            $result = $result_math;
          } else {
            $result %= $result_math;
          }
          break;
      }
    }
    return $result;
  }
  
}

/**
 * Representa dados, persistentes ou não.
 * Os Data Mappers (Mapper) fazem a ligação direta entre dados e Model.
 * Estes dados podem ser buscados de qualquer lugar persistente, como banco de dados,
 * arquivo, xml, json, ou até mesmo um array temporário.
 * 
 * Pense nos Mappers como um objeto onde se pode guardar dados.
 * 
 * @abstract
 * 
 * @property mixed $nome_do_campo Campo do Mapper
 * 
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 * @version 0.1 (24/09/2013)
 */
abstract class Mapper extends MapperBase implements \ArrayAccess {
  
  const FRESH = 0;
  const PERSISTED = 1;
  
  // onde dados do registro ficam guardados
  protected $data;
  protected $_pristine_data;
  
  // entidade que guarda a persistencia do mapper
  // pode ser uma tabela, um nome de arquivo, ou até nada (dados temporarios)
  protected $entity;
  
  // identificador do registro
  // pode ser uma SQLExpression (Dbc), o numero da linha (file), um id, um index de array, etc..
  protected $pointer = array('id' => null);
  
  // guarda os registros retornados pelo find() ou filter(), e o ponteiro quem vai lidar
  // com o registro unico. o mapper funcionará como um recordset
  protected $result = null;
  // guarda só os ids dos registros que foram filtrados
  protected $_filtered_result = array();
  protected $internal_pointer = 0;
  protected $count = 0;
  
  protected $offset = 0;
  protected $limit = 0;
  protected $fields = array();
   // serve como base para dados que vierem para serem alterados ou inseridos
  protected $_fields_array = array();
  protected $filters = array();
  protected $order = array();
  
  private $autocommit = true;
  
  public function __construct() {
    $this->result = new types\StorageArray();
    //$this->result = array();
  }
  
  /**
   * Retorna o proximo valor do autoincrementador interno do Mapper.
   * É usado para Mappers que não tem definidos um autoincrementador nativo, como escrever
   * em arrays ou arquivos (txt, xml, etc.). O DbcMapper, por exemplo, não precisa utiliza-lo,
   * pois o autoincrementador vem do proprio banco de dados
   * @access protected
   * @return integer
   */
  protected function autoIncrement() {
    
    // o autoincrement sempre terá que retonar algo unico, por isso está sendo mandado
    // o timestamp atual em float.
    // isso corrige o bug de, dois scripts escreverem em arquivo e pegarem o mesmo id ao mesmo tempo
    //return uniqid() . microtime(true);
    return uniqid() . mt_rand(100000,999999);
  }
  
  /**
   * Define se as operações do Mapper serão executadas as persistentes a cada instrução
   * ou no fim de uma transação. É recomendado que, ao fazer ações repetidas no Mapper,
   * desligar o autoCommit, e, ao final de tudo, commitar e religar o autoCommit.
   * Isso agiliza no processo de persistencia.
   * @param bool $set
   * @return bool
   */
  public function autoCommit($set=null) {
    if (isset($set)) {
      $this->autocommit = (bool)$set;
      return;
    }
    return $this->autocommit;
  }
  
  /**
   * Função auxiliar para encontrar um registro no result interno por alguma coluna
   * O $search deve ser da seguinte forma: array('coluna' => 'valor a ser procurado')
   * Essa função não altera o registro atual.
   * @access protected
   * @param array $search
   * @return int|boolean Retorna a posição do registro no result, ou FALSE se não encontrar
   */
  protected function _find($search) {
    for($i=0;$i<$this->count;$i++) {
      $found = false;
      if (is_array($search)) {
        $found = reset($search) == $this->result[$i]['data'][ key($search) ];
        // TODO: melhorar essa parte, pois chamar key() e reset() toda hora é lento
      } else {
        // TODO: implementar também pra quando for pesquisado por uma string, por ex
      }
      if ($found) {
        return $i;
      }
    }
    return false;
  }
  
  /**
   * Procura um registro pelo seu id (pointer). Em alguns mappers é possível procurar por uma
   * expressão também.
   * Se encontrar, o registro atual é substituido pelo registro encontrado. Caso contrario,
   * o registro atual é apagado (nullset)
   * @param mixed $pointer Pode ser um integer (id) ou uma expressão (string, object...)
   * @return integer|boolean Retorna a posição do registro no result, ou FALSE se não encontrar
   */
  public function find($pointer) {
    // limpa os dados internos
    $this->nullset();
    if (($offset = $this->_find(array($this->getPointer()=>$pointer))) !== false) {
      $this->set($this->result[$offset]['data']);
      // após encontrar, o pointeiro interno deve apontar agora para o registro no result
      $this->internal_pointer = $offset; // VER O QUE ISSO IMPACTA
      return $offset;
    }
    return false;
  }
  
  /**
   * Seleciona os registros de uma entidade.
   * 
   * @param query\Field[] $fields
   * @param boolean $distinct Se retorna apenas registros agrupados se repetidos (TRUE) ou não
   * @return int Número de registros total/filtrados
   * @throws \Djck\CoreException
   */
  public function select($fields=array(), $distinct=false) {
    if ($distinct) {
      throw new \Djck\CoreException('$distinct não suportado para Mapper');
    }
    // TODO: selecionar por $fields
    
    $where = $this->filters;
    if (!$where) $where = array();
    
    // order by no mapper puro, suporte apenas pra 1 campo
    $order = reset($this->order);
    $this->sort($order);
    
    // só pega registros não deletados, se a tabela foi configurada para tal
    /*if ($this->permanent_delete !== true) {
      $where[] = new query\Criteria(new query\Field(self::DEFAULT_DELETE_NAME), '=', '0');
    }*/

    $num_rows = $this->_filterResult($where);
    
    // reseta ponteiro
    $this->first();
    
    // guarda os valores atuais para log de alteracao
    if ($num_rows) {
      $this->saveState();
    }

    return $num_rows; // retorna o registro fetchado
  }
  
  /**
   * Salva as alterações feitas no registro e replica para o result.
   * 
   * Se foi feito um filtro anterior, as alterações serão feitas nos registros do
   * result que validem como TRUE a expressão de filtro.
   * 
   * Exemplo de uso
   * --------------
   * <code>
   * $mapper->setFilter(array(new query\Criteria($mapper->campo, '>', 10)));
   * $mapper['campo'] = 20;
   * $mapper->update(); // vai alterar todos os registros em que 'campo' for maior que 10
   * </code>
   * 
   * @return int Número de registros que foram efetivamente alterados
   */
  public function update() {
    
    $where = $this->filters;
    if (!$where) $where = array();
    
    // primeiro tenta ver se tem id para alterar
    if (empty($where) && ($id = $this->getPointerValue())) {
      $new_data = $this->data;
      
      $offset = $this->find($id);
      if ($offset !== false) {
        // se for id, muda só 1 registro
        $this->set($new_data);
        $this->refresh();
        return 1;
      }
    }
    
    // se não tiver nenhum filtro, não fazer update
    $has_filter = count($where) > 0;
    if (!$has_filter) {
      return 0;
    }
    
    // só pega registros não deletados, se a tabela foi configurada para tal
    /*if ($this->permanent_delete !== true) {
      $where[] = new query\Criteria(new query\Field(self::DEFAULT_DELETE_NAME), '=', '0');
    }*/
    $num_rows = $this->_filterResult($where);
    
    $affected = 0;
    $updated_values = $this->_getUpdatedValues(); // só valores que foram alterados
    
    foreach ($this->_filtered_result as $i => $_) {
      if ($i < 0) {
        continue; // ignora -1
      }
      $this->get($i);
      foreach ($updated_values as $field => $val) {
        // altera cada campo que foi alterado
        $this[$field] = $val;
      }
      $this->refresh();
      ++$affected;
    }
    
    // reseta ponteiro
    $this->first();
    
    if ($affected) {
      $this->saveState();
    }

    return $affected;
  }
  
  /**
   * Insere os novos registros na persistencia do Mapper.
   * 
   * Observações:
   * - Num mapper temporário, esse método é apenas "informativo", pois os registros
   * que são inseridos com push(), em teoria, já estão na persistencia do mapper, que
   * é o $result. 
   * 
   * @return int Número de registros que foram efetivamente inseridos
   */
  public function insert() {
    
    $affected = 0;
    foreach ($this->result as $i => $_) {
      if ($this->result[$i]['flag'] === self::FRESH) {
        ++$affected;
        //$this->result[$i]['flags'] = self::PERSISTED;
        // tive que mudar pq o PHP 5.3 não suporta a sintaxe acima com offsetGet passado por referencia direto
        // funções passadas por referencia requerem o & nos dois lugares
        // ver: http://www.php.net/manual/en/language.references.return.php
        $result = &$this->result->offsetGet( $i );
        //$result['data'] = $data;
        $result['flag'] = self::PERSISTED;
      }
    }
    
    return $affected;
  }
  
  public function delete() {
    
    $where = $this->filters;
    if (!$where) $where = array();
    
    // primeiro tenta ver se tem id para alterar
    if (empty($where) && ($id = $this->getPointerValue())) {
      
      if ($this->remove($id)) {
        // se for id, deleta só 1 registro
        return 1;
      }
    }
    
    // se não tiver nenhum filtro, não fazer update
    $has_filter = count($where) > 0;
    if (!$has_filter) {
      return 0;
    }
    
    // só pega registros não deletados, se a tabela foi configurada para tal
    /*if ($this->permanent_delete !== true) {
      $where[] = new query\Criteria(new query\Field(self::DEFAULT_DELETE_NAME), '=', '0');
    }*/
    $num_rows = $this->_filterResult($where);
    
    $affected = 0;
    $updated_values = $this->_getUpdatedValues(); // só valores que foram alterados
    
    foreach ($this->_filtered_result as $i => $_) {
      if ($i < 0) {
        continue; // ignora -1
      }
      $this->get($i);
      foreach ($updated_values as $field => $val) {
        // altera cada campo que foi alterado
        $this[$field] = $val;
      }
      $this->refresh();
      ++$affected;
    }
    
    // reseta ponteiro
    $this->first();
    
    if ($affected) {
      $this->saveState();
    }

    return $affected;
  }
  
  /**
   * Limpa os dados do registro atual. Não modifica o result.
   * @return void
   */
  public function nullset() {
    //$this->data = null;
    $this->_setData($this->data, null);
    $this->saveState(); // pristine
    $this->pointer = array($this->getPointer() => null);
    // se o registro atual é apagado, o ponteiro interno deve apontar pra algo que nao exista
    $this->internal_pointer = -1; // VER O QUE ISSO IMPACTA
  }
  
  /**
   * Retorna o index do ponteiro interno
   * @return int
   */
  public function index() {
      return $this->internal_pointer;
  }
  
  /**
   * Define todos os valores do registro atual.
   * O $data deve ter a estrutura: array('coluna1' => 'valor1', 'coluna2' => 'valor2' ...)
   * @param array $data
   * @return void
   */
  public function set($data) {
    if (!$data) {
      $this->nullset();
      return;
    }
    $id = $this->getPointer();
    $values = array_change_key_case($data, CASE_LOWER);
    
    $this->_setData($this->data, $values);
    $this->saveState();
    $this->pointer = array($id => $data[$id]);
  }
  
  /**
   * Retorna os dados do registro atual num array associativo
   * O array terá esta estrutura: array('coluna1' => 'valor1', 'coluna2' => 'valor2' ...)
   * @return array|boolean Os dados num array ou FALSE se o result tiver vazio ou o registro nao existir
   */
  public function get($index=null) {
    if (isset($index)) {
      $this->internal_pointer = $index;
    }
    $data = $this->result[$this->internal_pointer];
    $this->set($data['data']);
    return $data ? $data['data'] : false;
  }
  
  /**
   * Retorna os dados do registro atual num array associativo
   * O array terá esta estrutura: array('coluna1' => 'valor1', 'coluna2' => 'valor2' ...)
   * @return array|boolean Os dados num array ou FALSE se o result tiver vazio ou o registro nao existir
   */
  public function getData() {
    return $this->data;
  }
  
  /**
   * Limpa o result. Não modifica o registro atual
   * @return void
   */
  public function clearResult() {
    //$this->result = array();
    $this->result->clean();
    $this->internal_pointer = 0;
    $this->count = 0;
  }
  
  /**
   * Adiciona ao result o registro atual/$data no fim do array. Segue a ideia do array_push()
   * @param array $data Se data for NULL, insere o registro atual, senão, o $data
   * @return void 
   */
  public function push($data = null, $flag = self::FRESH) {
    if (!isset($data)) {
      $data = $this->data;
    }
    if ($data === null) return;
    
    if (!isset($data[ $this->getPointer() ]))
      $data[ $this->getPointer() ] = $this->autoIncrement();
    
    $this->result[ $this->count++ ] = array(
        'data' => $data,
        //'pointer' => $data[ $this->getPointer() ], // valor do ponteiro
        'flag' => $flag, // flag é usado nos mappers de banco de dados para saber se o registro foi salvo ou não no bd
    );
    $this->internal_pointer = $this->count-1;
  }
  
  /**
   * Remove o ultimo registro do result. Segue a ideia do array_pop()
   * @return array Os dados do registro deletado
   */
  public function pop() {
    //$result = array_pop($this->result);
    $result = $this->result->pop();
    --$this->count;
    return $result['data'];
  }
  
  /**
   * Remove um registro do result baseando no index dele. Segue a ideia do array_splice()
   * @param integer $offset Index do registro que será removido
   * @param integer $len Quantos registros serão apagados após o $offset. ex: splice(2,4) irá apagar 4 registros começando do index 2
   * @return boolean TRUE
   */
  public function splice($offset, $len=1) {
    if ($len === null) $len = $this->count;
    //array_splice($this->result, $offset, $len);
    $this->result->splice($offset, $len);
    $this->count-=$len;
    return true;
  }
  
  /**
   * Remove um registro do result pelo seu id (pointer), ou o registro atual. 
   * @param mixed $pointer Se for NULL, apaga o registro atual, senão, tenta remover pelo id. Pode ser um integer, ou, dependendo do mapper, pode ser um filtro (string, object, etc)...
   * @return boolean
   */
  public function remove($pointer = null) {
    if (!isset($pointer)) {
      $pointer = $this->data[ $this->getPointer() ];
    }
    if (($offset = $this->find($pointer)) !== false) {
      //array_splice($this->result, $offset, 1);
      $this->result->splice($offset, 1);
      --$this->count;
      $this->nullset();
      return true;
    }
    return false;
  }
  
  /**
   * Adiciona ao result o registro atual/$data no inicio do array. Segue a ideia do array_unshift()
   * @param array $data Se data for NULL, insere o registro atual, senão, o $data
   * @return void 
   */
  public function unshift($data = null, $flag = self::FRESH) {
    if (!isset($data)) {
      $data = $this->data;
    }
    if ($data === null) return;
    
    $pointer = $this->getPointer();
    if (!isset($data[ $pointer ]))
      $data[ $pointer ] = $this->autoIncrement();
    
    $this->result->unshift(array(
        'data' => $data,
        //'pointer' => $data[ $pointer ], // valor do ponteiro
        'flag' => $flag, // flag é usado nos mappers de banco de dados para saber se o registro foi salvo ou não no bd
    ));
    ++$this->count;
    $this->internal_pointer = 0;
  }
  
  /**
   * Remove o primeiro registro do result. Segue a ideia do array_shift()
   * @return array Os dados do registro deletado
   */
  public function shift() {
    //$result = array_shift($this->result);
    $result = $this->result->shift();
    --$this->count;
    return $result['data'];
  }
  
  /**
   * Verifica se o registro atual foi definido ou não.
   * @return boolean
   */
  public function exists() {
    return $this->data !== null || current($this->pointer) !== null;
  }
  
  protected function _searchNextFilteredResult($inverse = false) {
    if (!empty($this->_filtered_result)) {
      while (!$this->_filtered_result[$this->internal_pointer]) {
        // se não achar, retornar -1
        if ($this->internal_pointer < 0 || $this->internal_pointer >= $this->count) {
          $this->internal_pointer = -1;
          break;
        }
        // anda com o ponteiro até achar o proximo registro filtrado
        $this->internal_pointer += $inverse ? -1 : 1;
      }
    }
  }
  
  /**
   * Anda com o ponteiro interno até o primeiro registro e o retorna.
   * @return array Os dados do registro
   */
  public function first() {
    $this->internal_pointer = 0;
    $this->_searchNextFilteredResult();
    $data = $this->result[$this->internal_pointer];
    $this->set($data['data']);
    return $data ? $data['data'] : false;
  }
  
  /**
   * Anda com o ponteiro interno até o próximo registro e o retorna. Retorna FALSE se for o último
   * @return array Os dados do registro
   */
  public function next() {
    ++$this->internal_pointer;
    $this->_searchNextFilteredResult();
    $data = $this->result[$this->internal_pointer];
    $this->set($data['data']);
    return $data ? $data['data'] : false;
  }
  
  /**
   * Anda com o ponteiro interno até o registro anterior e o retorna. Retorna FALSE se for o primeiro
   * @return array Os dados do registro
   */ 
  public function prev() {
    --$this->internal_pointer;
    $this->_searchNextFilteredResult(true); // inverse
    $data = $this->result[$this->internal_pointer];
    $this->set($data['data']);
    return $data ? $data['data'] : false;
  }
  
  /**
   * Anda com o ponteiro interno até o último registro e o retorna.
   * @return array Os dados do registro
   */
  public function last() {
    $this->internal_pointer = $this->count-1;
    $this->_searchNextFilteredResult(true); // inverse
    $data = $this->result[$this->internal_pointer];
    $this->set($data['data']);
    return $data ? $data['data'] : false;
  }
  
  /**
   * Atualiza os dados do registro atual no result, se o registro atual contiver no result.
   * Serve para atualizar os dados em alguns mappers que usam o result como referencia
   * para a persistencia de dados, como o FileMapper
   * @return void 
   */
  public function refresh() {
    $data = $this->data;
    if ($data === null) return;
    
    if (isset($this->result[ $this->internal_pointer ]) && $data[$this->getPointer()]) {
      foreach ($data as &$val) {
        if ($val instanceof query\Expression) {
          // se o valor for uma expressao, usar o valor do prestine como referencia
          // para executar a expressao
          $val = $this->_evalMathExpression($this->_pristine_data, $val);
        }
      }
      unset($val);
      
      //$this->_setData($this->result[ $this->internal_pointer ]['data'], $data);
      // tive que mudar pq o PHP 5.3 não suporta a sintaxe acima com offsetGet passado por referencia direto
      // funções passadas por referencia requerem o & nos dois lugares
      // ver: http://www.php.net/manual/en/language.references.return.php
      $result = &$this->result->offsetGet( $this->internal_pointer );
      //$result['data'] = $data;
      $this->_setData($result['data'], $data);
    }
  }
  
  /**
   * Salva todos os dados no result em um ambiente persistente (banco de dados, arquivo, etc)
   * @return boolean
   */
  public function commit() {
    // nao faz nada: o mapper temporario fica com seus dados todos no result
    return true;
  }
  
  /**
   * Recupera as informações antes delas terem sido alteradas na persistencia.
   * Geralmente, em Mappers que mexem com arquivo, esse método não faz nada, pois
   * a persistencia só é feita no commit, diferente dos Mappers de banco de dados.
   * @return void
   */
  public function rollback() {
    // nao faz nada: o mapper temporario fica com seus dados todos no result
    return;
  }
  
  /**
   * Ordena os registros do result por uma coluna. Se coluna for NULL, é ordenado pelo id (pointer)
   * @param string $column Nome da coluna
   * @param boolean $desc Se TRUE, ordena descrescente, senão, crescente (padrão)
   * @return boolean TRUE
   */
  public function sort($column = null, $desc = false) {
    if (!isset($column))
      $column = $this->getPointer();
    
    if ($column instanceof query\Field) {
      $desc = $column->getOrder();
      $column = $column->getAlias();
    }
    
    $this->_quicksort($this->result, $column, 0, $this->count-1, $desc === true || strtolower($desc) === 'desc');
    return true;
  }
  
  /**
   * Define a entidade que o mapper ira utilizar. Pode ser um nome de arquivo, uma tabela do
   * banco de dados, ou nada (dados temporários).
   * @param mixed $entity
   */
  public function setEntity($entity) {
    $this->entity = $entity;
  }
  
  /**
   * Retorna a entidade definida do mapper
   * @return mixed
   */
  public function getEntity() {
    return $this->entity;
  }
  
  /**
   * Retorna o campo definido do registro.
   * 
   * Atenção! Este método pode resetar o ponteiro interno do result!
   * 
   * @param string $name Nome/alias do campo
   * @return \Djck\database\query\Field
   */
  public function getField($name) {
    // sanitize
    $field = strtolower($name);
    
    // procura pelos campos reais (Field)
    if (isset($this->fields[$field])) {
      return $this->fields[$field];
    }
    
    // procura pelos campos definidos na entity (Field)
    if ($this->entity instanceof query\base\EntityBase) {
      $fieldobj = $this->entity->getField($field);
      if ($fieldobj) {
        return $fieldobj;
      }
    }
    
    // por ultimo, tenta pelo data
    $this->first(); // reseta o ponteiro (necessario caso o ponteiro interno estiver no final)
    if (isset($this->data[$field]) || array_key_exists($field, $this->data)) {
      return new query\Field($field);
    }
  }
  
  /**
   * Define os campos dos registros. Nos arquivos servirão de cabeçalho. Nos outros formatos
   * como json ou xml, serão propriedades
   * (É protected pois não é possivel alterar os campos em tempo de execução, só ao criar a
   * instancia __construct)
   * @param \Djck\database\query\Field[] $entity
   * @access protected
   */
  public function setFields($fields) {
    $this->fields = array();
    $this->_fields_array = array();
    foreach ($fields as $f) {
      $this->fields[$f->getHash()] = $f;
      $this->_fields_array[$f->getHash()] = null;
    }
  }
  
  public function getFields() {
    if ($this->fields) {
      return $this->fields;
    }
    return array_keys($this->data);
  }
  
  /**
   * Define qual coluna será o "primary key" dos registros.
   * @param string $pointer
   * @param mixed $initval Se definido, o id (pointer) do registro atual sera iniciado já com este valor
   */
  public function setPointer($pointer, $initval = null) {
    if ($pointer instanceof query\base\HasAlias) {
      $pointer = $pointer->getAlias();
    }
    $this->pointer = array($pointer => $initval);
  }
  
  /**
   * Retorna o noma da coluna que é o "primary key" dos registros.
   * @return string
   */
  public function getPointer() {
    return key($this->pointer);
  }
  
  /**
   * Retorna o valor da coluna que é o "primary key" dos registros.
   * @return string
   */
  public function getPointerValue() {
    return reset($this->pointer);
  }
  
  /**
   * Define um filtro para os dados.
   * 
   * @param \Djck\database\query\ExpressionBase[] $exprs Array de expressões
   * @throws ModelException
   */
  public function setFilter($exprs) {
    if (!is_array($exprs)) return;
    
    $this->_filtered_result = array(); // limpa os resultados filtrados anteriormente, importante
    $this->filters = array();
    foreach ($exprs as $expr) {
      if (!($expr instanceof query\base\ExpressionBase)) {
        throw new ModelException('O filtro deve ser uma expressão.');
      }
      
      $this->filters[$expr->getHash()] = $expr;
    }
  }
  
  /**
   * Retorna as expressões de filtro definidas do mapper.
   * 
   * @return \Djck\database\query\base\ExpressionBase[]
   */
  public function getFilter() {
    return $this->filters;
  }
  
  public function setOrderBy($orders) {
    if (!is_array($orders) || empty($orders)) return false;
    
    $this->order = array();
    foreach ($orders as $o) {
      $direction = null;
      if (is_array($o)) {
        $direction = $o[1];
        $o = $o[0];
      }
      if ($o instanceof query\interfaces\Ordenable) {
        if ($direction) $o->setOrder($direction);
      } else {
        throw new ModelException('A ordenação deve ser um objeto ordenavel.');
      }
      $this->order[] = $o;
    }
  }
  
  public function getOrderBy() {
    return $this->order;
  }
  
  public function setOffset($offset) {
    if ($offset < 0) $offset = 0;
    $this->offset = $offset;
  }
  
  public function setStart($offset) {
    $this->setOffset($offset);
  }
  
  public function setLimit($limit) {
    if ($limit < 0) $limit = 0;
    $this->limit = $limit;
  }
  
  /**
   * Grava os valores atuais numa variável interna. Serve para criar o log na alteração
   * de valores.
   */
  public function saveState() {
    $this->_pristine_data = $this->data;
    /*foreach ($this->data as $k => $f) {
      $this->_pristine_data[$k] = $f;
    }*/
  }
  
  /**
   * Filtra o result de acordo com as expressões passadas.
   * Deve ser sempre um array.
   * 
   * As expressões desse array serão interligadas por uma expressão AND.
   * Caso queira usar OR, criar um array que possua 1 expressão OR com as subexpressões
   * dentro dele, que vai resultar em algo do tipo: AND(OR(exp1, exp2...))
   * 
   * @param \Djck\database\query\Expression[] $filter Array de expressões
   * @return int Número de linhas retornadas do filtro
   */
  protected function _filterResult($filter=array()) {
    if (empty($filter)) {
      $this->_filtered_result = array();
      return 0;
    }
    if ($num_rows = count($this->_filtered_result) && $filter == $this->filters) {
      return $num_rows;
    }
    if ($filter) {
      $filter = new query\Expression('AND', $filter);
    }

    $num_rows = 0;
    
    $this->_filtered_result = array();
    $this->_filtered_result[-1] = true; // flag para dizer que foi filtrado alguma coisa
    for ($i = $this->offset; $i < $this->count && ($this->limit == 0 || $i-$this->offset < $this->limit); $i++) {
      $data = $this->result[$i];
      
      if ($this->_evalExpression($data['data'], $filter)) {
        //$this->_filtered_result[ $data['data'][ $this->getPointer() ] ] = true;
        $this->_filtered_result[ $i ] = true;
        ++$num_rows;
      }
    }
    return $num_rows;
  }
  
  /**
   * Função auxiliar que retorna apenas os campos que foram definidos valores.
   * É usado para as instruções de UPDATE e INSERT só alterarem os campos alterados
   * 
   * @return array
   */
  protected function _getUpdatedValues() {
    if (!$this->data) return array();
    $fields = array();
    foreach ($this->data as $k => $v) {
      if ($v != $this->_pristine_data[$k]) {
        $fields[$k] = $v;
      }
    }
    return $fields;
  }
  
  protected function _setData(&$prop, $data) {
    if (!empty($this->_fields_array)) {
      if (is_null($data)) {
        $prop = $this->_fields_array;
      } else {
        $prop = $this->_diff($this->_fields_array, $data);
      }
    } else {
      $prop = $data;
    }
  }
  
  /**
   * Retorna o número de registros do result
   * @return integer
   */
  public function count() {
    return $this->count;
  }
  
  /**
   * Retorna algumas propriedades dos mappers. Seu retorno pode variar de mapper pra mapper,
   * mas os retornos básicos sao:
   * - Nome de colunas
   * - Nome da entidade atual
   * - Filtro atual (alguns mappers)
   */
  public function __get($name) {
    return $this->getField($name);
  }
  
  // --------------------- INICIO DOS METODOS DE ACESSO POR ARRAY ----
  
  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para adicionar um valor ao objeto (ex: $obj[] = 'valor').
   */
  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      //$this->data[] = $value;
      $this->data[] = $value;
      //throw new CoreException('Não é possível definir valores para um campo sem nome');
      // TODO: deixar ele acrescentar valores, desde que os data tenham sido definidos
      // e que o valor a ser adicionado não ultrapasse o numero de campos definidos
    } else {
      // sanitize
      $offset = strtolower($offset);
      
      $this->data[ $offset ] = $value;
      //$this->saveState();
    }
  }
  
  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para verificar se o elemento existe (ex: isset($obj[1]) ).
   */
  public function offsetExists($offset) {
    return isset($this->data[strtolower($offset)]);
  }

  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para deletar um elemento do objeto (ex: unset($obj[1]) ).
   */
  public function offsetUnset($offset) {
    $this->offsetSet($offset, null);
  }

  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para retornar o valor de um elemento existente (ex: $var = $obj[1] ).
   */
  public function offsetGet($offset) {
    if (is_numeric($offset)) {
      if (!is_array($this->data)) return null;
      
      $data = array_values($this->data);
      
      /*if (!isset($data[ $offset ]))
        throw new ModelException('Campo '.$offset.' não existe');*/
      
      $val = $data[ $offset ];
      
    } else {
      // sanitize
      $offset = strtolower($offset);
      
      /*if (!isset($this->data[ $offset ]))
        throw new ModelException('Campo '.$offset.' não existe');*/
      
      $val = $this->data[ $offset ];
    }
    
    if ($val instanceof query\Expression) {
      // se o valor for uma expressao, usar o valor do prestine como referencia
      // para executar a expressao
      $val = $this->_evalMathExpression($this->_pristine_data, $val);
    }
    
    return $val;
  }
  
  
  // --------------------- FIM DOS METODOS DE ACESSO POR ARRAY ----
  
}