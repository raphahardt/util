<?php

/**
 * Carrega as classes core principais e as configurações referentes a core e app.
 * Não mudar este arquivo (somente em caso de algum core for essencial carregar). 
 */
// essentials
Core::import('AbstractObject',    'core/core');
Core::import('AbstractSingleton', 'core/core');

// interface
Core::import('Response',   'core/network');
Core::import('Request',    'core/network');
Core::import('Dispatcher', 'core/core');

// database
Core::import('DbcConfig', 'core/database/dbc');
Core::import('Dbc',       'core/database/dbc');
Core::import('SQLBase',   'core/database/sql');

// mvc
Core::import('Controller', 'core/mvc/controller');
Core::import('Mapper',     'core/mvc/model');
Core::import('Behavior',   'core/mvc/model');
Core::import('Model',      'core/mvc/model');
Core::import('View',       'core/mvc/view');

// router
Core::import('Router', 'core/router');

// client comm
Core::import('Cookie', 'core/cookie');

// logger
Core::import('Logger', 'core/logger');

// upload handler (https://github.com/blueimp/jQuery-File-Upload/blob/master/server/php/UploadHandler.php)
Core::import('Uploader', 'core/upload');

// neon parser (para ler configuracoes em .neon)
Core::import('Parser', 'core/parser');

// classes para debug, testes, e utilidades só usadas localmente
if (_DEV) {
  Core::import('UnitTest', 'core/utility');
}

// --------------------------------------
// trata URL ----------------------------
// Não usar $_GET direto (ver: http://us1.php.net/manual/en/filter.examples.sanitization.php )
// a partir do PHP 5.2, é recomendado acessar essas variaveis usando filters, que limpam (sanitizam)
// as variaveis para se tornarem seguras para uso (desde 05/11/13)
// $_GET['a'] é o mesmo que: 
// filter_input(INPUT_GET, 'a', FILTER_SANITIZE_STRING)
$Q = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING, 
                FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);

// -------------------------------------------------------------------------------------
// importando classes adicionais (Import.neon) -----------------------------------------
$cfg = cfg('Import');
foreach ($cfg as $class => $path) {
  Core::import($class, $path);
}
unset($class,$path); //memoria

// -------------------------------------------------------------------------------------
// pegando configurações do banco (Connectios.neon) ------------------------------------
Core::depends('DbcConfig');
$cfg = cfg('Connections');
foreach ($cfg as $name => $dbc_config) {
  
  if (is_string($dbc_config)) {
    // modo link
    DbcConfig::set($name, DbcConfig::get($dbc_config));
    
  } else {
    // modo normal
    DbcConfig::set($name, array(
      '#host'     => $dbc_config['host'],
      '#user'     => $dbc_config['user'],
      '#password' => $dbc_config['pwd'],
      '#schema'   => $dbc_config['schema']
    ));
    
  }
  
}
unset($name,$dbc_config); //memoria

// -------------------------------------------------------------------------------------
// importando controllers e mapeando rotas (Routes.neon) -------------------------------
$cfg = cfg('Routes');
// controllers
foreach ($cfg['controllers'] as $controller => $path) {
  if (is_string($controller))
  Core::register($controller, 'controller'.$path);
}
unset($controller,$path); //memoria

// routes
if (!isset($Router)) {
  $Router = new Router(SITE_URL);
}
foreach ($cfg['routes'] as $name => $route_config) {
  $url = $route_config['url'];
  $target = $route_config['target'];
  
  // normaliza target
  if (is_string($target)) {
    // controller (NomeController)
    $target = array($target => null);
  } else {
    // link (nome#metodo)
    $target = key($target) . '#' . reset($target);
  }
  
  // rota sem nome
  if (is_numeric($name)) {
    $name = null;
  }
  
  // adiciona rotas links dentro da rota
  if ($name && !empty($route_config['links'])) {
    foreach ((array)$route_config['links'] as $subroute_config) {
      // adiciona subrota
      $Router->map($subroute_config['url'], $name.'#'.$subroute_config['method']);
    }
  }
  
  // adiciona rota
  $Router->map($url, $target, $name);
}
unset($route_config,$subroute_config,$url,$target,$name); //memoria


// -------------------------------------------------------------------------------------
// manipulando a session (Session.neon) ------------------------------------------------
$cfg = cfg('Session');
if (!$cfg) {
  // se não foi configurado nenhuma classe, usar a padrao
  $cfg = array('class' => 'SessionFile');
}
// se a classe não existir, criar uma nova classe
Core::uses($cfg['class'], 'core/session');
class_alias($cfg['class'], 'Session');

$Session = new Session();
$Session->table_name = $cfg['table']; // FIXME: provisorio

// token
if (!$_SESSION[SESSION_TOKEN_NAME])
  $_SESSION[SESSION_TOKEN_NAME] = g_token();

// se usuario logou, regerar session
if ($_SESSION['logged']) {
  $Session->regenerateId();
  unset($_SESSION['logged']);
}

/**
 * Token de segurança da sessão.
 * Formulários com informações sensíveis devem trocar informações passando 
 * junto o token e conferindo com o da sessão.
 */
def('TOKEN', $_SESSION[SESSION_TOKEN_NAME]);


// -------------------------------------------------------------------------------------
// configurações de email (MailerAccounts.neon) ----------------------------------------

// TODO!!!!

// -------------------------------------------------------------------------------------
// i18n (Language.neon) ------------------------------------------------
$cfg = cfg('Language');

// verifica se for digitado a lingua na url
$url_parts = explode('/', $Q, 2);
if (in_array($url_parts[0], $cfg['languages']) || isset($cfg['similar'][$url_parts[0]])) {
  // se a lingua for encontrada no url, usar ela
  $Q = $url_parts[1];
  $lang = $url_parts[0];
} else {
  // se não, tentar ler o que vem do browser (header Accept-Language)
  $langs = Request::acceptLanguage();
  $lang = reset($langs); // primeira do accept geralm/e é a lang padrão do browser do user
}

// verificar similaridade
if (isset($cfg['similar'][$lang])) {
  $lang = $cfg['similar'][$lang];
}

/**
 * Lingua padrão do site.
 * A variável fica no formato "pt-br" (hifen, país minusculo)
 */
def('LANG_DEFAULT', $cfg['default']);

/**
 * Lingua escolhida para o site ser mostrado.
 * Depende da localização do usuário (header Accept-Language) ou do que for digitado na
 * URL do site. Ex: www.site.com.br/en-us/menu vai abrir o site em inglês (en-us), mesmo
 * que o usuário seja de outro país.
 * A variável fica no formato "pt-br" (hifen, país minusculo)
 */
def('LANG', $lang);

// acerta os strings de lang (deixa como pt-BR e pt_BR em vez de pt-br)
$_parts = explode('-', $lang, 2);
if ($_parts[1])
  $_parts[1] = strtoupper($_parts[1]);

$lang = implode('-', $_parts);     // ex: pt-BR
$lang_alt = implode('_', $_parts); // ex: pt_BR (versão alternativa)

/**
 * Lingua escolhida para o site ser mostrado.
 * A variável fica no formato "pt-BR" (hifen, país maiusculo)
 * @see LANG
 */
def('LANG_STR', $lang);

/**
 * Versão alternativa da lingua escolhida do site.
 * A variável fica no formato "pt_BR" (underline, país maiusculo)
 * @see LANG
 */
def('LANG_STR_ALT', $lang_alt);

unset($cfg, $url_parts, $_parts, $lang, $lang_alt); // evita variaveis globais indesejadas no sistema