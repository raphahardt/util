<?php

namespace Djck\database;

use Djck\Core;
use Djck\database\DbcConfig;
use Djck\system\AbstractSingleton;

Core::registerPackage('Djck\database:dbc\exceptions');

class Dbc extends AbstractSingleton {

  private $num_rows = 0;
  private $insert_id = 0;
  private $affected_rows;
  private $charset;
  private $autocommit = true;
  //stmt
  /**
   *
   * @var \mysqli_stmt[] 
   */
  private $stmt = array();
  private $stmt_binds = array();
  private $stmt_values = array();
  private $stmt_query = array();
  private $stmt_index = 0;
  //result
  /**
   * Objeto de conexão com banco.
   * 
   * @var \mysqli 
   */
  protected $con = null; // resource de conexao
  protected $schema;
  
  /**
   * Construtor da DB: cria uma nova conexão
   * 
   * @author Raphael Hardt
   * @param string $config Nome da configuração de conexão. Padrão: 'default'
   */
  public function __construct($config = null) {

    if (!isset($config)) {
      $config = 'default';
    }
    
    // pega a configuracao
    $config_params = DbcConfig::get($config);
    
    // ativa exceptions pro mysqli
    mysqli_report(MYSQLI_REPORT_STRICT);
    
    try {
      // faz uma conexão com o banco de dados
      $this->con = mysqli_init();
      $this->con->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
      if ( !$this->con->real_connect($config_params['#host'], 
              $config_params['#user'], 
              $config_params['#password'], 
              $config_params['#schema'], $config_params['#port']) ) {
        // erro de conexão
        throw new exceptions\DbcFailedConnectionException($this->con->connect_error, $this->con->connect_errno);
      }
    }
    catch (\mysqli_sql_exception $e) {
      throw new exceptions\DbcFailedConnectionException($e);
    }

    // define o nome do banco da conexao
    $this->schema = strtoupper($config_params['#schema']);

    try {
      // define o charset utilizado
      $this->con->set_charset($config_params['#charset']);
      $this->charset = $config_params['#charset'];
      
    } catch (\mysqli_sql_exception $e) {
      throw new exceptions\DbcFailedExecuteException($e);
    }

    // limpa variaveis internas 
    // FIXME: talvez não precise dessa chamada...
    $this->reinit();
  }

  /**
   * Destrutor da Dbc: fecha a conexão depois de destruir a instancia
   * @author Raphael Hardt
   */
  public function __destruct() {
    $this->close();
  }

  /**
   * Cria ou utiliza uma instancia de conexão com o banco
   * @author Raphael Hardt
   * @static
   * @param string $config Nome da configuração de conexão. Padrão: 'default'
   * @return Dbc
   */
  static public function getInstance($config = null) {
    self::$_class = __CLASS__; // PHP 5.3 <
    return parent::getInstance($config);
  }

  /**
   * Destroi todas as instancias de conexão com o banco
   * @static
   * @author Raphael Hardt
   */
  static public function destroyInstances() {
    self::$_class = __CLASS__; // PHP 5.3 <
    return parent::destroyInstances();
  }
  

  public function getResource() {
    return $this->con;
  }

  /**
   * Limpa variaveis internas
   * @author Raphael Hardt
   */
  public function reinit() {
    $this->num_rows = 0;
    $this->insert_id = 0;
    $this->affected_rows = 0;
  }
  
  public function destroy() {
    $this->close();
  }

  /**
   * Fecha a conexão da instancia
   * @author Raphael Hardt
   */
  public function close() {
    if ($this->con)
      $this->con->close();
  }

  /**
   * Fecha todas as conexões com o banco
   * @author Raphael Hardt
   */
  public function closeAll() {
    self::destroyInstances();
  }

  /**
   * Conecta com o banco, na mesma instancia; se a conexão já estiver aberta, retorna seu resource
   * @author Raphael Hardt
   * @param string $dbname Nome do banco
   * @param string $dbuser Usuario
   * @param string $dbpass Senha
   * @param string $dbhost Host
   * @param string $charset Charset
   */
  public function connect($config = null) {
    if (is_object($this->con)) {
      return $this->con;
    } else {
      return new self($config);
    }
  }

  /**
   * Define ou retorna o autocommit
   * @author Raphael Hardt
   * @param bool $mode
   * @return bool
   */
  public function autocommit($mode = null) {
    if (isset($mode)) {
      $this->autocommit = (bool) $mode;
      $this->con->autocommit($this->autocommit);
    } else {
      return $this->autocommit;
    }
  }

  /**
   * Executa um comando SQL
   * @author Raphael Hardt
   * @param string $query a instrução SQL a ser executada
   * @param array $values Valores a serem inseridos na instrução, atraves de bind; Utilizar array
   * associativo, onde os keys são os campos e os values são os valores.
   * @return bool
   */
  public function query($query, $values = array()) {
    // TODO: fazer
  }

  /**
   * Prepara um comando SQL, criando uma nova instancia stmt para ser executada pela função execute()
   * @author Raphael Hardt
   * @param string $query SQL a ser executado
   * @return bool
   */
  public function prepare($query) {
    $index = &$this->stmt_index;

    // joga o ponteiro para o proximo stmt para o proximo prepare usar
    ++$index;

    // tira os espaços em volta do sql
    $query = trim($query);

    // pega o tipo de instrução sql que foi passado
    $instruction = explode(' ', $query);
    $instruction = strtoupper($instruction[0]);

    // exceção para SELECT
    if ($instruction == 'SELECT') {

      // no mysql, o select não precisa de preparo, então apenas guarda a posição de stmt para
      // o execute
      $success = true;
      
    } else {
      // faz o prepare numa nova instancia de stmt
      try {
        $this->stmt[$index] = $this->con->prepare($query);
        $success = is_object($this->stmt[$index]);
        if (!$success) {
          throw new exceptions\DbcFailedPrepareException($this->con->error, $this->con->errno);
        }
      } 
      catch (\mysqli_sql_exception $e) {        
        throw new exceptions\DbcFailedPrepareException($e);
      }
    }
    // limpa os binds e valores bindaveis
    $this->stmt_binds[$index] = '';
    $this->stmt_values[$index] = array();
    $this->stmt_query[$index] = $query;

    // retorna se o stmt foi criado ou nao
    return $success;
  }

  /**
   * Executa a ultima instancia stmt criada pela função prepare()
   * @author Raphael Hardt
   */
  public function execute() {

    // pega a instrução executada pelo parse
    $index = $this->stmt_index;

    // pega a instrução que foi passada
    $query = $this->stmt_query[$index];

    // pega o tipo de instrução sql que foi passado
    $instruction = explode(' ', $query);
    $instruction = strtoupper($instruction[0]);

    // exceção para SELECT
    if ($instruction == 'SELECT') {
      // não usar o bind padrão, e sim uma substituição de string
      if (!empty($this->stmt_values[$index])) {

        // auxiliares
        $len = strlen($this->stmt_binds[$index]);
        $pos = 0;

        //vai em cada valor de bind para substituir
        for ($i = 0; $i < $len; $i++) {
          //pega tipo e valor
          $type = $this->stmt_binds[$index]{$i};
          $valor = $this->stmt_values[$index][$i];

          // escapa o valor (xss safe)
          if (get_magic_quotes_gpc()) {
            $valor = stripslashes($valor);
          }
          $valor = $this->con->real_escape_string($valor);

          // se for do tipo string, envolver com apostrofes
          if ($type == 's') {
            $valor = '\'' . $valor . '\'';
          } else {
            $valor = (int) $valor; // redundancia por questão de segurança
          }

          // encontra a posição do proximo ? para substituir
          $pos = strpos($query, '?', $pos);

          // se achou um ?, substitui pelo valor
          if ($pos !== false) {
            $query = substr_replace($query, $valor, $pos, 1);
          }

          // para evitar que um ? de um valor passado seja substituido novamente,
          // o pos é aumentado com o numero de char do valor para ser usado como offset
          // de strpos para o proximo valor
          $pos += strlen($valor);
        }
      }

      try {
        $this->stmt[$index] = $this->con->query($query);
        $success = is_object($this->stmt[$index]);
        if (!$success) {
          throw new exceptions\DbcFailedExecuteException($this->con->error, $this->con->errno);
        }
      } 
      catch (\mysqli_sql_exception $e) {  
        throw new exceptions\DbcFailedExecuteException($e);
      }
      
      // retorna se o query foi executado com sucesso
      return $success;
    } 
    else {
      // binda os parametros temporarios antes guardados
      if (!empty($this->stmt_values[$index])) {
        $values = array_merge((array) $this->stmt_binds[$index], $this->stmt_values[$index]);
        
        // tenta evitar o uso do call_user_func_array, que é 4x mais lento que chamar
        // a funcao diretamente
        // veja: http://www.php.net/manual/en/function.call-user-func-array.php#100794
        $stmt = &$this->stmt[$index];
        /*switch (count($values)) {
          case 1:
            return $stmt->bind_param(&$values[0]);
          case 2:
            return $stmt->bind_param(&$values[0], &$values[1]);
          case 3:
            return $stmt->bind_param(&$values[0], &$values[1], &$values[2]);
          case 4:
            return $stmt->bind_param(&$values[0], &$values[1], &$values[2], &$values[3]);
          default:*/
            call_user_func_array(array($stmt, 'bind_param'), self::_refValues($values));
        /*}*/
        unset($stmt); // apaga ref
      }

      // executa o comando
      try {
        $success = $this->stmt[$index]->execute();
        if (!$success) {
          throw new exceptions\DbcFailedExecuteException($this->con->error, $this->con->errno);
        }
      }
      catch (\mysqli_sql_exception $e) {        
        throw new exceptions\DbcFailedExecuteException($e);
      }

      return $success;
    }
  }

  /**
   * Limpa da memória o comando SQL executado pela função execute(), lembre-se sempre de
   * chama-la a cada fim de execute() ou query(), não importa se retornou true ou false
   * @author Raphael Hardt
   */
  public function free() {
    $index = & $this->stmt_index;

    $success = false;
    
    try {

      // verifica se o resource foi criado
      if (is_object($this->stmt[$index])) {

        // no mysql existem dois tipos de objetos diferentes para manipulação de resultado
        // verificar qual é para usar o comando free correto
        if ($this->stmt[$index] instanceof \mysqli_stmt) {
          // se for stmt (update, insert, delete ...)
          $success = $this->stmt[$index]->close();
        } 
        elseif ($this->stmt[$index] instanceof \mysqli_result) {
          // se for result (select, describe...)
          $this->stmt[$index]->free_result();
          $success = true; // mysql_free_result não retorna nada
        }
      }
    
    }
    catch (\mysqli_sql_exception $e) {        
      throw new exceptions\DbcFailedCloseException($e);
    }
    // volta o ponteiro para o stmt antigo
    --$index;

    return $success;
  }

  /**
   * Faz um bind de valores numa instrução SQL 
   * @author Raphael Hardt
   * @param unknown_type $name
   * @param unknown_type $value
   * @param unknown_type $maxlength
   * @param unknown_type $type
   */
  public function bind_param($name, &$value) {
    $index = $this->stmt_index;

    // verificar o tipo de variavel e definir o tipo de bind
    if (is_int($value)) {
      $type = 'i';
    } elseif (is_float($value) || is_double($value)) {
      $type = 'd';
    } else {
      $type = 's';
    }

    // no mysql, o bind precisa ser feito "temporariamente" pra só depois ser executado
    // de uma vez, então a chamada de função bind_param fica no execute()
    $this->stmt_binds[$index] .= $type;
    $this->stmt_values[$index][] = $value;

    return true;
  }

  /**
   * Commita uma operação SQL executada sem autocommit
   * @author Raphael Hardt
   */
  public function commit() {
    return $this->con->commit();
  }

  /**
   * Dá um "rollback" em uma operação SQL executada sem autocommit
   * @author Raphael Hardt
   */
  public function rollback() {
    return $this->con->rollback();
  }

  /**
   * Retorna o numero ID do último registro inserido na tabela; Só funciona depois de um
   * INSERT;
   * @author Raphael Hardt
   * @return int O número ID do último registro inserido
   */
  public function insert_id() {
    return $this->con->insert_id;
  }

  /**
   * Retorna o número de linhas afetadas pelas instruções: UPDATE, INSERT, DELETE, e outras 
   * @author Raphael Hardt
   * @return int Numero de linhas afetadas
   */
  public function affected_rows() {
    $index = $this->stmt_index;
    // se não for um stmt (update,insert...), retornar 0
    if (!($this->stmt[$index] instanceof \mysqli_stmt)) {
      return 0;
    }

    return $this->stmt[$index]->affected_rows;
  }

  /**
   * Retorna o número de linhas de uma instruçao SELECT 
   * @author Raphael Hardt
   * @return int Numero de linhas de um SELECT
   */
  public function num_rows() {
    $index = $this->stmt_index;
    return $this->stmt[$index]->num_rows;
  }

  /**
   * Retorna o resultado de uma linha de registros encontrados de um SELECT;
   * Sempre retorna LOBs como um descriptor (objeto), da instancia OCILob (para ler o conteudo
   * é necessário usar read() )
   * @author Raphael Hardt
   * @return object
   */
  public function fetch_object() {
    $index = $this->stmt_index;
    // se não for um resource result, retornar false
    if ($this->stmt[$index] instanceof \mysqli_stmt) {
      return false;
    }

    try {
      return $this->stmt[$index]->fetch_object();
    }
    catch (\mysqli_sql_exception $e) {        
      throw new exceptions\DbcFailedFetchException($e);
    }
  }

  /**
   * Retorna o resultado de uma linha de registros encontrados de um SELECT
   * Sempre vai retornar LOBs como string, caso seja necessario manipular como objeto,
   * utilize fecth_object() 
   * @author Raphael Hardt
   * @return array
   */
  public function fetch_row() {
    return $this->fetch_array(MYSQLI_NUM);
  }

  /**
   * Retorna o resultado de uma linha de registros encontrados de um SELECT
   * Sempre vai retornar LOBs como string, caso seja necessario manipular como objeto,
   * utilize fecth_object() 
   * @author Raphael Hardt
   * @return array
   */
  public function fetch_assoc() {
    return $this->fetch_array(MYSQLI_ASSOC);
  }

  /**
   * Retorna o resultado de uma linha de registros encontrados de um SELECT;
   * Sempre vai retornar LOBs como string, caso seja necessario manipular como objeto,
   * utilize fecth_object() 
   * @author Raphael Hardt
   * @return array
   */
  public function fetch_array($flags = null) {
    $index = $this->stmt_index;

    // se não for um resource result, retornar false
    if ($this->stmt[$index] instanceof \mysqli_stmt) {
      return false;
    }

    // se não setar as flags, retornar um array associativo e numerico
    if (!isset($flags)) {
      $flags = MYSQLI_BOTH;
    }

    try {
      return $this->stmt[$index]->fetch_array($flags);
    }
    catch (\mysqli_sql_exception $e) {        
      throw new exceptions\DbcFailedFetchException($e);
    }
  }

  private static function _refValues($arr) {
    if (strnatcmp(phpversion(), '5.3') >= 0) {
      $refs = array();
      foreach ($arr as $key => $value)
        $refs[$key] = &$arr[$key];
      return $refs;
    }
    return $arr;
  }

}