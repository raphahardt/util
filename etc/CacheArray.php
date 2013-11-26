<?php

/**
 * Classe que faz cache persistente das informações de um objeto que precisa guardar
 * um numero muito grande de registros.
 *
 * @author sistema13
 */
class CacheArray implements ArrayAccess, Countable, Iterator {
  
  private $storage = array();
  private $objectName = 'tmp';
  public $offset = 0;
  private $limit = 500;
  private $path;
  private $count = 0;
  private $iterator_index = 0;
  
  public function __construct($object) {
    //if (!is_object($object))
      //throw new CoreException('O parametro passado pro CacheArray não é uma classe');
    
    $this->path = TEMP_PATH.DS.'cachearray';
    
    if (!is_dir($this->path)) {
      mkdir($this->path, 0777);
    }
    
    //$this->objectName = get_class($object);
    //$this->objectName = md5(microtime());
    $this->objectName = 'cache';
    
    // limpa diretorio
    if ($dh = opendir($this->path)) {
      while (($file = readdir($dh)) !== false) {
        if (strpos($file, $this->objectName) !== false)
          unlink($this->path . DS .$file);
      }
      closedir($dh);
    }

    $this->path .= DS.$this->objectName;
    //if (!is_file($this->path)) {
      // limpa diretorio
      
      //fclose(fopen($this->path, 'w'));
    //}
  }
    
  public function offsetExists($offset) {
    $this->checkCacheLoad($offset);
    isset($this->storage[$offset]);
  }

  public function &offsetGet($offset) {
    $this->checkCacheLoad($offset);
    return $this->storage[$offset];
  }

  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      $offset = $this->count;
    }
    $this->checkCacheSave($offset);
    if (!isset($this->storage[$offset]))
      ++$this->count;
    $this->storage[$offset] = $value;
  }

  public function offsetUnset($offset) {
    $this->checkCacheLoad($offset);
    unset($this->storage[$offset]);
    --$this->count;
  }
  
  public function count() {
    return $this->count;
  }
  
  public function clean() {
    $this->count = 0;
    $this->storage = array();
  }
  
  protected function setPage($page) {
    $this->offset = (int)($page * $this->limit);
  }
  
  protected function getPageByOffset($offset) {
    return (int)floor($offset / $this->limit);
  }
  
  protected function isOutOfRange($offset) {
    return ($offset < $this->offset) || ($this->offset + $this->limit <= $offset);
  }
  
  protected function setCache() {
    file_put_contents($this->path.'-'.$this->offset, serialize($this->storage));
  }
  
  protected function getCache() {
    if (!is_file($this->path.'-'.$this->offset)){
      $this->storage = array();
      return;
    }
    $this->storage = unserialize(file_get_contents($this->path.'-'.$this->offset));
    reset($this->storage);
  }
  
  protected function checkCacheLoad($offset) {
    if ($this->isOutOfRange($offset)) {
      //if (!is_file($this->path.'-'.$this->offset)){
        $this->setCache();
      //}
      
      $newpage = $this->getPageByOffset($offset);
      $this->setPage($newpage);
      
      $this->getCache();
    }
  }
  
  protected function checkCacheSave($offset) {
    if ($this->isOutOfRange($offset)) {
      $this->setCache();
      
      $newpage = $this->getPageByOffset($offset);
      $this->setPage($newpage);
      
      $this->getCache();
    }
  }

  public function current() {
    return current($this->storage);
  }

  public function key() {
    return key($this->storage);
  }

  public function next() {
    ++$this->iterator_index;
    $return = next($this->storage);
    if (!$return) {
      $this->checkCacheLoad($this->iterator_index);
      ++$this->iterator_index;
      //$return = reset($this->storage);
    }
    return $return;
  }

  public function rewind() {
    $this->iterator_index = 0;
    $this->checkCacheLoad($this->iterator_index);
    reset($this->storage);
  }

  public function valid() {
    $key = key($this->storage);
    $var = ($key !== NULL && $key !== FALSE);
    return $var;
  }

}