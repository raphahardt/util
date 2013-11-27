<?php

namespace App\controller\minify;

use Djck\Core;
use App\controller\AppController;
use Minify;
use Minify_Loader;
use Minify_Logger;

Core::uses('AppController', 'App');

Core::import('Minify_Loader', '/plugin/min/lib/Minify/Loader.php'); // nome do arquivo não bate com nome da classe

/**
 * Description of HomeController
 *
 * @author usuario
 */
class MinifyController extends AppController {
  
  function index($file) {
    // try to disable output_compression (may not have an effect)
    ini_set('zlib.output_compression', '0');
    
    // normaliza o parametro file para definir qual será o arquivo a ser servido pelo minify
    $parts = explode('/', $file);
    if (count($parts) == 1) {
      // group
      $_GET['g'] = $file;
    } else {
      // files
      $files = explode(',', $file);
      foreach ($files as &$f) {
        $tmp = preg_split('#(js|css)/#i', $f, 2);
        $ext = explode('.', $tmp[1]);
        $ext = end($ext);
        $f = $tmp[0] . "public/$ext/" . $tmp[1];
      }
      unset($f);
      $file = implode(',', $files);
      
      $_GET['f'] = $file;
    }
    
    // procura ver se o request está vindo do mesmo host, se não, mandar 403 (forbidden)
    // evita usarem os resources direto do seu dominio
    $referer = $this->request->referer();
    if (!_DEV && (empty($referer) /*acesso direto*/ || 
        strpos($referer, SITE_FULL_URL) === false /*acesso externo*/)) {
      $this->response->statusCode(403);
      echo '<h1>Forbidden</h1>'; // resposta curta e simples, como feedback visual apenas
      return;
    }
    unset($referer);
    
    // carrega as configurações do minify
    $config = cfg('Minify');

    Minify_Loader::register();
    Minify::$uploaderHoursBehind = $config['uploaderHoursBehind'];
    Minify::setCache(
            isset($config['cachePath']) ? $config['cachePath'] : '', $config['cacheFileLocking']
    );
    // cria pasta, se ela nao existe
    if (isset($config['cachePath']) && !is_dir($config['cachePath'])) {
      mkdir($config['cachePath'], 0777);
    }

    if ($config['documentRoot']) {
      $_SERVER['DOCUMENT_ROOT'] = $config['documentRoot'];
      Minify::$isDocRootSet = true;
    }
    
    // algumas opções fixas que não serão mudadas pelo arquivo de config
    $config['serveOptions']['rewriteCssUris'] = false;
    //$config['serveOptions']['minifierOptions']['text/css']['prependRelativePath'] = SITE_URL.'/public/css/';
    $config['serveOptions']['bubbleCssImports'] = false;
    $config['serveOptions']['quiet'] = true; // faz minify retornar em vez de imprimir na tela
    $config['serveOptions']['debug'] = $config['debug'];
    $config['serveOptions']['minApp']['allowDirs'] = array(
        '//public', // alias para document root
        // adicionar mais caminhos permitidos
    );
    $_SERVER['DOCUMENT_ROOT'] = ROOT;

    // links simbolicos (não usarei por enquanto)
    /*$min_serveOptions['minifierOptions']['text/css']['symlinks'] = $min_symlinks;
    // auto-add targets to allowDirs
    foreach ($min_symlinks as $uri => $target) {
      $min_serveOptions['minApp']['allowDirs'][] = $target;
    }*/
    
    // debug
    if ($config['errorLogger']) {
      if (true === $config['errorLogger']) {
        $config['errorLogger'] = FirePHP::getInstance(true);
      }
      Minify_Logger::setLogger($config['errorLogger']);
    }

    // max age
    if (is_string($config['serveOptions']['maxAge'])) {
      $config['serveOptions']['maxAge'] = strtotime($config['serveOptions']['maxAge']) - time();
    }

    // check for URI versioning
    if (preg_match('/&\\d/', env('QUERY_STRING'))) {
      $config['serveOptions']['maxAge'] = 31536000; // 1 ano
    }
    // well need groups config
    if (isset($_GET['g'])) 
      $config['serveOptions']['minApp']['groups'] = $config['groups'];
    
    if (isset($_GET['f']) || isset($_GET['g'])) {
      // serve!   
      $result = Minify::serve(new Minify_Controller_MinApp(), $config['serveOptions']);
    }
    
    // imprime e manda os headers pelo controller, se deu certo
    if ($result['success']) {
      $this->response->statusCode($result['statusCode']);
      $this->response->header($result['headers']);
      echo $result['content'];
    } else {
      $this->response->statusCode(404);
      echo '<h1>Not Found</h1>'; // resposta curta e simples, como feedback visual apenas
    }
  }
  
}