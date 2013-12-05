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

/**
 * Classes, geralmente campos (Field), que podem ser ordenadas num SELECT
 */
interface Ordenable {
  /**
   * Retorna o campo e sua ordenação (asc ou desc)
   * 
   * @return array
   */
  function getOrder();
  /**
   * Define uma ordenação relativo ao campo.
   * 
   * @param array $order
   */
  function setOrder($order);
}

/**
 * Classes que possuem alias.
 */
interface HasAlias {
  /**
   * Retorna o alias.
   * 
   * @return string
   */
  function getAlias();
  /**
   * Define um alias.
   * 
   * @param string $alias
   */
  function setAlias($alias);
  /**
   * Retorna o alias, caso o objeto tenha, ou o nome real dele.
   * 
   * @return string
   */
  function toAlias();
}

/**
 * Classes que podem ser negadas ou não.
 * 
 * Útil em expressões complexas.
 */
interface Negable {
  /**
   * Retorna se a expressão está negada.
   * 
   * @return boolean
   */
  function getNegate();
  /**
   * Define a expressão como negada.
   * 
   * @param boolean $neg
   */
  function setNegate($neg);
}

/**
 * Classes que podem ser envolvidas em uma função.
 * 
 * Útil para campos que precisam ser aplicados funções diretamente na query.
 * 
 */
interface HasFunction {
  /**
   * Retorna as funções definidas.
   * 
   * @return array
   */
  function getFunction();
  /**
   * Define uma função para o objeto.
   * 
   * @param string $function Nome da função
   * @param mixed[] $params Um array de parametros
   */
  function setFunction($function, $params = null);
  /**
   * Define se as funções serão mostradas na string ou não.
   * 
   * @param boolean $bool
   */
  function showFunctions($bool);
}

/**
 * Classes que possuem operadores de ligação.
 * 
 * Para expressões lógicas, podem ser AND ou OR.
 * Para expressões comparativas, podem ser =, !=, >, <, etc...
 * Para expressões matemáticas, podem ser +, -, *, /...
 */
interface HasOperator {
  /**
   * Retorna o operador de ligação da expressão.
   * 
   * @return string
   */
  function getOperator();
  /**
   * Define um operador de ligação para expressão.
   * 
   * @param string $operator
   */
  function setOperator($operator);
}

/**
 * Classes que podem ser selecionáveis num SELECT
 * 
 * Por exemplo: SELECT inSelect1, inSelect2, ... FROM
 */
interface InSelect {
  
}

/**
 * Classes que podem ser usadas como expressão.
 * 
 * Geralmente possuem operadores de ligação (HasOperator), podem ser nagadas
 * (Negable) e podem ter alias (HasAlias)
 */
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
   * @return SQLBase
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
