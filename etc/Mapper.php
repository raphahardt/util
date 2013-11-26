<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// registra os mappers principais
$package = 'core/mvc/model/mappers';
Core::register('BDMapper', $package);
Core::register('TempMapper', $package);
Core::register('FileMapper', $package);
Core::register('FileBase64Mapper', $package);
Core::register('JsonMapper', $package);
Core::register('XmlMapper', $package);
Core::register('SessionMapper', $package);

// interfaces /////////////////////////////////////
/**
 * Interface que define todos os Mappers. É o padrão.
 */
interface DefaultItfMapper {
  /**
   * Função que é como se fosse o "constructor" do Mapper, porém só é chamado quando
   * ele é linkado ao Model
   */
  public function init();
  public function commit();
}
/**
 * Interface que define todos os Mappers que tem ligação com banco de dados.
 * Ela contem mais 4 métodos para a conversação correta com os dados: select, update,
 * delete e insert. Os Behaviors irão verificar se o Mapper implementa essa interface
 * e utilizar os metodos corretos em cada momento.
 */
interface DatabaseItfMapper extends DefaultItfMapper {
  public function select();
  public function update();
  public function delete();
  public function insert();
  //public function rollback();
}
/**
 * Interface que define todos os Mappers que escrevem em arquivos.
 * Ela contem mais um método destroy() que apaga o arquivo.
 */
interface FileItfMapper extends DefaultItfMapper {
  public function destroy();
}
/**
 * Interface que define todos os Mappers que precisam manipular seus dados de forma
 * aninhada. Por exemplo, os xmls.
 * Ela tem mais alguns metodos essenciais para essa manipulação. Os Behaviors irão 
 * verificar se o Mapper implementa essa interface
 * e utilizar os metodos corretos em cada momento.
 */
interface NestedItfMapper extends DefaultItfMapper {
  // addnode, removenode, etc...
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
 * @package mvc
 * @subpackage model
 * 
 * @property-read mixed $nome_do_campo Campo do Mapper
 * 
 * @author Raphael Hardt <sistema13@furacao.com.br>
 * @version 0.1 (24/09/2013)
 */
abstract class Mapper implements ArrayAccess {
  
  const FRESH = 0;
  const PERSISTED = 1;
  
  // onde dados do registro ficam guardados
  protected $data;
  
  // entidade que guarda a persistencia do mapper
  // pode ser uma tabela, um nome de arquivo, ou até nada (dados temporarios)
  protected $entity;
  
  // identificador do registro
  // pode ser uma SQLExpression (BD), o numero da linha (file), um id, um index de array, etc..
  protected $pointer = array('id'=>null);
  
  // guarda os registros retornados pelo find() ou filter(), e o ponteiro quem vai lidar
  // com o registro unico. o mapper funcionará como um recordset
  protected $result = array();
  protected $internal_pointer = 0;
  protected $count = 0;
  
  private $autoincrement = 1;
  
  public function __construct() {
    $this->result = new CacheArray($this);
  }
  
  /**
   * Retorna o proximo valor do autoincrementador interno do Mapper.
   * É usado para Mappers que não tem definidos um autoincrementador nativo, como escrever
   * em arrays ou arquivos (txt, xml, etc.). O BDMapper, por exemplo, não precisa utiliza-lo,
   * pois o autoincrementador vem do proprio banco de dados
   * @access protected
   * @return integer
   */
  protected function autoIncrement($set=null) {
    if (isset($set)) {
      $this->autoincrement = (int)$set;
      return;
    }
    return $this->autoincrement++;
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
    if (($offset = $this->_find(array(key($this->pointer)=>$pointer))) !== false) {
      $this->set($this->result[$offset]);
      // após encontrar, o pointeiro interno deve apontar agora para o registro no result
      $this->internal_pointer = $offset; // VER O QUE ISSO IMPACTA
      return $offset;
    }
    return false;
  }
  
  /**
   * Limpa os dados do registro atual. Não modifica o result.
   * @return void
   */
  public function nullset() {
    $this->data = null;
    $this->pointer = array(key($this->pointer) => null);
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
    $id = key($this->pointer);
    $values = array_change_key_case($data['data'], CASE_LOWER);
    
    $this->data = $values;
    $this->pointer = array($id => $data['data'][$id]);
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
    $this->set($data);
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
    
    if (!isset($data[ key($this->pointer) ]))
      $data[ key($this->pointer) ] = $this->autoIncrement();
    
    $this->result[ $this->count++ ] = array(
        'data' => $data,
        //'pointer' => $data[ key($this->pointer) ], // valor do ponteiro
        'flag' => $flag, // flag é usado nos mappers de banco de dados para saber se o registro foi salvo ou não no bd
    );
    $this->internal_pointer = $this->count-1;
  }
  
  /**
   * Remove o ultimo registro do result. Segue a ideia do array_pop()
   * @return array Os dados do registro deletado
   */
  public function pop() {
    $result = array_pop($this->result);
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
    array_splice($this->result, $offset, $len);
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
      $pointer = $this->data[ key($this->pointer) ];
    }
    if (($offset = $this->find($pointer)) !== false) {
      array_splice($this->result, $offset, 1);
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
    
    if (!isset($data[ key($this->pointer) ]))
      $data[ key($this->pointer) ] = $this->autoIncrement();
    
    array_unshift($this->result, array(
        'data' => $data,
        //'pointer' => $data[ key($this->pointer) ], // valor do ponteiro
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
    $result = array_shift($this->result);
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
  
  /**
   * Anda com o ponteiro interno até o primeiro registro e o retorna.
   * @return array Os dados do registro
   */
  public function first() {
    $this->internal_pointer = 0;
    $data = $this->result[$this->internal_pointer];
    $this->set($data);
    return $data ? $data['data'] : false;
  }
  
  /**
   * Anda com o ponteiro interno até o próximo registro e o retorna. Retorna FALSE se for o último
   * @return array Os dados do registro
   */
  public function next() {
    ++$this->internal_pointer;
    $data = $this->result[$this->internal_pointer];
    $this->set($data);
    return $data ? $data['data'] : false;
  }
  
  /**
   * Anda com o ponteiro interno até o registro anterior e o retorna. Retorna FALSE se for o primeiro
   * @return array Os dados do registro
   */ 
  public function prev() {
    --$this->internal_pointer;
    $data = $this->result[$this->internal_pointer];
    $this->set($data);
    return $data ? $data['data'] : false;
  }
  
  /**
   * Anda com o ponteiro interno até o último registro e o retorna.
   * @return array Os dados do registro
   */
  public function last() {
    $this->internal_pointer = $this->count-1;
    $data = $this->result[$this->internal_pointer];
    $this->set($data);
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
    
    if (isset($this->result[ $this->internal_pointer ]) && $data[key($this->pointer)])
      $this->result[ $this->internal_pointer ]['data'] = $data;
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
   * Ordena os registros do result por uma coluna. Se coluna for NULL, é ordenado pelo id (pointer)
   * @param string $column Nome da coluna
   * @param boolean $desc Se TRUE, ordena descrescente, senão, crescente (padrão)
   * @return boolean TRUE
   */
  public function sort($column = null, $desc = false) {
    if (!isset($column))
      $column = key($this->pointer);
    
    $this->_quicksort($column, 0, $this->count-1, $desc === true || strtolower($desc) === 'desc');
    return true;
  }
  
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
  protected function _quicksort($col, $left, $right, $inverse = false) {
    $i = $left;
    $j = $right;
    $pivot = (int)(($i + $j) / 2);
    $val_pivot = $this->result[$pivot]['data'][$col];
    while ($i < $j) {
      if ($inverse) {
        while ($this->_compare($this->result[$i]['data'][$col], $val_pivot) > 0) { // menor
          ++$i;
        }
        while ($this->_compare($this->result[$j]['data'][$col], $val_pivot) < 0) { // maior
          --$j;
        }
      } else {
        while ($this->_compare($this->result[$i]['data'][$col], $val_pivot) < 0) { // menor
          ++$i;
        }
        while ($this->_compare($this->result[$j]['data'][$col], $val_pivot) > 0) { // maior
          --$j;
        }
      }
      if ($i <= $j) {
        $aux = $this->result[$i];
        $this->result[$i] = $this->result[$j];
        $this->result[$j] = $aux;
        ++$i;
        --$j;
      }
    }
    if ($j > $left) $this->_quicksort($col, $left, $j, $inverse);
    if ($i < $right) $this->_quicksort($col, $i, $right, $inverse);
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
  
  public function setFields($fields) {
    
  }
  
  public function getFields() {
    
  }
  
  /**
   * Define qual coluna será o "primary key" dos registros.
   * @param string $pointer
   * @param mixed $initval Se definido, o id (pointer) do registro atual sera iniciado já com este valor
   */
  public function setPointer($pointer, $initval = null) {
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
    // sanitize
    $field = strtolower($name);
    if (isset($this->data[$field])) {
      return $field;
    }
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
      
      return $data[ $offset ];
      
    } else {
      // sanitize
      $offset = strtolower($offset);
      
      /*if (!isset($this->data[ $offset ]))
        throw new ModelException('Campo '.$offset.' não existe');*/
      
      return $this->data[ $offset ];
    }
  }
  
  
  // --------------------- FIM DOS METODOS DE ACESSO POR ARRAY ----
  
}