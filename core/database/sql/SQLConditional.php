<?php

class SQLConditional extends SQLFieldBase implements ISQLAliasable,ISQLOrdenable,ISQLFunctionable,ISQLExpressions {

  private $case = null; // se aqui for um SQLField, os conditions devem ser valores, como um select case, se null, devem
  // ser expressoes
  private $when_conditions = array(); // pode ser um SQLfield ou um SQLExpressionBase (criteria ou expression)
  private $when_statements = array(); // pode ser um SQLField ou um SQLExpressionBase (criteria ou expression)
  
  protected function hash() {
    // TODO: mudar pra alias
    return SQLBase::key(__CLASS__.self::$_obj_id++);
  }
  
  public function __construct(SQLBase $case, $condition, $result = null) {
    // depois chama o construct do parent, que cria o hash (entre outros)
    parent::__construct();
    
    $args = func_get_args();
    $this->callMethod('build', $args);
  }
  
  /**
   * Construtor do objeto condicional. Ele cria uma expressão SQL básica do tipo CASE...WHEN.
   * O primeiro argumento é opcional e indica o campo que será testado pelos when's, como se fosse um switch () {}.
   * Se ele for preenchido ou não, pelo menos 2 condicionais são obrigatórias.
   * As condicionais dos when's devem ser em pares de argumentos, ex: ($cond1, $res1, $cond2, $res2, ...);
   * Para indicar um "else", basta passar null para a condicional, ex: (..., $cond3, $res3, null, $res4);
   * 
   * Exemplos:
   * 
   * $c = new SQLConditional($tabela['campo1'], 'A', $tabela['campoA'], 'B', 13, null, $tabela['campoELSE']);
   * // CASE tabela.campo1 
   *      WHEN 'A' THEN tabela.campoA
   *      WHEN 'B' THEN 13
   *      ELSE tabela.campoELSE
   *    END
   * 
   * $c = new SQLConditional($expressao1, $result1, null, $result2);
   * // CASE
   *      WHEN (...) THEN (...)
   *      ELSE (...)
   *    END
   * 
   * @param SQLEntityBase $case
   * @param SQLBase $condition
   * @param SQLBase $statement
   */
  public function build(SQLBase $case, $condition, $result = null) {
    $args = func_get_args();
    
    // se o primeiro argumento for preenchido (sendo ele umfield ou tendo numero impar de argumentos),
    // tratar ele como case e retirar ele da lista de argumentos das condições
    $case = null; // força null no case
    if ($args[0] instanceof SQLField && count($args) % 2 != 0) {
      $case = $args[0];
      array_shift($args);
    }
    
    // se não for pares de condição/resultado, dar erro
    $this->destroy();
    $this->callMethod('add', $args);
    $this->case = $case;
    
  }
  
  public function destroy() {
    // apaga os objetos antes de zerar a propriedade
    foreach ($this->when_conditions as $i => $e) {
      unset($this->when_conditions[$i], $this->when_statements[$i], $e); // apaga os objetos
    }
    $this->when_conditions = $this->when_statements = array();
    $this->case = null;
  }
  
  public function add($condition, $result) {
    $args = func_get_args();
    
    // se não for pares de condição/resultado, dar erro
    if (count($args) % 2 != 0)
      throw new SQLException('A condicional requer "pares" de condição/resultado');
    
    $iscond = true;
    foreach ($args as $a) {
      if (is_object($a)) {
        if (!($a instanceof ISQLExpressions)) 
          throw new SQLException('O método '.__METHOD__.' só aceita ISQLExpressions'.
                                ' ou tipos comuns (string, int...) como parametro');

        // se a expressao que estiver adicionando for o mesmo que o objeto instanciado,
        // não adicionar, pois irá gerar recursão e entrar em loop infinito
        if ($a instanceof SQLConditional && $a->getHash() === $this->getHash()) {
          throw new SQLException('Você não pode colocar como subcondição a própria '.
                                  'instancia de condição');
        }

        // adicionando um objeto do tipo ISQLExpression
        if ($iscond) {
          $this->when_conditions[ $a->getHash() ] = $a;
        } else {
          $this->when_statements[ $a->getHash() ] = $a;
        }
      } else {

        // adicionando uma variavel comum (string, int...) no array
        if ($iscond) {
          $this->when_conditions[] = $a;
        } else {
          $this->when_statements[] = $a;
        }
      }
      $iscond = !$iscond;
    }
  }
  
  public function addCondition($condition, $result) {
    $this->callMethod('add', func_get_args());
  }
  
  public function remove($hash) {
    if ($hash instanceof SQLBase)
      $hash = $hash->getHash();
    
    unset($this->when_conditions[ $hash ], $this->when_statements[ $hash ]);
  }
  
  public function removeCondition($hash) {
    $this->remove($hash);
  }
  
  public function get() {
    return array(
        'case' => $this->case,
        'conditions' => $this->when_conditions,
        'results' => $this->when_statements
    );
  }
  
  public function getConditions() {
    return array(
        'conditions' => $this->when_conditions,
        'results' => $this->when_statements
    );
  }
  
  public function setCase(SQLBase $case) {
    $this->case = $case;
  }
  
  public function unsetCase() {
    $this->case = null;
  }
  
  public function getCase() {
    return $this->case;
  }
  
  public function __toString() {
    
    $s = 'CASE ';
    if ($this->case) {
      $s .= $this->case . ' ';
    }
    
    foreach ($this->when_conditions as $i => $cond) {
      $stat = $this->when_statements[$i];
      // condition
      if ($cond) {
        $s .= 'WHEN ';
        if ($cond instanceof SQLBase) {
          $s .= (string)$cond;
        } else {
          $s .= SQLBase::parseValue($cond);
        }
        $s .= ' THEN ';
      } else {
        $s .= 'ELSE ';
      }
      // statement
      if ($stat instanceof SQLBase) {
        $s .= (string)$stat;
      } else {
        $s .= SQLBase::parseValue($stat);
      }
      $s .= ' ';
    }
    $s .= 'END ';
    
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
