<?php

namespace App\controller\_static;

use Djck\Core;
use App\contoller\AppController;

Core::uses('AppController', 'App');

class Resource {
  
}

class ImageResource extends Resource {
  
  protected $file;
  protected $ext;
  protected $sizes_source;
  protected $ratio_source;
  protected $sizes_dest;
  
  // serve para evitar sobrecarga de arquivos no servidor em permitir 
  // qualquer tipo de tamanho, fazendo com que se crie infinitas imagens
  // no tmp. fazendo o limite abaixo, eu garanto que apenas count(array) imagens daquela
  // mesma imagem exista no servidor, + ela mesma
  private $permitted_sizes = array(
      100, 150, 200, 300, 350, 400, 500, 600, 800, 900, 1000, 1200
  );
  // o mesmo que o de cima, mas para tamanhos relativos a imagem original
  private $permitted_percents = array(
      0.25, 0.5, 0.75, 1, 2, 4
  );
  
  public function __construct($file) {
    
    $this->setFile($file);
    
    // pega tamanho da imagem
    if ($this->file && $this->isImage()) {
      list($width, $height) = getimagesize($this->file);

      $this->sizes_dest = $this->sizes_source = array($width, $height);
      $this->ratio_source = $width / $height;
    }
    
  }
  
  public function isImage() {
    return $this->ext === 'jpg' || $this->ext === 'png';
  }
  
  public function setFile($file) {
    //$file_notfound = DJCK.DS.'public'.DS.'img'.DS.'core'.DS.'notfound.png';
    
    $dirs_to_search = array(
        ROOT.DS.'public'.DS.'img'.DS,
        DJCK.DS.'public'.DS.'img'.DS,
    );
    
    $file = str_replace('/', DS, $file);
    
    // procura o arquivo nas pastas do core e da aplicação, e retorna a primeira
    // que encontrar
    $found = false;
    foreach ($dirs_to_search as $dir) {
      if (is_file($dir.$file)) {
        $file = $dir.$file;
        $found = true;
        break;
      }
    }
    
    // se não for encontrada
    if (!$found) {
      //$file = $file_notfound;
      $this->unsetFile();
      return;
    }
    
    // extensao
    $parts = explode('.', $file);
    $ext = array_pop($parts);
    
    $this->file = $file;
    $this->ext = $ext;
  }
  
  public function unsetFile() {
    $this->file = $this->ext = null;
  }
  
  public function getLastModifiedTime() {
    return filemtime($this->file);
  }
  
  public function output() {
    if (!$this->file) { // arquivo nao existe
      return;
    }
    
    $cache_file = $this->_tmpName();
    if (is_file($cache_file)) {
      readfile($cache_file);
    }
  }
  
  public function setSizes($width, $height = null) {
    if (!$this->file) { // arquivo nao existe
      return false;
    }
    // verifica se o width está dentro do permitido
    if (!in_array($width, $this->permitted_sizes)) {
      //$this->__construct('core/notfound.png');
      $this->unsetFile();
      return;
    }
    // se não for definido height, calcular novo height pelo ratio original
    if (!$height) { // null, 0, false
      if ($width > $height) {
        $height = (int)($width / $this->ratio_source);
      } else {
        $height = $width;
        $width = (int)($height * $this->ratio_source);
      }
    }
    $this->sizes_dest = array($width, $height);
  }
  
  protected function _tmpName() {
    return TEMP_PATH.DS.'images'.DS.md5($this->file.implode('.',$this->sizes_dest)).'.png';
  }
  
  public function render() {
    
    if (!$this->file) { // arquivo nao existe
      return false;
    }
    
    $cache_file = $this->_tmpName();
    
    if (!is_file($cache_file)) {
      
      $filename = $this->file;
      $ext = $this->ext;
      $w = $this->sizes_dest[0];
      $h = $this->sizes_dest[1];
      $ow = $this->sizes_source[0];
      $oh = $this->sizes_source[1];
            
      if ($ext === 'jpg' || $ext === 'jpeg') {
        $im2 = imagecreatefromjpeg($filename);
      } elseif ($ext === 'png') {
        $im2 = imagecreatefrompng($filename);
        imagealphablending( $im2, false );
        imagesavealpha( $im2, true );
      } elseif ($ext === 'gif') {
        $im2 = imagecreatefromgif($filename);
      } else {
        $im2 = imagecreatetruecolor(100, 40);
      }
      
      $im = imagecreatetruecolor($w, $h);
      imagealphablending( $im, false );
      imagesavealpha( $im, true );

      // White background and blue text
      //$textcolor = imagecolorallocate($im, 0, 0, 255);

      imagecopyresampled($im, $im2, 0, 0, 0, 0, $w, $h, $ow, $oh);

      // Write the string at the top left
      //imagestring($im, 3, 0, 0, $filename, $textcolor);
      //imagestring($im, 3, 0, 10, $w.'x'.$h, $textcolor);


      if (!is_dir(TEMP_PATH.DS.'images')) {
        mkdir(TEMP_PATH.DS.'images', 0744);
      }

      $return = imagepng($im, $cache_file, 9);
      imagedestroy($im);
      
    }
    
    return $return === false ? false : $cache_file;
  }
  
}

/**
 * Description of HomeController
 *
 * @author usuario
 */
class StaticContentController extends AppController {
  
  function index() {
    // processa o parametro
    $file = $this->request->params['file'];
    
    $parts_file = explode('/', $file);
    // detecta se o primeiro pedaço do nome do arquivo é um "sizes" (999x999)
    if (is_numeric($parts_file[0])) {
      $width = array_shift($parts_file);
      $height = null;
    } elseif (strpos($parts_file[0], 'x') !== false) {
      $parts_size = explode('x', $parts_file[0], 2);
      if (is_numeric($parts_size[0]) && is_numeric($parts_size[1])) {
        array_shift($parts_file);
        $width = $parts_size[0];
        $height = $parts_size[1];
      }
      unset($parts_size); //memoria
    }
    $file = implode(DS, $parts_file);
    unset($parts_file); //memoria
    
    
    // cria um objeto de criacao de imagem
    $im = new ImageResource($file);
    if ($width) {
      $im->setSizes($width, $height);
    }
    
    // renderiza o novo arquivo (ou pega do cache)
    // retorna o nome do arquivo
    $new_file = $im->render();
    
    // verifica cache e outputa o arquivo
    if ($new_file !== false) {
      $this->response->cache($im->getLastModifiedTime(), "+1 month");
      $this->response->type('png');
      $modified = $this->response->checkNotModified($this->request);

      if (!$modified) {
        $im->output();
      }
    } else {
      // imagem nao criada ou nao encontrada
      $this->response->statusCode(404);
    }
  }
  
  
  function imagem() {
    
    /*if ($this->request->referer() != SITE_FULL_URL.'/') {
      //$this->response->statusCode(404);
      // Create a 100*30 image
      $im = imagecreate(500, 30);

      // White background and blue text
      $bg = imagecolorallocate($im, 255, 255, 255);
      $textcolor = imagecolorallocate($im, 0, 0, 255);

      // Write the string at the top left
      imagestring($im, 3, 0, 0, $this->request->referer(), $textcolor);
      imagestring($im, 3, 0, 10, SITE_FULL_URL.'/', $textcolor);

      // Output the image
      $this->response->type('png');

      imagepng($im);
      imagedestroy($im);
      //echo 'erro';
      return;
    }*/
    
    $this->response->cache(mktime(0,0,0, 6, 8, 2013), '+1 day');
    $this->response->type('jpg');
    $modified = $this->response->checkNotModified($this->request);
    
    if (!$modified) {
      readfile(DJCK.DS.'123.jpg');
    }
  }
  
}