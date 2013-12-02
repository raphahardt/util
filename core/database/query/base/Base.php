<?php

namespace Djck\database\query\base;

use Djck\system\AbstractObject;
use Djck\types;

/**
 * Exceptions
 */

class QueryException extends \Djck\CoreException {}

/**
 * Interfaces
 * @todo passar tudo para trait, quando PHP dos servidores forem pra 5.4
 */

interface Ordenable {
  function getOrder();
  function setOrder($order);
}

interface HasAlias {
  function getAlias();
  function setAlias($alias);
  function toAlias();
}

interface Negable {
  function getNegate();
  function setNegate($neg);
}

interface HasFunction {
  function getFunction();
  function setFunction($function, $params = null);
  function showFunctions($bool);
}

interface HasOperator {
  function getOperator();
  function setOperator($operator);
}

interface InSelect {
  
}

interface Expressee {
  
}

/**
 * Description of Base
 *
 * @author Rapha e Dani
 */
abstract class Base extends AbstractObject {
  
  const DATEFORMAT_TO_SAVE = 'd-m-Y H:i:s';
  const DATEFORMAT_TO_RETURN = 'd-m-Y H:i:s';
  
  protected $hash = null;
  
  static private $_binds = array();
  static private $_binds_index = 1;
  static private $_binds_hash = array();
  
  static protected $validOperators = array(
      'criteria' => array('=' => 1, '<' => 1, '>' => 1, '<=' => 1, 
                          '>=' => 1, '!=' => 1, '<>' => 1, 
                          'LIKE' => 1, 'NOT LIKE' => 1, 
                          'IN' => 1, 'NOT IN' => 1, 'IS' => 1, 'IS NOT' => 1, 
                          'BETWEEN' => 1, 'NOT BETWEEN' => 1, 'REGEXP' => 1),
      'math' => array('+' => 1, '-' => 1, '*' => 1, '/' => 1, '%' => 1),
      'logic' => array('AND' => 1, 'OR' => 1),
      'concat' => array('||' => 1),
  );
  
  static protected $aggregateFunctions = array(
      'SUM' => 1, 'COUNT' => 1, 'MAX' => 1, 'MIN' => 1, 
      'AVG' => 1, 'FIRST' => 1, 'LAST' => 1,
  );
  
  public function __construct() {
    ;
  }
  
  /**
   * Trata um valor desconhecido para o padrão de banco. Por exemplo, ele transforma
   * valores strings em "'string'" e null em "NULL"
   * @param SQLBase $v Valor a ser tratado
   * @return SQLBase|
   */
  static public function parseValue($v) {
    
    // proprio campo
    if ($v instanceof Base) {
      return $v;
    } 
    // nulo não precisa de bind
    elseif (is_null($v)) {
      return 'NULL';
    } 
    // strings
    elseif (is_string($v)) {
      $v = (string)$v;
    } 
    // data/hora em formato de string, converter para DateTime e fazer parse de novo
    elseif (types\DateTime::seemsDateTime($v)) {
      return self::parseValue(new types\DateTime($v));
    } 
    // data/hora
    elseif ($v instanceof \DateTime) {
      $v = $v->format(self::DATEFORMAT_TO_SAVE);
    } 
    // numeros
    elseif (is_numeric($v)) {
      // nada
    } 
    // booleanos (varchar(1))
    elseif (is_bool($v)) {
      $v = $v ? '1' : '0';
    } 
    // arrays
    elseif (is_array($v) || $v instanceOf \stdClass) {
      $v = json($v);
    }
    
    // guarda o binding
    self::$_binds[] = $v;
    return "?"; // tipo de bind do mysql
  }
  
  static public function parseValueWalk(&$v) {
    $v = self::parseValue($v);
  }
  
  public function addBind($bind, $val) {
    /*if (strpos($bind, ':') !== 0) 
      throw new SQLException('Nome do bind precisa começar com :');*/
    
    self::$_binds[] = $val;
  }
  
  public function getBinds() {
    return self::$_binds;
  }
  
  public function clearBinds() {
    foreach (self::$_binds as $b) {
      unset($b);
    }
    foreach (self::$_binds_hash as $b) {
      unset($b);
    }
    self::$_binds = array();
    self::$_binds_hash = array();
    self::$_binds_index = 1;
  }
  
  protected function makeHash() {
    return uniqid();
  }
  
  function getHash() {
    if (!$this->hash) {
      $this->hash = $this->makeHash();
    }
    return $this->hash;
  }
  
  abstract function __toString();
  
}
