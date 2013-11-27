<?php

/**
 * Classe que faz cache persistente das informações de um objeto que precisa guardar
 * um numero muito grande de registros.
 * 
 * Basicamente este objeto é um array como qualquer outro, porém ele foi desenhado para
 * suportar até milhões de elementos sem estourar a memória. Ele faz uma paginação dos
 * elementos e os salva em arquivo quando não está utilizando, e vai trocando as páginas
 * conforme necessário. Este processo, porém, é invisível para quem utiliza esta classe.
 * 
 * Exemplo de uso
 * --------------
 * <code>
 * <?php
 * $array = new StorageArray();
 * $array[] = 'elemento';          // adiciona um elemento no array
 * $array->unshift('elemento2');   // insere no inicio um elemento (array_unshift)
 * echo count($array);             // 2 (ou $array->count())
 * var_dump(isset($array[0]));     // true
 * var_dump($array[2]);            // NULL
 * 
 * foreach ($array as $element) {
 *   echo $element;               // imprime cada elemento
 * }
 * ?>
 * </code>
 * 
 * Problemas conhecidos
 * --------------------
 * - O storage não suporta index não-numericos (ex: $obj['teste']);
 * - Se alterar um valor de um index que for maior que o número de elementos do storage
 * (por exemplo, o storage tem 60 elementos e altera: $obj[190] = x), os métodos pop() e splice()
 * não funcionarão corretamente;
 * - O método splice() não está totalmente implementado e tem problemas de performance dependendo
 * da situação;
 *
 * @todo terminar splice()
 * @todo arrumar problema de key maiores que o numero de elementos com pop() e splice()
 * @author sistema13
 * @version 0.1 (27/11/2013)
 */
class StorageArray extends ArrayIterator/* implements ArrayAccess, Countable, Iterator*/ {
  
  /**
   * Dados do objeto
   * @var mixed[] 
   */
  private $storage = array();
  
  /**
   * Nome do arquivo único que identifica o cache no storage
   * @var string 
   */
  private $objectName = '';
  
  /**
   * Offset atual. É o que identifica em que arquivo de cache estou editando/visualizando
   * Pense neste número como a "página" em que o storage está abrindo no momento.
   * @var int 
   */
  private $offset = 0;
  
  /**
   * Quantidade de elementos que serão guardados em cada arquivo de cache.
   * @var int
   */
  private $limit = 3000;
  
  /**
   * Nome do arquivo do cache. Consiste de: [codigounico]_[offset]
   * Exemplo: 4320j0fj29034238_100 (offset = 100)
   * @var string 
   */
  private $path;
  
  /**
   * Quantidade de elementos total do storage
   * @var int 
   */
  private $count = 0;
  
  /**
   * Ponteiro do iterador (dinâmico)
   * @var int 
   */
  private $iterator_index = 0;
  
  /**
   * Menor valor que o storage tem como key.
   * @var int 
   */
  private $iterator_index_min = 0;
  
  /**
   * Maior valor que o storage tem como key.
   * @var int 
   */
  private $iterator_index_max = 0;
  
  /**
   * Fator de soma para elementos do array.
   * 
   * Quando um elemento é adicionado com unshift(), na teoria todos os elementos são
   * "empurrados" pra frente e o novo elemento ganha o key 0. Porém isso é muito pesado
   * para esta classe, pois requer reescrever (N / limit) arquivos que já estão guardando cache
   * dos outros registros, onde N é o numero de elementos do storage. Para solucionar este 
   * problema, ao usar unshift() é adicionado um elemento com index negativo, e este 
   * número incrementa 1. Ao procurar o index, este fator sempre é somado ao index 
   * original, fazendo parecer que o elemento -1 seja o 0
   * @var int 
   */
  private $beggining_sum = 0;
  
  /**
   * Fator de soma para elementos que foram retirados do array pelo splice().
   * 
   * Um grande problema é reescrever todos os cache quando o key dos elementos precisam
   * ser redefinidos. No melhor caso, somente 1 arquivo será alterado, e no pior, todos os
   * (N / limit) vão ser alterados, onde N é o número de elementos do storage.
   * Para resolver, os elementos que forem retirados do storage terão esse fator aumentado
   * +N, onde N é o tamanho do splice.
   * 
   * Este array será preenchido da seguinte forma:
   * <code>array( 4 => 1, 9 => 2)</code>
   * Onde o key é o offset que sofreu splice e o value é a quantidade de elementos que foi
   * retirada a partir daquele ponto.
   * O fator será somado somente para offsets que ultrapassarem cada key.
   * Por exemplo, se eu buscar pelo elemento 2, ele retornará 2 do storage, pois não ultrapassou nenhum.
   * Se procurar pelo elemento 5, ele retornará o elemento 6 do storage, pois a partir do 4, o fator a
   * ser somado é 1. Se procurar pelo elemento 12, ele retornará o elemento 15, pois será somado 1
   * por ter ultrapassado o offset 4 e +2 por ter ultrapassado o offset 9.
   * 
   * @var int[]
   */
  private $sliced_sum = array();
  
  /**
   * Construtor da classe
   */
  public function __construct() {
    parent::__construct();
    
    $this->path = TEMP_PATH.DS.'storage-array';
    
    if (!is_dir($this->path)) {
      mkdir($this->path, 0777);
    }
    
    //$this->objectName = session_id();
    $this->objectName = md5(uniqid());
    
    $this->path .= DS.$this->objectName.'_';

    // limpa diretorio
    //$this->_cleanDiretory();
    tickin('start');
  }
  
  /**
   * Limpa o diretorio de cache. 
   * Limpa apenas os arquivos que utilizou
   */
  private function _cleanDiretory() {
    $path = dirname($this->path);
    if ($dh = opendir($path)) {
      while (($file = readdir($dh)) !== false) {
        if (strpos($file, $this->objectName) !== false)
          unlink($path . DS .$file);
      }
      closedir($dh);
    }
  }
  
  /**
   * Limpa o diretorio de cache ao ser destruído.
   * Limpa apenas os arquivos que utilizou
   */
  public function __destruct() {
    $this->_cleanDiretory();
  }
  
  /**
   * Wrapper para array_push
   * @see http://php.net/array_push
   * @param mixed $value Elemento a ser adicionado no storage
   * @return int Novo número de elementos do storage
   */
  public function push($value) {
    $elems = func_get_args();
    foreach ($elems as $elem) {
      $this->offsetSet(null, $elem);
    }
    return $this->count();
  }
  
  /**
   * Wrapper para array_unshift
   * @see http://php.net/array_unshift
   * @param mixed $value Elemento a ser inserido no storage
   * @return int Novo número de elementos do storage
   */
  public function unshift($value) {
    $elems = func_get_args();
    foreach ($elems as $elem) {
      // fator de soma de inicio (unshift)
      ++$this->beggining_sum;
      $this->offsetSet(0, $elem);
    }
    return $this->count();
  }
  
  /**
   * Wrapper para array_pop
   * @see http://php.net/array_pop
   * @return mixed Elemento retirado do storage
   */
  public function pop() {
    $elem = $this->offsetGet($this->count-1);
    $this->offsetUnset($this->count-1);
    return $elem;
  }
  
  /**
   * Wrapper para array_shift
   * @see http://php.net/array_shift
   * @return mixed Elemento retirado do storage
   */
  public function shift() {
    $elem = $this->offsetGet(0);
    $this->offsetUnset(0);
    // fator de soma de inicio (unshift)
    --$this->beggining_sum;
    return $elem;
  }
  
  /**
   * Wrapper para array_splice
   * Este método é pesado dependendo da situação.
   * Caso mais rápido: o número de elementos retirados é igual ao número de elementos a
   * serem adicionados (replacement)
   * Caso rápido: apenas retirar elementos, replacement vazio
   * Caso mais lento: adicionar elementos e retirar menos (retirar mais entra como caso
   * rápido também
   * @see http://php.net/array_splice
   * @param int $offset 
   * @param int $length
   * @param array $replacement
   * @return int Novo número de elementos do storage
   */
  public function splice($offset, $length = null, $replacement = array()) {
    if (!is_array($replacement)) {
      $replacement = (array)$replacement;
    }
    // se não for adicionar nada, fazer alguns metodos considerados "rapidos"
    if (empty($replacement)) {
      // simular pop
      if ($offset < 0 && $length === null) {
        $elems = array();
        for ($i=$offset;$i<0;$i++) {
          $elems[] = $this->pop();
        }
        return $elems;
      }
      // simular shift
      elseif ($offset == 0 && $length > 0) {
        $elems = array();
        for ($i=0;$i<$length;$i++) {
          $elems[] = $this->shift();
        }
        return $elems;
      }
      
    } else {
      // se tiver replacement, ainda tentar algumas estruturas "rapidas"
      
      // simular unshift
      if ($offset == 0 && $length == 0) {
        foreach ($replacement as $elem) {
          $this->unshift($elem);
        }
        return array();
      }
      // simular unshift
      elseif ($offset == $this->count() && $length == 0) {
        foreach ($replacement as $elem) {
          $this->push($elem);
        }
        return array();
      }
    }
    // caso mais rápido (performance)
    // se a quantidade a ser adicionada é a mesma a ser retirada, apenas trocar seus
    // valores
    if ($length == count($replacement)) {
      $elems = array();
      foreach ($replacement as $elem) {
        $elems[] = $this->offsetGet($offset);
        $this->offsetSet($offset, $elem);
        ++$offset;
      }
      return $elems;
    }
    // caso rápido (performance)
    // se vai apenas retirar elementos, é mais simples apenas marcar os excluidos com a flag
    //
    if (empty($replacement)) {
      // acerta valores
      if ($offset < 0) {
        $offset = $this->count() + $offset; // count + (-offset) = count - offset
      }
      if ($length === null) {
        $length = $this->count();
      } else {
        $length += $offset;
      }
      $elems = array();
      for ($i = $offset;$i < $length;$i++) {
        $elems[] = $this->offsetGet($i);
        // para ocupar menor memoria, os elementos vao ser apagados
        $this->offsetUnset($i); // TODO ver o que isso impacta
      }
      // guarda o valor no fator de soma dos sliceds
      $this->sliced_sum[$offset] = $length - $offset;
      ksort($this->sliced_sum); // precisa estar sempre ordenado por key, senão não funciona
      return $elems;
    }
    // se chegar aqui, é porque a implementação de splice() para o caso é
    // pesado demais ou realmente não há solução prática
    throw new BadMethodCallException('splice(): modo de uso não suportado');
  }
  
  /**
   * Normaliza o offset pretendido para o offset real.
   * 
   * Serve para os casos onde o offset pretendido corresponde a outro offset real.
   * Por exemplo, ao usar unshift() que adiciona um elemento negativo, essa funcao
   * transforma 0 (pretendido) em -1 (real).
   * Ou se o storage tem os elementos [0,1,2,3] e usar splice(2,1), ela vai transformar
   * 2 (pretendido) em 3 (real).
   * @param int $offset Offset pretendido (no caso de $inverse = TRUE, offset real)
   * @param boolean $inverse Se TRUE, pede-se o real e devolve-se o pretendido (contrario)
   * @return int
   * @throws InvalidArgumentException
   */
  private function _normalizeOffset($offset, $inverse = false) {
    if (!is_numeric($offset)) {
      // não suporta keys não-numericos
      throw new InvalidArgumentException('StorageArray não suporta key não-numericos ('.$offset.' experado int)');
    }
    // real -> retorna o pretendido
    if ($inverse) {
      // fator de soma de inicio (unshift)
      $offset = $offset + $this->beggining_sum;
      
      // fator de soma dos sliced
      foreach ($this->sliced_sum as $factor_offset => $factor_length) {
        if ($offset >= $factor_offset) {
          $offset -= $factor_length;
        }
      }
      
    } 
    // pretendido -> retorna o real
    else {
      // fator de soma de inicio (unshift)
      $offset = $offset - $this->beggining_sum;
      
      // fator de soma dos sliced
      foreach ($this->sliced_sum as $factor_offset => $factor_length) {
        if ($offset >= $factor_offset) {
          $offset += $factor_length;
        }
      }
    }
    return $offset;
  }
  
  /**
   * Verifica se o elemento realmente existe no storage.
   * Pode ser usado como: isset($obj[0])
   * @see http://php.net/arrayaccess
   * @param int $offset
   */
  public function offsetExists($offset) {
    // normaliza offset com fatores
    $offset = $this->_normalizeOffset($offset);
    
    $this->checkCacheLoad($offset);
    isset($this->storage[$offset]);
  }
  
  /**
   * Retorna o elemento do stogare
   * Pode ser usado como: $obj[0]
   * @see http://php.net/arrayaccess
   * @param int $offset
   * @return mixed
   */
  public function &offsetGet($offset) {
    // normaliza offset com fatores
    $offset = $this->_normalizeOffset($offset);
    
    $this->checkCacheLoad($offset);
    return $this->storage[$offset];
  }

  /**
   * Define um elemento do storage
   * Pode ser usado como: $obj[0] = x ou $obj[] = x
   * @see http://php.net/arrayaccess
   * @param int $offset
   * @param mixed $value
   */
  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      $offset = $this->count;
    }
    // normaliza offset com fatores
    $offset = $this->_normalizeOffset($offset);
    
    $this->checkCacheSave($offset);
    if (!isset($this->storage[$offset])) {
      ++$this->count;
    }
    $this->storage[$offset] = $value;
    // se for adicionado um valor que é menor que o que ja tem, reordenar
    // TODO pensar em algo melhor e mais eficaz
    if ($offset < $this->iterator_index_min) {
      ksort($this->storage);
    }
    // define os novos minimos e maximos
    $this->iterator_index_min = min($this->iterator_index_min, $offset);
    $this->iterator_index_max = max($this->iterator_index_max, $offset);
  }

  /**
   * Exclui um elemento do storage.
   * Pode ser usado como: unset($obj[0])
   * @see http://php.net/arrayaccess
   * @param int $offset
   */
  public function offsetUnset($offset) {
    // normaliza offset com fatores
    $offset = $this->_normalizeOffset($offset);
    
    $this->checkCacheLoad($offset);
    unset($this->storage[$offset]);
    --$this->count;
  }
  
  /**
   * Retorna o número de elementos total do storage
   * Pode ser usado como: count($obj)
   * @see http://php.net/countable
   * @return int
   */
  public function count() {
    return $this->count;
  }
  
  /**
   * Limpa o storage.
   */
  public function clean() {
    $this->count = 0;
    $this->iterator_index_min = 0;
    $this->iterator_index_max = 0;
    $this->beggining_sum = 0;
    $this->sliced_sum = array();
    $this->storage = array();
  }
  
  /**
   * Muda o offset (a "página") do storage.
   * Ao ser alterada, o método getCache() passa a ler de outro arquivo.
   * @access private
   * @param int $page
   */
  private function setPage($page) {
    $this->offset = (int)($page * $this->limit);
  }
  
  /**
   * Retorna a "página", de acordo com o offset informado.
   * Exemplo: offset = 102 com limit = 50: página 2 (terceira página)
   *          offset = 4 com limit = 50: página 0 (primeira pagina)
   * @access private
   * @param int $offset
   */
  private function getPageByOffset($offset) {
    return (int)floor($offset / $this->limit);
  }
  
  /**
   * Retorna TRUE se $offset estiver fora da "página" atual.
   * Exemplo: $offset = 100 na pagina 0 com limit = 100: TRUE (fora)
   *          $offset = 99 na pagina 0 com limit = 100: FALSE (dentro)
   * @access private
   * @param int $offset
   * @return boolean
   */
  private function isOutOfRange($offset) {
    return ($offset < $this->offset) || ($this->offset + $this->limit <= $offset);
  }
  
  /**
   * Salva a "página" no arquivo
   * @access private
   */
  protected function setCache() {
    file_put_contents($this->path.$this->offset, serialize($this->storage));
    
    tickin('save');
  }
  
  /**
   * Muda o storage de "página".
   * @access private
   */
  protected function getCache() {
    if (!is_file($this->path.$this->offset)){
      $this->storage = array();
      return;
    }
    $this->storage = unserialize(file_get_contents($this->path.$this->offset));
    reset($this->storage);
    
    tickin('load');
  }
  
  /**
   * Verifica se, de acordo com $offset, precisa mudar de "página".
   * @access private
   * @param int $offset
   */
  private function checkCacheLoad($offset) {
    if ($this->isOutOfRange($offset)) {
      //if (!is_file($this->path.'-'.$this->offset)){
        $this->setCache();
      //}
      
      $newpage = $this->getPageByOffset($offset);
      $this->setPage($newpage);
      
      $this->getCache();
    }
  }
  
  /**
   * Verifica se, de acordo com $offset, precisa salvar a "página".
   * @access private
   * @param int $offset
   */
  private function checkCacheSave($offset) {
    if ($this->isOutOfRange($offset)) {
      $this->setCache();
      
      $newpage = $this->getPageByOffset($offset);
      $this->setPage($newpage);
      
      $this->getCache();
    }
  }

  /**
   * Retorna o elemento atual em uma iteração
   * @see http://php.net/iterator
   * @return mixed
   */
  public function current() {
    return current($this->storage);
  }

  /**
   * Retorna o key atual em uma iteração
   * @see http://php.net/iterator
   * @return int
   */
  public function key() {
    $this->iterator_index = key($this->storage);
    return $this->_normalizeOffset($this->iterator_index, true); // fatores de soma
  }

  /**
   * Vai para o próximo registro do storage em uma iteração
   * @see http://php.net/iterator
   * @return mixed
   */
  public function next() {
    ++$this->iterator_index;
    $return = next($this->storage);
    if (!$return) {
      while (!($return = $this->storage[$this->iterator_index]) 
              && $this->iterator_index <= $this->iterator_index_max) {
        $this->checkCacheLoad($this->iterator_index);
        ++$this->iterator_index;
      }
      //$return = reset($this->storage);
    }
    return $return;
  }

  /**
   * Vai para o primeiro registro do storage em uma iteração
   * @see http://php.net/iterator
   * @return null
   */
  public function rewind() {
    $this->iterator_index = $this->iterator_index_min;
    $this->checkCacheLoad($this->iterator_index);
    reset($this->storage);
  }

  /**
   * Retorna se o elemento é valido em uma iteração
   * @see http://php.net/iterator
   * @return boolean
   */
  public function valid() {
    $key = key($this->storage);
    $var = ($key !== NULL && $key !== FALSE);
    return $var;
  }
  
  /**
   * Alias para push().
   * Método implementado do ArrayIterator
   * @see http://php.net/manual/en/arrayiterator.append.php
   * @param mixed $value
   */
  public function append($value) {
    $this->push($value);
  }
  
  /**
   * Define o ponteiro interno numa iteração.
   * Método implementado do ArrayIterator
   * @see http://php.net/manual/en/arrayiterator.append.php
   * @param int $position
   */
  public function seek($position) {
    // normaliza offset com fatores
    $position = $this->_normalizeOffset($position);
    $this->iterator_index = $position;
    $this->checkCacheLoad($this->iterator_index);
  }

}