<?php

namespace Djck\aspect;

use Djck\system\AbstractObject;

class Advice extends AbstractObject {
  
  public $priority = null;
  
  public function before($arguments) {
    return $arguments;
  }
  
  public function around() {
    return;
  }
  
  public function after($result) {
    return $result;
  }
  
}