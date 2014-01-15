<?php

namespace Djck\system;

/**
 * Description of AbstractObject
 *
 * @author usuario
 */
abstract class AbstractObject {
  
  public function __construct() {
    // roda todas as pré-configurações
    $this->cfg();
  }
  
  /**
   * Função que todos os objetos podem definir e servem como configurador dos mesmos.
   * 
   * Útil para controllers que precisam fazer uma pré-configuração dos advices (criar aspectos)
   * 
   * @return type
   */
  protected function cfg() {
    return;
  }
  
  public function callMethod($method, $params = array()) {
    if (!method_exists($this, $method)) {
      throw new \BadMethodCallException("Method '$method' not exists in ".get_class($this));
    }
    switch (count($params)) {
      case 0:
        return $this->{$method}();
      case 1:
        return $this->{$method}($params[0]);
      case 2:
        return $this->{$method}($params[0], $params[1]);
      case 3:
        return $this->{$method}($params[0], $params[1], $params[2]);
      case 4:
        return $this->{$method}($params[0], $params[1], $params[2], $params[3]);
      case 5:
        return $this->{$method}($params[0], $params[1], $params[2], $params[3], $params[4]);
      case 6:
        return $this->{$method}($params[0], $params[1], $params[2], $params[3], $params[4], $params[5]);
      default:
        return call_user_func_array(array(&$this, $method), $params);
    }
  }
  
  public function callStaticMethod($method, $params = array()) {
    $method_ = get_called_class().'::'.$method;
    if (!function_exists($method_)) {
      throw new \BadMethodCallException("Method '$method' not exists in ".get_called_class());
    }
    switch (count($params)) {
      case 0:
        return static::$method();
      case 1:
        return static::$method($params[0]);
      case 2:
        return static::$method($params[0], $params[1]);
      case 3:
        return static::$method($params[0], $params[1], $params[2]);
      case 4:
        return static::$method($params[0], $params[1], $params[2], $params[3]);
      case 5:
        return static::$method($params[0], $params[1], $params[2], $params[3], $params[4]);
      case 6:
        return static::$method($params[0], $params[1], $params[2], $params[3], $params[4], $params[5]);
      default:
        return call_user_func_array($method_, $params);
    }
  }
  
}
