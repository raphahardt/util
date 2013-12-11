<?php

namespace Djck\database\query\base;

use Djck\Core;
use Djck\database\query\interfaces;

Core::uses('Base', 'Djck\database\query\base');

/**
 * Description of ExpressionBase
 *
 * @author Rapha e Dani
 */
abstract class FieldBase 
extends Base 
implements interfaces\HasAlias, interfaces\Ordenable, 
        interfaces\HasFunction, interfaces\InSelect {
  
  protected $alias = null;
  protected $orderDesc = false;
  protected $functions = array();
  protected $showFunctions = true;
  
  protected $parentTable = null;
  
  /**
   * 
   * @param \Djck\database\query\base\EntityBase $t
   */
  public function setTable(EntityBase &$t) {
    $this->parentTable = $t;
  }
  
  /**
   * 
   * @return \Djck\database\query\base\EntityBase
   */
  public function getTable() {
    return $this->parentTable;
  }
  
  public function setAlias($alias) {
    $this->alias = $alias;
  }
  
  public function getAlias() {
    return $this->alias;
  }
  
  public function toAlias() {
    if ($this->alias) {
      return $this->alias;
    } else {
      return (string)$this;
    }
  }
  
  public function getFunction() {
    $last_function = end($this->functions);
    return $last_function['name'];
  }


  public function setFunction($func, $params = array()) {
    $args = func_get_args();
    array_shift($args);
    
    $function_params = array();
    
    if (count($args) > 1) {
      $function_params = $args;
    } elseif (count($args) == 1) {
      if (is_array($params)) {
        $function_params = $params;
      } else {
        $function_params[] = $params;
      }
    }
    
    $this->functions[] = array( 'name' => $func, 'params' => $function_params );
  }
  
  public function unsetFunction($func = null) {
    if (!$func) {
      $this->functions = array();
      return;
    }
    foreach ($this->functions as $index => $func) {
      if ($func['name'] == $func) {
        unset($this->functions[$index]);
      }
    }
  }
  
  public function showFunctions($bool) {
    $this->showFunctions = (bool)$bool;
  }

  public function getOrder() {
    return ($this->orderDesc ? 'DESC' : 'ASC');
  }
  
  public function setOrder($order) {
    $this->orderDesc = (strtoupper($order) == 'DESC' ? true : false );
  }
  
}
