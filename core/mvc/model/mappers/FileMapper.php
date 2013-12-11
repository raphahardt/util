<?php

namespace Djck\mvc\mappers;

use Djck\CoreException;

use Djck\mvc\Mapper;
use Djck\mvc\interfaces;

/**
 * Description of FileMapper
 *
 * @author usuario
 */
// persistencia do mapper em arquivo
// collection: ~16.000 (20 campos)
class FileMapper extends Mapper implements interfaces\FileSystemMapper {
  
  const DEFAULT_ID_NAME = 'id';
  const DEFAULT_DELETE_NAME = 'exc';
  const DEFAULT_DELETE_DATE_NAME = 'exc_em';
  
  // caracteres de separação de dados de arquivo
  protected $delimiter = ',';
  protected $closure = '"';
  protected $escape = '\\';
  protected $newline = PHP_EOL;
  
  protected $fields;
  
  protected $permanent_delete = true;
  
  // serve como base para dados que vierem para serem alterados ou inseridos
  private $_fields_array = array();
  
  /**
   * File pointer, para operações de abrir arquivo
   * @var resource 
   */
  private $fp;
  
  /**
   * Fecha qualquer resource de arquivo aberto, assim como "deslocka"-o
   */
  public function __destruct() {
    if ($this->fp) {
      flock($this->fp, LOCK_UN); // fecha qualquer lock aberto
      fclose($this->fp);
    }
  }
  
  public function init() {
    
    if (!isset($this->entity))
      throw new CoreException('Obrigatorio definir um arquivo');
    
    if (!isset($this->fields))
      throw new CoreException('Obrigatorio definir os campos');
    
    $this->_fields_array = array();
    foreach ($this->fields as $f) {
      $this->_fields_array[$f] = null;
    }
    // inicia os dados já com os campos definidos
    $this->nullset();
    
    // select registros logo de inicio
    if (!is_file($this->entity)) {
      throw new CoreException('Arquivo '.basename($this->entity).' não existe');
    }
    
    // OLD
    //$input = file_get_contents($this->entity);
    //$this->_formatInput($input);
    
    // limpa o result interno
    $this->clearResult();
    
    // le o que está no arquivo e passa para o result intero
    if ($this->startLock(LOCK_SH)) { // (sh = shared for read)
      $this->read();
      $this->endLock();
    } else {
      throw new CoreException('Houve um problema ao ler o arquivo '.basename($this->entity));
    }
    
    // seta o autoincremente para o ultimo encontrado no arquivo
    // autoincrement foi alterado para microtime(true) (05/11/13)
    //$this->autoIncrement($this->result[$this->count-1]['data'][$this->getPointer()]+1);
    
  }
  
  protected function startLock($lock) {
    if ($this->fp) return false;
    
    $this->fp = fopen($this->entity, $lock == LOCK_EX ? "r+b" : "rb");
    // o uso do r+b em vez de w+b é que a truncagem está sendo feita na mão
    // isso é necessário pois, ao verificar se o arquivo já está sendo gravado por outro
    // processo (lockado), o php não pode truncar o arquivo (w trunca o arquivo ao abrir)
    // portanto, o 'r' lê o arquivo e o '+' permite que ele escreva
    // 'b' porque eu gravo o arquivo em formato binário, para evitar inconsistencia
    // de caracteres especiais como \n (windows traduz automaticamente pra \r\n em arquivos não-binarios)
    // e de encoding

    return flock($this->fp, $lock); // do an exclusive lock (sh = shared for read)
  }
  
  protected function endLock() {
    flock($this->fp, LOCK_UN); // fecha qualquer lock aberto
    fclose($this->fp);
    unset($this->fp);
  }
  
  public function nullset() {
    parent::nullset();
    $this->data = $this->_fields_array; // limpa com os campos da tabela
  }
  
  public function set($data) {
    parent::set($data);
    $this->data = $this->_diff($this->_fields_array, $this->data); // preenche os campos que faltaram
  }
  
  public function push($data = null, $flag = self::FRESH) {
    if (is_array($data) && !empty($data)) {
      $data = $this->_diff($this->_fields_array, $data); // preenche os campos que faltaram
    }
    return parent::push($data, $flag);
  }
  
  public function unshift($data = null, $flag = self::FRESH) {
    if (is_array($data) && !empty($data)) {
      $data = $this->_diff($this->_fields_array, $data); // preenche os campos que faltaram
    }
    return parent::unshift($data, $flag);
  }
  
  /**
   * Processa os dados do objeto para o arquivo.
   * Cada tipo de mapper deve alterar essa função para fazer a conversão correta dos
   * dados.
   * @param array $input
   * @return string
   */
  protected function _formatOutput($input) {
    return str_putcsv($input, $this->delimiter, $this->closure).$this->newline;
  }
  
  /**
   * Processa os dados do arquivo para o objeto.
   * Cada tipo de mapper deve alterar essa função para fazer a conversão correta dos
   * dados.
   * @param string $output String com todos os dados, formatados da forma que salvou
   * @return array
   */
  protected function _formatInput($output) {
    
    $values = str_getcsv(trim($output), $this->delimiter, $this->closure, $this->escape);
    
    $count = count($values);
    for($i=0;$i<$count;$i++) {
      if (is_numeric($values[$i])) {
        $values[$i] = (float)$values[$i];
      }
    }
    return $values;
    
  }
  
  protected function read() {
    
    if (!is_file($this->entity))
      return false;
    
    if (!$this->fp)
      return false;
    
    //$success = true; TODO: usar essa var
    
    // vai com ponteiro pro inicio do arquivo
    fseek($this->fp, 0, SEEK_SET); 
    
    $header = null;
    while (($buffer = fgets($this->fp, 4096)) !== false) {
      $final .= $buffer;
      if (!$buffer) {
        continue;
      }
      if (!$header) {
        $header = $this->_formatInput($buffer);
        continue; // pega cabeçalho
      }
      // le cada linha do arquivo
      $values = $this->_formatInput($buffer);

      // formata os dados de maneira correta
      $data=array();
      for($i=0;$i<count($header);$i++) {
        $data[ $header[$i] ] = $values[$i];
      }

      // ve se o item ja tem
      if (($offset = $this->find($data[ $this->getPointer() ])) !== false) {
        // update, se encontrar
        $this->set($data);
        $this->refresh();
      } else {
        // insert, se não encontrar
        $this->push($data);
      }

    }
    if (!feof($this->fp)) {
      throw new CoreException('O arquivo '.basename($this->entity).' não pode ser lido completamente');
    }

    return true;
  }
  
  /**
   * Salva as alterações dos registros no arquivo
   * @return boolean
   */
  public function commit() {
    
    if (!is_file($this->entity))
      return false;
    
    $success = true;
    
    if (!$this->startLock(LOCK_EX)) {
      return false;
    }
    
    // dá um "refresh" no result interno, antes de salvar
    // isso é necessario pois o arquivo irá ser truncado antes de ser salvo, e para isso
    // é preciso que os dados dele estejam atualizados antes de salvar.
    // se não fizer isso, se dois usuarios acessarem o mesmo arquivo ao mesmo tempo,
    // informações podem ser perdidas
    $this->read();
    
    // começa a escrita
    ftruncate($this->fp, 0);      // truncate file
    fseek($this->fp, 0, SEEK_SET); // vai com ponteiro pro inicio do arquivo

    // header
    $header = $this->_formatOutput($this->fields);
    $success && $success = (fwrite($this->fp, $header) !== false);

    // contents
    $count = 0;
    foreach ($this->result as $r) {
      $line = $this->_formatOutput($r['data']);
      $success && $success = (fwrite($this->fp, $line) !== false);

      if ($count % 200 == 0) {
        fflush($this->fp); // flush each 200 regs
      }
      ++$count;
    }

    fflush($this->fp); // flush output before releasing the lock

    $this->endLock();
    
    return $success && parent::commit();
  }
  
  /**
   * Deleta o arquivo (entidade)
   * @return boolen
   */
  public function destroy() {
    $success = is_file($this->entity) ? unlink($this->entity) : false;
    if ($success)
      $this->clearResult();
    
    return $success;
  }
  
  /**
   * Define os campos dos registros. Nos arquivos servirão de cabeçalho. Nos outros formatos
   * como json ou xml, serão propriedades
   * (É protected pois não é possivel alterar os campos em tempo de execução, só ao criar a
   * instancia __construct)
   * @param mixed $entity
   * @access protected
   */
  public function setFields($fields) {
    $this->fields = $fields;
  }
  
  /**
   * Retorna os campos definidos
   * @return mixed
   */
  public function getFields() {
    return $this->fields;
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
    
    if (in_array($field, $this->fields)) {
      return $field;
    }
  }
  
}