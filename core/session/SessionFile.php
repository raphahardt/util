<?php

namespace Djck\session;

use Djck\Core;
use Djck\session\SessionBuilder;

Core::uses('SessionBuilder', 'Djck\session');

class SessionFile extends SessionBuilder {
  
  private $savePath;
  
  public function open($savePath, $session_name) {
    
    if (!isset($this->savePath)) {
      $this->savePath = $savePath;
    }
    $this->savePath .= DS;
    
    if (!is_dir($this->savePath)) {
      mkdir($this->savePath, 0777);
    }
    return true;
  }

  public function close() {
    return true;
  }

  public function read($sid) {
    $string = '';
    try {
      $string = (string)@file_get_contents($this->savePath.SESSION_NAME."_$sid");
    } catch (Exception $e){}
    return $string;
  }

  public function write($sid, $data) {
    return file_put_contents($this->savePath.SESSION_NAME."_$sid", $data) === false ? false : true;
  }

  public function destroy($sid) {
    $file = $this->savePath.SESSION_NAME."_$sid";
    if (file_exists($file)) {
      unlink($file);
    }
    return parent::destroy($sid);;
  }

  public function gc($lifetime) {
    foreach (glob($this->savePath .SESSION_NAME."_*") as $file) {
      if (filemtime($file) + $lifetime < time() && file_exists($file)) {
        unlink($file);
      }
    }
    return true;
  }
  
}
