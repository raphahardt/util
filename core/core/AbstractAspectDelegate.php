<?php

namespace Djck\system;

use Djck\Core;
use Djck\system\AbstractObject;

use Djck\aspect\Advice;

Core::uses('Aspect', 'Djck\aspect');

/**
 * Classe especial que possibilita receber Advices e ser tratada como um aspecto.
 * 
 * Os advices são registrados nestas classes pelo AspectDelegator.
 * 
 * Exemplo de uso
 * --------------
 * <code>
 * <?php
 * class ObjetoAspecto extends system\AbstractAspectDelegate {
 *   
 *   // importante! todos os métodos desta classe devem ter visibilidade protected ou private
 *   // métodos publicos serão chamados sem passar pelo aspecto
 *   protected function metodo($param) {
 *     return "O parametro foi $param";
 *   }
 * 
 * }
 * 
 * // advice para o objeto
 * class TesteAdvice extends aspect\Advice {
 *   
 *   function beforeMetodo($arguments) {
 *     $arguments[0] = 'interceptei o primeiro parametro';
 *     return $arguments;
 *   }
 * 
 * }
 * 
 * // registrando (no seu app)
 * system\AspectDelegator(new ObjetoAspecto, 'metodo', new TesteAdvice);
 * 
 * ?>
 * </code>
 * 
 */
abstract class AbstractAspectDelegate extends AbstractObject {
  
  /**
   *
   * @var \Djck\aspect\Advice[]
   */
  protected $aspects = array();
  
  /**
   * Mapeia cada método da classe com seu aspecto correspondente.
   * 
   * É automaticamente preenchido pelo AspectDelegator.
   * 
   * @var array Array de advices
   */
  protected $advices = array();
  
  public function addAdvice($method, Advice $aspect, $alias = null) {
    if (!isset($this->advices[$method])) {
      $this->advices[$method] = array();
    }
    if (isset($alias)) {
      // se tiver um alias, chamar o metodo com alias
      $add = array($aspect, $alias);
    } else {
      $add = $aspect;
    }
    // verifica se foi dado uma prioridade pro aspecto (menor->primeiro)
    if (isset($aspect->priority)) {
      $this->advices[$method][(int)$aspect->priority] = $add;
    } else {
      // se nao tiver prioridade, usa metodo fifo (fila)
      $this->advices[$method][] = $add;
    }
  }
  
  public function refreshAdvicePriority() {
    foreach ($this->advices as &$advices) {
      ksort($advices);
    }
  }
  
  /**
   * Wrapper para métodos de um AbstractAspect
   * 
   * @param type $name
   * @param type $arguments
   * @return type
   */
  public function __call($name, $arguments) {
    if (isset($this->advices[$name])) {
      
      // para entender a logica do codigo abaixo,
      // ver: https://code.google.com/p/ajaxpect/wiki/UsageExample
      
      // normaliza o nome do metodo e referencia o advice
      $advices = array();
      foreach ($this->advices[$name] as $advice) {
        if (is_array($advice)) {
          $action = ucfirst($advice[1]);
          $advice = $advice[0];
        } else {
          $action = ucfirst($name);
        }
        $advices[ $action ] = $advice;
      }
      
      foreach ($advices as $action => $advice) {
        // execute before (passa o array de argumentos e retorna para o metodo around
        // ser executado
        if (method_exists($advice, "before$action")) {
          $arguments = $advice->{"before$action"}($arguments);
        }
      
        // execute around
        // TODO fazer around
        
      }
      
      $result = $this->callMethod($name, $arguments);
      
      foreach ($advices as $action => $advice) {
        // execute after
        if (method_exists($advice, "after$action")) {
          $result = $advice->{"after$action"}($result);
        }
      }
      
      return $result;
      
    } else {
      // chama o metodo normal, ele nao possui join point associado a ele
      return $this->callMethod($name, $arguments);
    }
  }
  
  public function __callStatic($name, $arguments) {
    ;
  }
  
}