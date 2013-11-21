<?php

date_default_timezone_set('America/Sao_Paulo');

// SITE --------------------------------------------------------------

/**
 * Titulo do site
 */
def('SITE_TITLE', 'TESTE');

/**
 * Subtitulo do site
 */
def('SITE_SUBTITLE', 'TESTE');

/**
 * Autor do site
 */
def('SITE_OWNER', 'Raphael Hardt');

/**
 * Copyright relacionado ao site
 */
def('SITE_COPYRIGHT', 'Raphael Hardt - Creative Commons (BY-NC-ND) 3.0');

/**
 * Keywords
 */
def('SITE_KEYWORDS', '');

/**
 * Description
 */
def('SITE_DESCRIPTION', '');

/**
 * Raiz de onde o site roda (path relativo ao DOC_ROOT configurado pelo server)
 */
if (!defined('SITE_URL')) {
  $root = str_replace('/', DS, env('DOCUMENT_ROOT'));
  $baseurl = str_replace(DS, '/', str_ireplace($root, '', ROOT));
  if (strpos($baseurl, '/') !== 0)
    $baseurl = '/'.$baseurl;
  
  if ($baseurl === '/') $baseurl = '';
  
  define('SITE_URL', $baseurl);
  
  unset($root, $baseurl);
}

/**
 * Host completo do site
 */
if (!defined('SITE_FULL_URL')) {
  $s = null;
  if (env('HTTPS')) {
    $s = 's';
  }

  $http_host = env('HTTP_HOST');
  if (!isset($http_host)) {
    $http_host = env('SERVER_NAME');
  }
  define('SITE_FULL_URL', 'http'.$s.'://'.$http_host . SITE_URL);
  
  unset($http_host, $s);
}

if (!defined('SITE_DOMAIN')) {
  if (env('HTTP_BASE') === '.localhost')
    define('SITE_DOMAIN', '');
  else
    define('SITE_DOMAIN', env('HTTP_BASE'));
}

/**
 * Host de onde estão os resources (imagens, js, css) estáticos
 */
def('STATIC_URL', SITE_FULL_URL.'/public');

/**
 * Charset do site
 */
def('SITE_CHARSET', 'utf-8');

/**
 * Se o site está offline ou não
 */
def('SITE_OFFLINE', false);

// SESSION --------------------------------------------------------------------------

/**
 * Nome do cookie que vai gravar o ID da session atual. Deve ser um nome curto,
 * identificável e conter apenas letras, de preferencia maiusculas.
 */
def('SESSION_NAME', 'DJCKID');

/**
 * Numero em segundos do tempo que o cookie da session irá ficar disponível para
 * o usuário enquanto ele estiver em "idle". 0 (zero) significa que o cookie só
 * é apagado quando o browser é fechado.
 */
def('SESSION_TIMEOUT', 0); 

/**
 * Nome da session que irá guardar o token de acesso do usuário
 */
def('SESSION_TOKEN_NAME', 'token');

/**
 * Nome da session que irá guardar o usuário logado no site
 */
def('SESSION_USER_NAME', 'user');


// COOKIES --------------------------------------------------------------------------

/**
 * Dominio onde os cookies serão gravados
 */
def('COOKIE_DOMAIN', SITE_DOMAIN);

/**
 * Pasta onde os cookies serão gravados
 */
def('COOKIE_PATH', '/');


// CACHE ---------------------------------------------------------------------------

/**
 * Tempo máximo de cache para resources estáticos
 */
def('CACHE_STATIC_SIZE', 1 * 30 * 24 * 60 * 60); // 1 mes

/**
 * O mesmo que CACHE_STATIC_SIZE, mas em formato timestamp
 */
def('CACHE_STATIC_TIMESTAMP', time() + CACHE_STATIC_SIZE);