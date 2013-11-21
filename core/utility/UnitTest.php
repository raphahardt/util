<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UnitTest
 *
 * @author usuario
 */
class UnitTest {
  private $test;
  private $result;
  private $expected = null;
  private $expected_sign = '';
  private $negate = array();
  private $final = null;
  private $_pointer = 0;
  
  function __construct($test) {
    $this->test = $test;
    return $this;
  }
  
  function expect($result) {
    $this->result[$this->_pointer] = $result;
    return $this;
  }
  
  function not() {
    if (!isset($this->negate[$this->_pointer])) $this->negate[$this->_pointer] = false;
    $this->negate[$this->_pointer] = !$this->negate[$this->_pointer];
    return $this;
  }
  
  function also() {
    ++$this->_pointer;
    $this->result[$this->_pointer] = $this->result[$this->_pointer-1];
    return $this;
  }
  
  function toBe($expected, $strict = true) {
    $this->expected[$this->_pointer] = $expected;
    $this->expected_sign[$this->_pointer] = $strict ? '=' : 'lazy=';
    $this->final[$this->_pointer] = $strict ? $this->result[$this->_pointer] === $expected : $this->result[$this->_pointer] == $expected;
    return $this;
  }
  
  /**
   * Obsoleta. 
   * @deprecated Obsoleta. Use ->not()->toBe()...
   */
  function notToBe($expected, $strict = true) {
    $this->expected[$this->_pointer] = $expected;
    $this->expected_sign[$this->_pointer] = $strict ? '!=' : 'lazy!=';
    $this->final[$this->_pointer] = $strict ? $this->result[$this->_pointer] !== $expected : $this->result[$this->_pointer] != $expected;
    return $this;
  }
  
  function toInstanceOf($instance) {
    if (is_object($instance))
      $this->result[$this->_pointer] = get_class($instance);
    
    $this->expected[$this->_pointer] = $instance;
    $this->expected_sign[$this->_pointer] = 'instanceof';
    $this->final[$this->_pointer] = $this->result[$this->_pointer] instanceof $instance;
    return $this;
  }
  
  function toBeOfType($expected) {
    $this->expected[$this->_pointer] = $expected;
    $this->expected_sign[$this->_pointer] = 'ser do tipo';
    $this->final[$this->_pointer] = gettype($this->result[$this->_pointer]) === $expected;
    return $this;
  }
  
  function toBeGreaterThan($expected) {
    $this->expected[$this->_pointer] = $expected;
    $this->expected_sign[$this->_pointer] = '>';
    $this->final[$this->_pointer] = $this->result[$this->_pointer] > $expected;
    return $this;
  }
  
  function toBeGreaterOrEqualThan($expected) {
    $this->expected[$this->_pointer] = $expected;
    $this->expected_sign[$this->_pointer] = '>=';
    $this->final[$this->_pointer] = $this->result[$this->_pointer] >= $expected;
    return $this;
  }
  
  function toBeLowerThan($expected) {
    $this->expected[$this->_pointer] = $expected;
    $this->expected_sign[$this->_pointer] = '<';
    $this->final[$this->_pointer] = $this->result[$this->_pointer] < $expected;
    return $this;
  }
  
  function toBeLowerOrEqualThan($expected) {
    $this->expected[$this->_pointer] = $expected;
    $this->expected_sign[$this->_pointer] = '<=';
    $this->final[$this->_pointer] = $this->result[$this->_pointer] <= $expected;
    return $this;
  }
  
  function __toString() {
    $trace = debug_backtrace();
    $trace = reset($trace);
    
    $id = uniqid();
    
    $s = '';
    
    $s .= '<div style="border:1px solid #ccc;margin:4px;padding:2px;"><div style="border:1px solid #ccc;margin:4px;background:#eee; padding:9px;" onclick="document.getElementById(\''.$id.'\').style.display = document.getElementById(\''.$id.'\').style.display===\'block\'?\'none\':\'block\'">Teste: <strong>' . $this->test . '</strong> <span style="color:#aaa;">(line '.($trace['line']).')</span></div>';
    $s .= '<div id="'.$id.'" style="font-size:80%;padding:9px;background:#fff;display:none">';
    
    for ($i=0;$i<count($this->result);$i++) {
      $result = $this->result[$i];
      $expected = $this->expected[$i];
      $expected_sign = $this->expected_sign[$i];
      $final = $this->final[$i];
      
      if ($this->negate[$i]) {
        $final = !$final;
        $expected_sign = 'NOT '.$expected_sign;
      }
      
      if (is_object($result))
        $result = get_class($result);
      
      if (!$final) {
        $s = str_replace('background:#eee', 'background:#fcc;color:red;border-color:red', $s);
      }
      
      $s .= '<br>'.($i+1).'#'.
            ''.(!$final ? '<div style="color:red;font-weight:bold;background:#fcc">FALHA</div>' : '<div style="color:green;background:#cfc">OK</div>').
            '<br>Esperado: <span style="color:blue">'.$expected_sign.' '.var_export($expected, true).'</span> ('.gettype($expected).')'.
            '<br>Resultado: <span style="color:blue">'.var_export($result, true) . '</span> ('. gettype($result). ')';
    }
    
    $s .= '</div>';
    $s .= '</div>';
    
    return $s;
  }
  
}

function it($test) {
  $unit = new UnitTest($test);
  return $unit;
}

