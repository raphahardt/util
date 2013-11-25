<?php

/**
 * Time of the current request in seconds elapsed since the Unix Epoch.
 *
 * This differs from $_SERVER['REQUEST_TIME'], which is stored as a float
 * since PHP 5.4.0. Float timestamps confuse most PHP functions
 * (including date_create()).
 *
 * @see http://php.net/manual/reserved.variables.server.php
 * @see http://php.net/manual/function.time.php
 */
define('START_REQUEST', $_SERVER['REQUEST_TIME_FLOAT'] ? $_SERVER['REQUEST_TIME_FLOAT'] : (int)$_SERVER['REQUEST_TIME']);
define('START', microtime(true));
$timeline = array();

if (!defined('DS'))
  define('DS', DIRECTORY_SEPARATOR);

/**
 * Nome das pastas do sistema
 */
define('APP_DIR', 'app');
define('CORE_DIR', 'core');
define('PLUGIN_DIR', 'plugins');
define('TEMP_DIR', '_tmp');
define('PUBLIC_DIR', 'public');

/**
 * Pasta absoluta raiz do sistema. Sempre resolve pelo sistema mais "alto"
 * Se você tiver um subsistema que lê as configurações do sistema pai, essa constante
 * sempre vai apontar pra pasta root do sistema pai.
 */
if (!defined('DJCK'))
  define('DJCK', dirname(dirname(__FILE__)));

/**
 * Pasta absoluta raiz do seu projeto. Sempre resolve pelo sistema mais "baixo" (atual).
 * Esta constante sempre irá retornar a pasta root do subsistema, caso haja sistema pai,
 * ou simplesmente será igual a DJCK
 */
if (!defined('ROOT'))
  define('ROOT', DJCK);

/**
 * Pasta absoluta da "app" do seu projeto. Irá tentar resolver pela ROOT em vez de DJCK.
 */
if (!defined('APP_PATH'))
  define('APP_PATH', ROOT.DS.APP_DIR);

/**
 * Pasta absolita do "core" do projeto
 */
if (!defined('CORE_PATH'))
  define('CORE_PATH', DJCK.DS.CORE_DIR);

/**
 * Pasta absoluta dos "plugins" do projeto
 */
if (!defined('PLUGIN_PATH'))
  define('PLUGIN_PATH', DJCK.DS.PLUGIN_DIR);

/**
 * Pasta absoluta dos arquivos temporarios do projeto
 */
if (!defined('TEMP_PATH'))
  define('TEMP_PATH', DJCK.DS.TEMP_DIR);

/**
 * Pasta absoluta dos arquivos publicos do projeto
 */
if (!defined('PUBLIC_PATH'))
  define('PUBLIC_PATH', DJCK.DS.PUBLIC_DIR);

define('OS', PHP_OS);
define('_DEV', $_SERVER['HTTP_HOST'] === 'localhost');


// funções basicas
include CORE_PATH.DS.'basics.php';

// finish
define('FINISH_BASICS', microtime(true));

// CORE
include CORE_PATH.DS.'core'.DS.'Core.php';
Core::setup(); // inicia o core (autoloads, handler de exceptions, fatal errors, etc)

// finish
define('FINISH_CORE_LOAD', microtime(true));

// verifica algumas integridades do sistema
if (!is_dir(APP_PATH)) {
  throw new CoreException('Pasta da aplicação não encontrada!');
  
} elseif (!is_file(APP_PATH.DS.'cfg'.DS.'defs.php')) {
  throw new CoreException('Arquivo de definições da aplicação não encontrado!');
  
}

// carrega primeiro as definicoes do app, depois do core
include APP_PATH.DS.'cfg'.DS.'defs.php';
include CORE_PATH.DS.'defs.php';

// finish
define('FINISH_DEFS', microtime(true));

if (!defined('_DEFS_ONLY')) {
  include CORE_PATH.DS.'load.php';
  // finish
  define('FINISH_LOAD', microtime(true));
}
