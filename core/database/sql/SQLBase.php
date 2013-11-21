<?php

Core::register('SQLConditional', 'core/database/sql');
Core::register('SQLCriteria', 'core/database/sql');
Core::register('SQLEntityBase', 'core/database/sql');
Core::register('SQLExpression', 'core/database/sql');
Core::register('SQLExpressionBase', 'core/database/sql');
Core::register('SQLField', 'core/database/sql');
Core::register('SQLFieldBase', 'core/database/sql');
Core::register('SQLICall', 'core/database/sql');
Core::register('SQLIDelete', 'core/database/sql');
Core::register('SQLIInsert', 'core/database/sql');
Core::register('SQLIInsertAll', 'core/database/sql');
Core::register('SQLISelect', 'core/database/sql');
Core::register('SQLIUpdate', 'core/database/sql');
Core::register('SQLIOnDuplicate', 'core/database/sql');
Core::register('SQLInstructionBase', 'core/database/sql');
Core::register('SQLJoin', 'core/database/sql');
Core::register('SQLParam', 'core/database/sql');
Core::register('SQLProcedure', 'core/database/sql');
Core::register('SQLTDateTime', 'core/database/sql');
Core::register('SQLTable', 'core/database/sql');
Core::import('Interfaces', 'core/database/sql');

class SQLException extends CoreException {};

function _c(SQLBase $field, $sign, $value) {
  return new SQLCriteria($field, $sign, $value);
}

/**
 * Classe abstrata que serve de base para todo o package de classes SQL
 * 
 * @package core
 * @subpackage sql
 * @abstract
 * @author Raphael Hardt <sistema13@furacao.com.br>
 * @version 0.1
 */
abstract class SQLBase {
  
  const F_UCASE = 'UPPER';
  
  const DATEFORMAT = 'DD-MM-YYYY HH24:MI:SS';
  const DATEFORMAT_PHP = 'd-m-Y H:i:s';
  const REGEXP_DATE = '#^\s*[0-9]{1,4}[/.-][0-9]{1,2}[/.-][0-9]{1,4}\s*([0-9]{1,2}:[0-9]{1,2}(:[0-9]{1,2})?)?#i';
    
  protected $hash = null;
  
  /**
   * Contador para gerar os nomes únicos
   * @var type 
   * @access private
   */
  static private $_name_counter = 1;
  
  static protected $_obj_id = 1;
  
  static private $_binds = array();
  static private $_binds_index = 1;
  static private $_binds_hash = array();
  
  static private $validOperators = array(
      'criteria' => array('=', '<', '>', '<=', '>=', '!=', '<>', 'LIKE', 'NOT LIKE', 
          'IN', 'NOT IN', 'IS', 'IS NOT', 'BETWEEN', 'NOT BETWEEN', 'REGEXP'),
      'math' => array('+', '-', '*', '/', '%'),
      'logic' => array('AND', 'OR'),
      'concat' => array('||'),
  );
  
  static private $aggregateFunctions = array(
      'SUM', 'COUNT', 'MAX', 'MIN', 'AVG', 'FIRST', 'LAST',
  );
  
  public function __construct() {
    $this->hash = $this->hash();
  }
  
  protected function _getValidOperators() {
    return self::$validOperators;
  }
  
  protected function _getAggregateFunctions() {
    return self::$aggregateFunctions;
  }
  
  protected function _isDateString($date) {
    /*if ($date instanceof DateTime) 
      return false;
    
    if (is_string($date)) {
      if (preg_match(self::REGEXP_DATE, $date))
        return true;
    }*/
    
    return false;
  }
  
  /**
   * Retorna um nome único para uma expressão (qualquer objeto filho da classe SQLExpression)
   * ou campo (SQLField) que não possui alias, porém precisa de uma identificação.
   * @param $prefix Prefixo para o nome único a ser gerado
   * @return string Nome único para ser usado de alias numa expressão/campo
   */
  static public function getUniqueName($prefix = '') {
    if (!$prefix) {
      $prefix = 'campo';
    }
    return $prefix . self::$_name_counter++;
  }
  
  /**
   * Normaliza o nome para uma string que será usada como chave em arrays que precisam
   * de identificação. Por exemplo: uma SQLTable que tenha um campo chamado "TeSte" é normalizado
   * para "teste" para poder ser retornado tanto procurando por "TESTE", "teste" ou "tEsTe".
   * @param $str Nome a ser normalizado
   * @return string Chave normalizada
   */
  static public function key($str) {
    return strtolower($str);
  }
  
  /**
   * Trata um valor desconhecido para o padrão de banco. Por exemplo, ele transforma
   * valores strings em "'string'" e null em "NULL"
   * @param SQLBase $v Valor a ser tratado
   * @return SQLBase|
   */
  static public function parseValue($v, $name = 'cmp') {
    
    if ($v instanceof SQLBase) {
      return $v;
    } elseif (is_null($v)) {
      return 'NULL';
    } elseif (is_string($v)) {
      $v = (string)$v;
    } elseif ($v instanceof DateTime) {
      $v = $v->format(self::DATEFORMAT_PHP);
      $is_date = true;
    } elseif (is_numeric($v)) {
      // nada
    } elseif (is_bool($v)) {
      $v = $v ? '1' : '0';
    } elseif (is_array($v)) {
      $v = json_encode($v);
    }
    
    self::$_binds[] = $v;
    /*if ($is_date) {
      return "TO_DATE(:$name$i, '".self::DATEFORMAT."')";
    } else {*/
      return "?";
    //}
  }
  
  /**
   * Trata um valor desconhecido para o padrão de banco. Por exemplo, ele transforma
   * valores strings em "'string'" e null em "NULL". Esta função é para o array_walk
   * @param $v
   */
  static public function parseValueIterator(&$v) {
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
        break;
    }
  }
  
  /**
   * Toda class filha de SQLBase deve ter implementada seu toString()
   * @abstract
   */
  abstract function __toString();
  
  abstract protected function hash();
  
  function getHash() {
    return $this->hash;
  }
  
}