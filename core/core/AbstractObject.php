<?php

namespace Djck\system;

/**
 * Description of AbstractObject
 *
 * @author usuario
 */
abstract class AbstractObject {
  
  public function callMethod($method, $params = array()) {
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
  
}
