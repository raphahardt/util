<?php

namespace Djck\system;

use Djck\Core;
use Djck\system\AbstractAspectDelegate;
use Djck\system\AbstractSingleton;

use Djck\aspect\Advice;

class AspectDelegator extends AbstractSingleton {
  
  /**
   * Mapeia cada método da classe com seu aspecto correspondente.
   * 
   * É automaticamente preenchido pelo AspectDelegator.
   * 
   * @var \Djck\aspect\Advice[] Array de advices
   */
  protected $advices = array();
  
  public function addAdvice(AbstractAspectDelegate $Delegate, $method, Advice $Advice) {
    $Advice->setDelegate($Delegate);
    $hash = get_class($Delegate);
    
    if (!isset($this->advices[$hash][$method])) {
      $this->advices[$hash][$method] = array();
    }
    // verifica se foi dado uma prioridade pro aspecto (menor->primeiro)
    if (isset($Advice->priority)) {
      $this->advices[$hash][$method][(int)$Advice->priority] = $Advice;
    } else {
      // se nao tiver prioridade, usa metodo fifo (fila)
      $this->advices[$hash][$method][] = $Advice;
    }
    // livra o objeto da memoria
    unset($Delegate);
  }
  
  public function refreshAdvicePriority(AbstractAspectDelegate $Delegate = null) {
    if (isset($Delegate)) {
      $hash = get_class($Delegate);
      foreach ($this->advices[$hash] as &$advices) {
        ksort($advices);
      }
      // livra o objeto da memoria
      unset($Delegate);
      
    } else {
      foreach ($this->advices as &$aspects) {
        foreach ($aspects as &$advices) {
          ksort($advices);
        }
      }
    }
  }
  
  public function getAdvices(AbstractAspectDelegate $Delegate = null) {
    if (isset($Delegate)) {
      $hash = get_class($Delegate);
      // livra o objeto da memoria
      unset($Delegate);
      
      return $this->advices[$hash];
    } else {
      $this->advices;
    }
  }
  
  public function destroy() {
    
  }

  public function reinit() {
    
  }
  
  public function register(AbstractAspectDelegate $Delegate, $pattern, Advice $Advice) {
    
    $class = new \ReflectionClass($Delegate);
    $class_methods = $class->getMethods(\ReflectionMethod::IS_PROTECTED);
    
    if (count($class_methods) > 0) {
      
      // se for pattern, checar
      if (is_string($pattern)) {
        if (strpos($pattern, '/') === 0) { // começa com '/'
          $is_pattern = true;
        } else {
          $is_pattern = false;
        }
      }
      
      // vai em cada método para mapea-lo
      foreach ($class_methods as $method) {
        $method_name = $method->getName();
        if (is_callable($pattern) && $pattern($method_name) == true) {
          $this->addAdvice($Delegate, $method_name, $Advice);
        } 
        elseif ($is_pattern && preg_match($pattern, $method_name)) {
          $this->addAdvice($Delegate, $method_name, $Advice);
        } 
        elseif ($method_name === $pattern) {
          $this->addAdvice($Delegate, $method_name, $Advice);
        }
      }
      
      // reordena advices dentro do delegate
      $this->refreshAdvicePriority($Delegate);
      
      // livra o objeto da memoria
      unset($Delegate);
      
    } else {
      throw new \Djck\CoreException('A classe \''.$class->getName().'\' não possui métodos');
    }
    
  }

}