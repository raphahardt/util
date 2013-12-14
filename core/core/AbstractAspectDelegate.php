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
 *   function before($arguments) {
 *     $arguments[0] = 'interceptei o primeiro parametro';
 *     return $arguments;
 *   }
 * 
 * }
 * 
 * // registrando (no seu app)
 * $delegator = system\AspectDelegator::getInstance();
 * $delegator->register(new ObjetoAspecto, 'metodo', new TesteAdvice);
 * 
 * ?>
 * </code>
 * 
 */
abstract class AbstractAspectDelegate extends AbstractObject {
  
  /**
   * Wrapper para métodos de um AbstractAspect
   * 
   * @param string $name
   * @param array $arguments
   * @return mixed
   */
  public function __call($name, $arguments) {
    $delegator = AspectDelegator::getInstance();
    $advices = $delegator->getAdvices($this);
    
    if (isset($advices[$name])) {
      
      // para entender a logica do codigo abaixo,
      // ver: https://code.google.com/p/ajaxpect/wiki/UsageExample
      
      foreach ($advices[$name] as $advice) {
        // execute before (passa o array de argumentos e retorna para o metodo around
        // ser executado
        $arguments = $advice->before($arguments);
      }
      
      try {
        
        foreach ($advices[$name] as $advice) {
          // execute around
          $result = $advice->around($name, $arguments);
          // execute after
          $result = $advice->after($result);
        }
        
      } catch (\Exception $e) {
        // execute after throwing
        try {
          $result = $advice->afterThrowing($e);
        } catch (\Exception $e) {
          
          // (hack) executa o finally mesmo se o after throwing lançar outra exception
          $advice->afterFinally();
          throw $e;
        }
      }
      
      // executa o finally (não será executado se o afterThrowing() lançar uma exception
      // (ou lançar a mesma exception)
      $advice->afterFinally();
      
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