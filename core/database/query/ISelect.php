<?php

namespace Djck\database\query;

use Djck\Core;

Core::uses('InstructionBase', 'Djck\database\query\base');

/**
 * Description of ISelect
 *
 * @author Rapha e Dani
 */
class ISelect
extends base\InstructionBase 
implements interfaces\HasAlias, interfaces\Ordenable, interfaces\HasFunction, 
        interfaces\Expressee, interfaces\InSelect {
  
  private $alias;
  private $fields = array();
  private $entities = array();
  //private $filter;
  private $orderby = array();
  //private $groupby = array(); group by será automático, dependendo dos fields e das funcoes q ele tiver
  
  protected $orderDesc = false;
  protected $functions = array();
  protected $showFunctions = true;
  
  private $distinct = false;
  
  protected function makeHash() {
    return $this->getAlias();
  }
  
  public function __construct($fields, $from, $where = null, $order = null, $defs = null) {
    if (!is_array($fields)) $fields = array($fields);
    if (!is_array($from)) $from = array($from);
    if (!is_array($order)) $order = array($order);
    
    $this->fields = $fields;
    $this->entities = $from;
    $this->filter = $where;
    $this->orderby = $order;
    
    parent::__construct();
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
      return rand(1, 5000);
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
  
  public function setDistinct($bool) {
    $this->distinct = (bool)$bool;
  }
  
  public function getDistinct() {
    return $this->distinct;
  }
  
  
  public function __toString() {
    
    parent::__toString();
    
    // variaveis para fazer o group by automatico
    $groupbyFields = array();
    $doGroupby = false;
    
    $s = 'SELECT ';
    if ($this->distinct) {
      $s .= 'DISTINCT ';
    }
    if (empty($this->fields)) {
      $s .= '*';
    } else {
      $sSel = '';
      foreach ($this->fields as $field) {
        $sSel .= ( empty($sSel) ? '' : ', ' );
        if ($field instanceof base\InstructionBase) {
          // verifica se a subinstrucao tem apenas 1 field, pois um subselect não pode
          // ter mais de 1
          /*$subqueryCols = $field->Columns;
          if (count($subqueryCols) != 1)
            throw new SQLException('A subquery só pode ter apenas 1 coluna');*/
          // TODO: ver se essa validação é realmente necessária, se não é melhor deixar
          // essa validação pro proprio banco de dados lidar
          
          $sSel .= "($field) {$field->getAlias()}";
        /*} elseif ($field instanceof SQLTable) {
          $sSel .= $field->toAlias().'.*'; // TODO: pensar se isso aqui é realmente inteligente a se fazer*/
        } elseif ($field instanceof base\HasAlias) {
          $sSel .= "$field";
          
          // verifica se faz group by automatico
          if ($field instanceof base\HasFunction) {
            $func = strtoupper($field->getFunction());
            
            if ( isset(self::$aggregateFunctions[$func]) ) {
              $doGroupby = true;
              $doGroupby = $doGroupby && !$field->getOver(); // só conta no group by se não tiver over
            } else {
              $groupbyFields[] = $field;
            }
            
            if ($field->getOver()) {
              $sSel .= ' OVER ('.$field->getOver().')';
            }
          }
          
          $sSel .= " {$field->getAlias()}";
          
        } else {
          $sSel .= $field;
          
        }
      }
      $s .= $sSel;
      //$s .= implode(', ', $this->fields);
    }
    
    $sFrom = '';
    foreach ($this->entities as $entity) {
      $sFrom .= ( empty($sFrom) ? '' : ', ' );
      if ($entity instanceof ISelect) {
        $entity = "($entity)";
      }
      if ($entity instanceof base\HasAlias) {
        $sFrom .= "$entity {$entity->getAlias()} ";
      } else {
        $sFrom .= $entity;
      }
    }
    
    if ($sFrom) {
      $s .= ' FROM '. $sFrom;
    }
    
    if ($this->filter) {
      $s .= ' WHERE '. $this->filter;
    }
    
    if ($doGroupby) {
      
      $sGroup = '';
      foreach ($groupbyFields as $gfield) {
        $sGroup .= ( empty($sGroup) ? '' : ', ' );
        if ($gfield instanceof base\HasFunction) {
          $gfield->showFunctions(false);
          $sGroup .= $gfield;
          $gfield->showFunctions(true);
        } else {
          $sGroup .= $gfield;
        }
      }

      if ($sGroup) {
        $s .= ' GROUP BY '. $sGroup;
      }
    }
    
    $sOrder = '';
    foreach ($this->orderby as $order) {
      if ($order instanceof base\Ordenable) {
        $sOrder .= ( empty($sOrder) ? '' : ', ' );
        $sOrder .= "{$order->toAlias()} {$order->getOrder()}";
      }
    }
    
    if ($sOrder) {
      $s .= ' ORDER BY '. $sOrder;
    }
    
    if ($this->showFunctions && $this->functions) {
      foreach ($this->functions as $func) {
        $s = $func['name'] . '('.$s;
        
        if (!empty($func['params'])) {
          array_walk($func['params'], array(self,'parseValueWalk'));
          $s .= ', '.implode(', ', $func['params']);
        }
        $s .= ')';
      }
    }
    
    return $s;
  }
  
}
