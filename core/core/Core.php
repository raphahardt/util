<?php

namespace Djck;

/**
 * Define algumas constantes caso elas ainda não tenham sido definidas
 * @deprecated 28/11/13 elas são todas definidas do defs.php
 */
if (!defined('CORE_PATH'))
  define('CORE_PATH', DJCK.DS.'core');

if (!defined('APP_PATH'))
  define('APP_PATH', DJCK.DS.'app');

if (!defined('PLUGIN_PATH'))
  define('PLUGIN_PATH', DJCK.DS.'plugins');

/**
 * Exceções lançadas da Core
 */
class CoreException extends \Exception {}

/**
 * Classe principal do sistema.
 * 
 * A classe Core é responsável por carregar as outras classes do sistema e também
 * por definir os handlers de erros e exceptions.
 * 
 * Exemplos de uso
 * ---------------
 * <code>
 * Core::setup();    // inicia core
 * 
 * Core::import('Classe1', 'namespace\para');   // importa \namespace\para\Classe1
 * Core::uses('Classe2', '/core/pasta');        // registra \Classe2
 * 
 * // registra todas as classes da pasta DJCK/namespace/pastaMapeada/subpasta
 * Core::registerPackage('namespace\package:pasta1\subpasta');
 * </code>
 * 
 * Exemplos de mapeamento de namespaces (Maps)
 * -------------------------------------------
 * <code>
 * // arquivo Maps.neon
 * namespace: %CORE_DIR%         # sempre redireciona \namespace\ para pasta definida do core
 * 
 * package:
 *   pasta1: pastaMapeada        # mapeia \package:pasta1\ para pastaMapeada\
 *   pasta2: pasta\com\subpasta  # mapeia \package:pasta2\ para pasta\com\subpasta\
 * 
 * outro: outraPasta             # mapeia \outro\ para outraPasta\
 * </code>
 * 
 * Para outros detalhes, veja Core::path()
 * 
 * @abstract
 * @author Raphael Hardt
 * @version 1.0
 */
abstract class Core {
  
  /**
   * Classes importadas.
   * 
   * @var array 
   */
  private static $imported = array();
  
  /**
   * Classes registradas para ser importadas por autoload.
   * 
   * @var array
   */
  private static $classes = array();
  
  /**
   * Número de vezes que um import() ou uses() foi chamado.
   * 
   * @var int 
   */
  private static $calls = 0;

  /**
   * Tipo de arquivo
   * Deve conter as seguintes configurações:
   *  - core: TRUE para buscar do core/, FALSE para buscar do app/
   *  - path: caminho onde o arquivo será encontrado, relativo ao app/ ou core/ de acordo
   *          com a opção core acima
   *  - root: TRUE para buscar da raiz do projeto, FALSE para buscar relativo ao core/ ou app/
   *  - plugins: TRUE para buscar da pasta dos plugins, FALSE para buscar relativo ao core/ ou app/
   * @deprecated 28/11/13 Use namespaces para carregar classes
   * @var type tipo do arquivo que sera carregado
   */
  public static $types = array(
      'core' =>       array('core' => true),
      'model' =>      array('core' => false, 'path' => 'model'),
      'view' =>       array('core' => false, 'path' => 'view'),
      'controller' => array('core' => false, 'path' => 'controller'),
      'plugin' =>     array('plugins' => true),
      'file' =>       array('root' => true),
      'root' =>       array('root' => true),
  );
  
  /**
   * Retorna a pasta física correspondente ao namespace.
   * 
   * @param string $namespace
   * @return string
   * @throws CoreException
   */
  private static function _namespaceToFolder(&$namespace) {
    // configuração: mapeamentos para namespaces ambíguos
    $cfg = cfg('Maps');
    
    $parts_namespace = explode('\\', $namespace);
    $final_namespace = array();
    foreach ($parts_namespace as &$block_namespace) {
      list($block, $subname) = explode(':', $block_namespace, 2);
      if ($cfg[ $block ]) {
        // se for array de um elemento só, usar proprio
        if (is_array($cfg[ $block ]) && count($cfg[ $block ]) == 1 && is_numeric(key($cfg[ $block ]))) {
          $cfg[ $block ] = reset($cfg[ $block ]);
        }
        if (is_array($cfg[ $block ])) {
          /*if (!$subname) {
            throw new CoreException('Path de namespace "'.$block.'" não mapeado '
                    . '(este package usa '.count($cfg[ $block ]).' subpackages.'
                    . ' Use namespace\\package:subpackage para selecionar');
          }
          $cfg[ $block ] = $cfg[ $block ][$subname];*/
          if ($subname) {
            $cfg[ $block ] = $cfg[ $block ][$subname];
          } else {
            $cfg[ $block ] = $block;
          }
        }
        $block_namespace = $cfg[ $block ];
      } else {
        // tirar :subname sempre, mesmo q nao tenha
        $block_namespace = $block;
      }
      // para o namespace normalizado
      $final_namespace[] = $block;
    }
    unset($block_namespace);
    // seta o namespace de volta, que está por referencia, para ter o novo nome
    // de namespace normalizado (sem os subpackages :subpckg)
    $namespace = implode('\\', $final_namespace);
    
    $abs_path = DJCK . DS . implode(DS, $parts_namespace);
    return sdir_rtrim($abs_path);
  }
  
  /**
   * Retorna a pasta física correspondente ao pseudo-path.
   * 
   * @param string $path
   * @return string
   */
  private static function _pseudoPathToFolder($path) {
    $parts = explode('/', $path);
    $type = array_shift($parts);
    
    // pega o path do tipo e acrescenta no inicio do caminho do arquivo
    if (isset(self::$types[$type]['path'])) {
      $path_ = explode('/', (string)self::$types[$type]['path']);
      $parts = array_merge($path_, $parts);
      unset($path_);
    }
    
    // caminho absoluto para o arquivo
    if (self::$types[$type]['core'] === true) {
      $abs_path = CORE_PATH . DS;
    } else {
      if (self::$types[$type]['root'] === true) {
        $abs_path = DJCK . DS;
      } elseif (self::$types[$type]['plugins'] === true) {
        $abs_path = PLUGIN_PATH . DS;
      } else {
        $abs_path = APP_PATH . DS;
      }
    }
    $abs_path .= implode(DS, $parts);
    
    return sdir_rtrim($abs_path);
  }
  
  /**
   * Retorna uma representação separada por pontos de um diretorio.
   * 
   * Exemplo: /sistema/core/library/Class -> core.library.Class
   * 
   * @param string $folder
   * @return string
   */
  private static function _parseAlias($folder) {
    foreach (array(CORE_PATH => 'core', 
                    APP_PATH => 'app', 
                    PLUGIN_PATH => 'plugins',
                    DJCK => 'root') as $search => $replacement) {
      $folder = str_replace($search, $replacement, $folder);
    }
    return str_replace(DS, '.', $folder);
  }
  
  /**
   * Importa uma classe no sistema.
   * 
   * Este método faz um include no arquivo da classe.
   * Use import() apenas se a ordem da importação das classes é importante pro funcionamento
   * do sistema. Para casos onde a ordem não importa, use uses() ou register()
   * 
   * Exemplo de uso
   * --------------
   * <code>
   * Core::import('Classe', 'namespace\da\classe');
   * </code>
   * 
   * O namespace deve corresponder à pasta onde ela está localizada a partir da pasta
   * root do sistema (DJCK). Caso um namespace não corresponda, ele deve ser mapeado
   * no arquivo de configuração "Maps"
   * 
   * @see Core::path()
   * @param string $file Nome da classe que será importada (sem o namespace)
   * @param string $path Namespace da classe. Se começar com /, será procurado por pasta
   * @param boolean $force Força a classe ser carregada novamente mesmo que tenha sido falha
   * @return boolean Se foi carregada ou não
   */
  final static function import($file, $path, $force = false) {
    $success = false;
    
    $alias = '';
    $parsed = self::_parseFile($file, $path, $alias);
    
    // checa se o arquivo já foi carregado e se vai força-lo a sobrecarrega-lo
    if (!isset(self::$imported[$alias]) || $force) {

      // verifica se o arquivo existe
      if (is_file($parsed)) {
        $success = (bool) include_once $parsed;
      }

      // registra qual o status da classe.
      self::$imported[$alias] = $success;
      
    }
    ++self::$calls;
    return self::$imported[$alias];
  }
  
  /**
   * Registra uma classe no sistema.
   * 
   * Este método registra a classe num buffer e importa no sistema apenas quando for necessária.
   * Não use este método para classes essenciais do sistema, para isso use import().
   * As classes registradas serão invocadas automaticamente pelo __autoload (load()).
   * 
   * Exemplo de uso
   * --------------
   * <code>
   * Core::uses('Classe', 'namespace\da\classe');
   * </code>
   * 
   * O namespace deve corresponder à pasta onde ela está localizada a partir da pasta
   * root do sistema (DJCK). Caso um namespace não corresponda, ele deve ser mapeado
   * no arquivo de configuração "Maps"
   * 
   * @see Core::path()
   * @param string $class Nome da classe que será importada (sem o namespace)
   * @param string $path Namespace da classe. Se começar com /, será procurado por pasta
   * @param boolean $force Força a classe ser carregada novamente mesmo que tenha sido falha
   */
  final static function uses($class, $path, $force = false) {
    // pega pasta correta
    $parsed = self::_parseFile($class, $path);
    
    if (strpos($path, '/') !== 0) {
      $class = $path.'\\'.$class; // adiciona o namespace na classe, se tiver
    }
    
    // sanitize class name
    $class = strtolower($class);
    
    // Only attempt to register the class if the name and file exist.
    if (!empty($class) && is_file($parsed)) {
      // Register the class with the autoloader if not already registered or the force flag is set.
      if (empty(self::$classes[$class]) || $force) {
        self::$classes[$class] = str_replace(DJCK, '#', $parsed); 
        // replace serve para economizar espaço da memoria utilizado pela variavel
        // estatica. não é necessario guardar o caminho inteiro
      }
    }
    
    ++self::$calls;
  }
  
  /**
   * Alias para Core::uses()
   * 
   * @see Core::path()
   * @see \Djck\Core::uses()
   * @param string $class
   * @param string $path
   * @param boolean $force
   */
  final static function register($class, $path, $force = false) {
    self::uses($class, $path, $force);
  }
  
  /**
   * Importa todas as classes de um namespace.
   * 
   * @see Core::path()
   * @param string $package
   */
  final static function importPackage($package) {
    // pega pasta correta
    $original_package = $package; // tenho que guardar a pasta correta pois $package será alterado por referencia
    $parsed = self::path($package);
    
    if (is_file($parsed.DS.'_load.php')) {
      
      // inclue o arquivo que configura o pacote
      include_once $parsed.DS.'_load.php';
      
    } else {
    
      foreach (glob($parsed.DS.'*.php') as $file) {
        // pega o nome da classe
        $class = str_replace(array('.class.php','.php'), '', basename($file));
        Core::import($class, $original_package);
      }
    }
  }
  
  /**
   * Registra todas as classes de um namespace.
   * 
   * @see Core::path()
   * @param string $package
   */
  final static function usesPackage($package) {
    // pega pasta correta
    $original_package = $package; // tenho que guardar a pasta correta pois $package será alterado por referencia
    $parsed = self::path($package);
    
    foreach (glob($parsed.DS.'*.php') as $file) {
      // pega o nome da classe
      $class = str_replace(array('.class.php','.php'), '', basename($file));
      Core::uses($class, $original_package);
    }
  }
  
  /**
   * Alias para Core::usesPackage()
   * 
   * @see Core::path()
   * @see Core::usesPackage()
   * @param string $package
   */
  final static function registerPackage($package) {
    self::usesPackage($package);
  }
  
  /**
   * Converte um pseudo-path ou namespace para sua pasta física correspondente.
   * 
   * Para pseudo-paths (deprecated)
   * --------------------
   * core: alias para CORE_PATH
   * model: alias para APP_PATH/model
   * controller: alias para APP_PATH/controller
   * view: alias para APP_PATH/view
   * plugin: alias para PLUGIN_PATH
   * file/root: alias para DJCK
   * 
   * Para os pseudo-paths funcionarem, eles devem iniciar com / (barra)
   * 
   * Para namespaces
   * ---------------
   * nome\do\namespace: 
   *   Vai procurar pelo \nome\do\namespace na pasta DJCK/nome/do/namespace/
   * outro\namespace:opcao\ambiguo: 
   *   Vai procurar pelo \outro\namespace\ambiguo na pasta DJCK/outro/namespace_mapeado/ambiguo/
   * 
   * Há vezes que um mesmo namespace pode apontar para pastas diferentes.
   * Para resolver isso, use o arq de config "Maps" e aponte o namespace que tenha
   * mais de uma pasta. Deve ser um array associativo, onde ['namespace' => ['opcao1' => 'pasta1/subpasta']] seja
   * acessado via \abc\namespace:opcao1\, que será alterado para DJCK/abc/pasta1/subpasta/
   * Não necessariamente precisa ser um array de ambiguidades, pode ser um simples mapeamento
   * para outra pasta, num string mesmo (ex: ['namespace' => 'outra/pasta']).
   * 
   * @param string $path Namespace ou pseudo-path (deprecated) que será mapeado para sua pasta correspondente.
   * @return string Caminho absoluto do namespace
   */
  final static function path(&$path) {
    
    // procurando classe por namespace
    if (strpos($path, '/') !== 0) {
      return self::_namespaceToFolder($path);
    } else {
      return self::_pseudoPathToFolder(substr($path, 1));
    }
  }
  
  /**
   * Método auxiliar para acertar o caminho de um arquivo.
   * 
   * Retorna o caminho absoluto do arquivo a ser importado/registrado.
   * O nome do arquivo é sempre o nome da classe
   * Se o caminho terminar com .php, o nome do arquivo do $path será usado
   * 
   * @private
   * @param string $file Nome da classe (arquivo)
   * @param string $path Namespace ou pseudo-path de onde a classe está
   * @param string $alias Alias de localização do arquivo fisicamente, usada para registrar
   *                      no import() (interno)
   * @return string Caminho absoluto
   */
  static private function _parseFile($file, &$path, &$alias = '') {
    
    // pega o caminho
    $abs_path = self::path($path);
    if (strpos($abs_path, '.php') !== false) { // mudança 18/09/13 - permite que se coloque arquivos com nomes diferentes do da classe se colocar .php no final do path
      $abs_path = substr($abs_path, 0, -4); // tira o .php, pq no codigo abaixo vai colocar de novo
    } else {
      $abs_path .= DS . $file; // o nome do arquivo é o nome da classe
    }
    
    // alias para o arquivo
    $alias = self::_parseAlias($abs_path);
    
    // coloca a extensão no arquivo
    foreach (array('.php', '.class.php') as $ext) {
      if (is_file($abs_path . $ext)) {
        $abs_path .= $ext;
        break;
      }
    }
    
    return $abs_path;
  }
  
  /**
   * Método chamado pelo autoload para carregar as classes registradas.
   * 
   * @param string $class Nome da classe a ser carregada (com namespace)
   * @return boolean
   */
  final public static function load($class) {
    // sanitize
    $class = strtolower($class);
    
    ++self::$calls;

    // se a classe ja existe, não fazer nada
    if (class_exists($class, false)) {
      return true;
    }

    // se a classe está registrada, include
    if (isset(self::$classes[$class])) {
      //echo self::$classes[$class];
      include_once str_replace('#', DJCK, self::$classes[$class]);
      return true;
    }

    return false;
  }
  
  /**
   * Faz com que o arquivo (ou partes dele) tenha dependencia com certa classe.
   * 
   * Ele verifica se a classe já foi carregada, senão, lança uma Exception e evita
   * o sistema de continuar.
   * 
   * @deprecated 28/11/13 Não é necessário mais forçar uma importação com esse método
   * @param string $class Nome da classe que o arquivo depende (com namespace)
   * @throws CoreException
   */
  final static function depends($class) {
    ++self::$calls;
    
    if (!class_exists($class, false)) {
      $trace = debug_backtrace();
      throw new CoreException('Arquivo '.basename($trace[0]['file']).' depende da classe '.$class);
    }
  }
  
  /**
   * Manipulador de erros do sistema.
   * 
   * Captura os erros comuns (trigger_error) e lança uma ErrorException no lugar.
   * Basicamente ela deixa o sistema mais orientado a objetos.
   * 
   * @param int $errno
   * @param string $errstr
   * @param string $errfile
   * @param int $errline
   * @return boolean
   * @throws \ErrorException
   */
  final static function error_handler($errno, $errstr, $errfile, $errline) {
    // não mostra erros para quando for usado o @ nas expressões
    // ver: http://www.php.net/manual/en/language.operators.errorcontrol.php
    if (error_reporting() == 0) {
      return true;
    }
    $severity =
            1 * E_ERROR |
            1 * E_WARNING |
            1 * E_PARSE |
            0 * E_NOTICE |
            1 * E_CORE_ERROR |
            1 * E_CORE_WARNING |
            1 * E_COMPILE_ERROR |
            1 * E_COMPILE_WARNING |
            1 * E_USER_ERROR |
            1 * E_USER_WARNING |
            0 * E_USER_NOTICE |
            0 * E_STRICT |
            0 * E_RECOVERABLE_ERROR |
            1 * (defined('E_DEPRECATED') ? E_DEPRECATED : 0) |
            0 * (defined('E_USER_DEPRECATED') ? E_USER_DEPRECATED : 0);
    $ex = new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
    if (($ex->getSeverity() & $severity) != 0) {
      throw $ex;
    }
  }
  
  /**
   * Manipulador de erros fatais do sistema.
   * 
   * O PHP, por padrão, não permite que você recupere de Fatal Errors e nem capture
   * estes erros com handlers normais. Este método, que é registrado no fim do script
   * (register_shutdown_function) faz com que um último possível erro seja capturado
   * e seja lançado uma Exception pelo handler de erros normal.
   * 
   * @see http://stackoverflow.com/questions/277224/how-do-i-catch-a-php-fatal-error
   * @return void
   */
  final static function fatal_error_handler() {
    $error = error_get_last();

    if( $error !== NULL) {
      $errno   = $error["type"];
      $errfile = $error["file"];
      $errline = $error["line"];
      $errstr  = $error["message"];
      
      // limpa qualquer buffer que tenha sido criado
      if (!_DEV) { // para nao apagar tudo quando tiver em modo DEV
        ob_end_clean();
      }
      
      // manda o erro como exception e captura depois com o handler padrao
      try {
        self::error_handler($errno, $errstr, $errfile, $errline);
      } catch (\Exception $e) {
        self::exception_handler($e);
      }
    }
  }
  
  /**
   * Manipulador de Exceptions não-capturadas.
   * 
   * Se algum erro lançar uma Exception e ela não for capturada, esta função é
   * executada e uma página "amigável" é mostrada com o Exception lançado.
   * 
   * Porém ela pode ser usada para enviar um e-mail pro programador, por exemplo.
   * 
   * @todo mandar email pro webmaster
   * @param \Exception $exception
   */
  final static function exception_handler(\Exception $exception) {
    // manda header de erro 500 (erro interno de servidor)
    if (!headers_sent()) {
      $html = true;
      header('Content-type: text/html; charset=UTF-8', true, 500);
    } else {
      $html = false;
    }
    
    $error_type = '';
    $errno = $exception->getCode();
    switch ($errno) {
      case E_ERROR: 
      case E_USER_ERROR:
        $error_type = 'Fatal error'; break;
      case E_WARNING:
      case E_USER_WARNING:
        $error_type = 'Warning'; break;
      case E_PARSE:
        $error_type = 'Parse error'; break;
      case E_NOTICE:
      case E_USER_NOTICE:
        $error_type = 'Notice'; break;
      case E_CORE_ERROR:
        $error_type = 'Fatal core error'; break;
      case E_CORE_WARNING:
        $error_type = 'Core warning'; break;
      case E_COMPILE_ERROR:
        $error_type = 'Compile error'; break;
      case E_COMPILE_WARNING:
        $error_type = 'Compile warning'; break;
      case E_STRICT:
        $error_type = 'Strict standards'; break;
      case E_RECOVERABLE_ERROR:
        $error_type = 'Recoverable error'; break;
      default:
        $error_type = 'Unknown error';
    }
    
    // imprime algo amigavel na tela pro visitante
    if ($html) {
      echo '<html><head><title>Erro não capturado - '.SITE_TITLE.'</title></head>';
      echo '<body>';
    }
    echo '<div style="margin:30px;border:1px solid #ccc;background:#eee;padding:15px; overflow:auto;">';
    echo '<span style="color:red">'.get_class($exception).' ['.$error_type.']:</span> ' , 
              $exception->getMessage(), 
            ' <div style="color:#999"> linha ', $exception->getLine(), 
            ' - ', str_replace(DJCK, '', $exception->getFile()),
            '<pre><code>', str_replace(DJCK, '', $exception->getTraceAsString()), '</code></pre>',
            '</div>',
            "\n";
    echo '</div>';
    finish(false);
    if ($html) {
      echo '</body></html>';
    }
    
    // avisa o webmaster ou faz log
    //mail('...@t...', 'teste erro', $exception->getMessage());
  }
  
  /**
   * Manipulador de output buffer.
   * 
   * Com este método é possível capturar erros fatais também, bem como mandar o
   * output compactado com gzip, por exemplo.
   * 
   * @todo gzip
   * @param string $buffer Buffer atual
   * @return string Buffer a ser mostrado no browser
   */
  final static function outputbuffer_handler($buffer) {
    // TODO fazer depois
    /*$error = error_get_last();

    if( $error !== NULL) {
      $errno   = $error["type"];
      $errfile = $error["file"];
      $errline = $error["line"];
      $errstr  = $error["message"];
      
      // manda o erro como exception e captura depois com o handler padrao
      try {
        self::error_handler($errno, $errstr, $errfile, $errline);
      } catch (\Exception $exception) {
        $buffer .= ''
                . '<div style="margin:15px auto;border:1px solid #ccc;background:#eee;padding:15px;width:400px">'
                  . '<span style="color:red">Erro não capturado ('.get_class($exception).'):</span> '
                    . $exception->getMessage()
                  . ' <div style="color:#999"> linha '. $exception->getLine()
                  . ' - '. str_replace(DJCK, '', $exception->getFile())
                  . '</div>'
                . '</div>';
      }
    }*/
    
    return $buffer;
  }
  
  /**
   * Inicializador da Core.
   * 
   * Deve ser chamado assim que importada.
   * 
   * @return void
   */
  final static function setup() {
    // define auto loader
    spl_autoload_register(array(__CLASS__, 'load'));
    
    // define handler de errors do php para sempre jogarem exceptions
    //error_reporting(0); // COMENTADO PQ NÃO É PARA DESABILITAR OS ERROS POR AQUI
    //@ini_set('display_errors', _DEV);
    @ini_set('display_errors', false);
    set_error_handler(array(__CLASS__, 'error_handler'));
    register_shutdown_function(array(__CLASS__, 'fatal_error_handler'));
    
    // lida com os exceptions que nao foram capturados
    set_exception_handler(array(__CLASS__, 'exception_handler'));
  }
  
  static function dump() {
    dump(array(self::$classes, self::$imported, self::$calls));
  }
  
}