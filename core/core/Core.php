<?php

namespace Djck;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('CORE_PATH'))
  define('CORE_PATH', DJCK.DS.'core');

if (!defined('APP_PATH'))
  define('APP_PATH', DJCK.DS.'app');

if (!defined('PLUGIN_PATH'))
  define('PLUGIN_PATH', DJCK.DS.'plugins');

class CoreException extends \Exception {}

/**
 * Description of Core
 *
 * @author Rapha e Dani
 */
abstract class Core {
  
  // classes importadas do core
  private static $imported = array();
  
  // classes registradas para serem carregadas pelo autoload do core
  private static $classes = array();
  
  // numero de chamadas de funcao do core
  private static $calls = 0;

  /**
   * Tipo de arquivo
   * Deve conter as seguintes configurações:
   *  - core: TRUE para buscar do core/, FALSE para buscar do app/
   *  - path: caminho onde o arquivo será encontrado, relativo ao app/ ou core/ de acordo
   *          com a opção core acima
   *  - root: TRUE para buscar da raiz do projeto, FALSE para buscar relativo ao core/ ou app/
   *  - plugins: TRUE para buscar da pasta dos plugins, FALSE para buscar relativo ao core/ ou app/
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
  
  // transforma um namespace em pasta
  private static function _namespaceToFolder($namespace) {
    // configuração: mapeamentos para namespaces ambíguos
    $cfg = cfg('Maps');
    
    $parts_namespace = explode('\\', $namespace);
    foreach ($parts_namespace as &$block_namespace) {
      list($block, $subname) = explode(':', $block_namespace, 2);
      if ($cfg[ $block ]) {
        // se for array de um elemento só, usar proprio
        if (is_array($cfg[ $block ]) && count($cfg[ $block ]) == 1) {
          $cfg[ $block ] = reset($cfg[ $block ]);
        }
        if (is_array($cfg[ $block ])) {
          if (!$subname) {
            throw new CoreException('Path de namespace "'.$block.'" não mapeado '
                    . '(este package usa '.count($cfg[ $block ]).' subpackages.'
                    . ' Use namespace\\package:subpackage para selecionar');
          }
          $cfg[ $block ] = $cfg[ $block ][$subname];
        }
        $block_namespace = $cfg[ $block ];
      } else {
        // tirar :subname sempre, mesmo q nao tenha
        $block_namespace = $block;
      }
    }
    unset($block_namespace);
    $abs_path = implode(DS, $parts_namespace);
    return sdir_rtrim($abs_path);
  }
  
  // registra a classe que vc vai usar no contexto para ser carregada pelo autoload
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
  
  final static function importPackage($package) {
    // pega pasta correta
    $parsed = self::path($package);
    dump($parsed);
    
    foreach (glob($parsed.DS.'*.php') as $file) {
      // pega o nome da classe
      $class = str_replace(array('.class.php','.php'), '', basename($file));
      dump($class);
      dump($package);
      Core::import($class, $package);
    }
  }
  
  // retorna o caminho correto
  final static function path($path) {
    
    // procurando classe por namespace
    if (strpos($path, '/') !== 0) {
      return self::_namespaceToFolder($path);
    } else {
      $path = substr($path, 1);
    }
    
    $parts = explode('/', $path);
    $type = array_shift($parts);
    
    // pega o path do tipo e acrescenta no inicio do caminho do arquivo
    if (isset(self::$types[$type]['path'])) {
      $path_ = explode('/', (string)self::$types[$type]['path']);
      $parts = array_merge($path_, $parts);
      unset($path_);
    }
    
    // caminho absoluto para o arquivo
    if (self::$types[$type]['core'] === true)
      $abs_path = CORE_PATH . DS;
    else {
      if (self::$types[$type]['root'] === true)
        $abs_path = DJCK . DS;
      elseif (self::$types[$type]['plugins'] === true)
        $abs_path = PLUGIN_PATH . DS;
      else
        $abs_path = APP_PATH . DS;
    }
    $abs_path .= implode(DS, $parts);
    
    return $abs_path;
  }
  
  static private function _parseFile($file, $path, &$alias = '') {
    
    // pega o caminho
    $abs_path = self::path($path);
    if (strpos($abs_path, '.php') !== false) { // mudança 18/09/13 - permite que se coloque arquivos com nomes diferentes do da classe se colocar .php no final do path
      $abs_path = substr($abs_path, 0, -4); // tira o .php, pq no codigo abaixo vai colocar de novo
    } else {
      $abs_path .= DS . $file; // o nome do arquivo é o nome da classe
    }
    
    // alias para o arquivo
    $alias = $abs_path;
    foreach (array(CORE_PATH => 'core', 
                    APP_PATH => 'app', 
                    PLUGIN_PATH => 'plugins') as $search => $replacement) {
      $alias = str_replace($search, $replacement, $alias);
    }
    $alias = str_replace(DS, '.', $alias);
    
    // coloca a extensão no arquivo
    foreach (array('.php', '.class.php') as $ext) {
      if (is_file($abs_path . $ext)) {
        $abs_path .= $ext;
        break;
      }
    }
    
    return $abs_path;
  }
  
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
  
  // carrega uma classe
  final public static function load($class) {
    // Sanitize class name.
    $class = strtolower($class);
    
    ++self::$calls;

    // If the class already exists do nothing.
    if (class_exists($class, false)) {
      return true;
    }

    // If the class is registered include the file.
    if (isset(self::$classes[$class])) {
      //echo self::$classes[$class];
      include_once str_replace('#', DJCK, self::$classes[$class]);
      return true;
    }

    return false;
  }
  
  // verifica se a classe já foi carregada, se não, lançar uma exception
  /**
   * 
   * @deprecated
   * @param type $class
   * @throws CoreException
   */
  final static function depends($class) {
    ++self::$calls;
    
    // If the class already exists do nothing.
    $namespaced_class = str_replace('.', '\\', strtolower($class));
    if (!class_exists(__NAMESPACE__.'\\'.$namespaced_class, false)) {
      $trace = debug_backtrace();
      throw new CoreException('Arquivo '.basename($trace[0]['file']).' depende da classe '.$class);
    }
  }
  
  // alias para ::uses
  final static function register($class, $path, $force = false) {
    self::uses($class, $path, $force);
  }
  
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
  
  // hack para conseguir pegar erros fatais no php
  final static function fatal_error_handler() {
    $error = error_get_last();

    if( $error !== NULL) {
      $errno   = $error["type"];
      $errfile = $error["file"];
      $errline = $error["line"];
      $errstr  = $error["message"];
      
      // limpa qualquer buffer que tenha sido criado
      ob_end_clean();
      
      // manda o erro como exception e captura depois com o handler padrao
      try {
        self::error_handler($errno, $errstr, $errfile, $errline);
      } catch (\Exception $e) {
        self::exception_handler($e);
      }
    }
  }
  
  final static function exception_handler($exception) {
    // manda header de erro 500 (erro interno de servidor)
    if (!headers_sent()) {
      $html = true;
      header('Content-type: text/html; charset=UTF-8', true, 500);
    } else {
      $html = false;
    }
    
    // imprime algo amigavel na tela pro visitante
    if ($html) {
      echo '<html><head><title>Erro não capturado - '.SITE_TITLE.'</title></head>';
      echo '<body>';
    }
    echo '<div style="margin:30px auto;border:1px solid #ccc;background:#eee;padding:15px;width:400px">';
    echo '<span style="color:red">Erro não capturado:</span> ' , $exception->getMessage(), 
            ' <div style="color:#999"> linha ', $exception->getLine(), 
            ' - ', str_replace(DJCK, '', $exception->getFile()),
            '</div>',
            "\n";
    echo '</div>';
    if ($html) {
      echo '</body></html>';
    }
    
    // avisa o webmaster ou faz log
    //mail('sistema13@furacao.com.br', 'teste erro', $exception->getMessage());
  }
  
  final static function setup() {
    // define auto loader
    spl_autoload_register(array(__CLASS__, 'load'));
    
    // define handler de errors do php para sempre jogarem exceptions
    //error_reporting(0); // COMENTADO PQ NÃO É PARA DESABILITAR OS ERROS POR AQUI
    @ini_set('display_errors', _DEV);
    set_error_handler(array(__CLASS__, 'error_handler'));
    register_shutdown_function(array(__CLASS__, 'fatal_error_handler'));
    
    // lida com os exceptions que nao foram capturados
    set_exception_handler(array(__CLASS__, 'exception_handler'));
  }
  
  static function dump() {
    dump(array(self::$classes, self::$imported, self::$calls));
  }
  
}