<?php

class SQLISelect extends SQLInstructionBase implements ISQLAliasable,ISQLOrdenable,ISQLFunctionable,ISQLExpressions,ISQLSelectables, SQLArrayAccess {
  
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
  
  protected function hash() {
    return SQLBase::key($this->getAlias());
  }
  
  public function __construct($fields, $from, $where = null, $order = null, $defs = null) {
    $this->build($fields, $from, $where, $order, $defs);
    parent::__construct();
  }

  public function build($fields, $from, $where = null, $order = null, $defs = null) {
    if (!is_array($fields)) $fields = array($fields);
    if (!is_array($from)) $from = array($from);
    if (!is_array($order)) $order = array($order);
    
    $this->fields = $fields;
    $this->entities = $from;
    $this->filter = $where;
    $this->orderby = $order;
  }
  
  public function destroy() {
    $this->fields = array();
    $this->entities = array();
    $this->filter = null;
    $this->orderby = array();
    
    $this->orderDesc = false;
    $this->functions = array();
  }
  
  public function add($expr) {
    $args = func_get_args();
    
    $fs = array();
    
    if (count($args) > 1) {
      $fs = $args;
    } elseif (count($args) == 1) {
      $expr = $args[0];
      if (is_array($expr)) {
        $fs = $expr;
      } else {
        $fs[] = $expr;
      }
    }
    
    foreach ($fs as $f) {
      if (!($f instanceof SQLField)) 
        throw new SQLException('O método '.__METHOD__.' só aceita SQLField como parametro');
      
      $this->fields[ $f->getHash() ] = $f;
    }
    
  }
  
  public function addFields() {
    $args = func_get_args();
    $this->callMethod('add', $args);
  }
  
  public function remove($hash) {
    if ($hash instanceof SQLBase)
      $hash = $hash->getHash();
    else
      $hash = SQLBase::key($hash);
    
    unset($this->fields[ $hash ]);
  }
  
  public function removeField($name) {
    $this->remove($name);
  }
  
  public function get() {
    return $this->getTable();
  }
  
  public function getTable() {
    return $this->entity;
  }
  
  public function getFields() {
    return $this->fields;
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
      return SQLBase::getUniqueName('from');
    }
  }
  
  public function getFunction() {
    $lfunc = end($this->functions);
    return $lfunc['name'];
  }
  
  public function setFunction($func, $params = null) {
    $args = func_get_args();
    array_shift($args);
    
    $functionParams = array();
    
    if (count($args) > 1) {
      $functionParams = $args;
    } elseif (count($args) == 1) {
      if (is_array($params)) {
        $functionParams = $params;
      } else {
        $functionParams[] = $params;
      }
    }
    
    $this->functions[SQLBase::key($func)] = array( 'name' => $func, 'params' => $functionParams );
  }
  
  public function unsetFunction($func) {
    unset($this->functions[SQLBase::key($func)]);
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
  
  // --------------------- INICIO DOS METODOS DE ACESSO POR ARRAY ----
  
  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para adicionar um valor ao objeto (ex: $obj[] = 'valor').
   */
  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      //$this->data[] = $value;
      throw new SQLException('Não é possível definir valores para um campo sem nome');
      // TODO: deixar ele acrescentar valores, desde que os fields tenham sido definidos
      // e que o valor a ser adicionado não ultrapasse o numero de campos definidos
    } else {
      $field = $this->fields[ SQLBase::key($offset) ];
      if (!($field instanceof SQLField))
        throw new SQLException('Campo '.$offset.' não existe');
      
      $field->setValue($value);
    }
  }
  
  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para verificar se o elemento existe (ex: isset($obj[1]) ).
   */
  public function offsetExists($offset) {
    return isset($this->fields[SQLBase::key($offset)]);
  }

  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para deletar um elemento do objeto (ex: unset($obj[1]) ).
   */
  public function offsetUnset($offset) {
    $this->removeField($offset);
  }

  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para retornar o valor de um elemento existente (ex: $var = $obj[1] ).
   */
  public function offsetGet($offset) {
    if (is_numeric($offset)) {
      // TODO: procurar uma função ou metodo mais eficiente de busca (talvez busca binaria)
      $counter = 0;
      foreach ($this->fields as $val) {
        if ($counter == $offset) {
          return $val;
        }
        ++$counter;
      }
      return null;
    } else {
      return $this->fields[SQLBase::key($offset)];
      //return isset($this->fields[strtolower($offset)]) ? $this->fields[strtolower($offset)] : null;
    }
  }
  
  /**
   * NÃO MUDAR!<br>
   * Método de acesso como array. Serve para contar os elementos do array (ex: count($var) )
   */
  public function count() {
    return count($this->fields);
  }
  // --------------------- FIM DOS METODOS DE ACESSO POR ARRAY ----
  
  function __get($name) {
    switch ($name) {
      case 'Columns':
      case 'Fields':
        return $this->fields;
      case 'Entity':
      case 'Table':
        return $this->entity;
      case 'TableFields':
      case 'EntityFields':
        $table = $this->entity;
        if (!($table instanceof SQLEntityBase))
          throw new SQLException('Tabela ou Join não definido');
        return $table->Fields;
      default:
        throw new SQLException('Propriedade '.$name.' não existe');
    }
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
        if ($field instanceof SQLInstructionBase) {
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
        } elseif ($field instanceof ISQLAliasable) {
          $sSel .= "$field";
          
          // verifica se faz group by automatico
          if ($field instanceof ISQLFunctionable) {
            $func = strtoupper($field->getFunction());
            $aggregFunc = $this->_getAggregateFunctions();
            
            if ( in_array($func, $aggregFunc) ) {
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
      if ($entity instanceof SQLISelect) {
        $entity = "($entity)";
      }
      if ($entity instanceof ISQLAliasable) {
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
        if ($gfield instanceof ISQLFunctionable) {
          $gfield->showFunctions(false);
          $sGroup .= $gfield;
          $gfield->showFunctions(true);
        } else
          $sGroup .= $gfield;
      }

      if ($sGroup) {
        $s .= ' GROUP BY '. $sGroup;
      }
    }
    
    $sOrder = '';
    foreach ($this->orderby as $order) {
      if ($order instanceof SQLField || $order instanceof SQLExpression) {
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
          array_walk($func['params'], 'SQLBase::parseValueIterator');
          $s .= ', '.implode(', ', $func['params']);
        }
        $s .= ')';
      }
    }
    
    return $s;
  }  
}