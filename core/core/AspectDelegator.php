<?php

namespace Djck\system;

use Djck\Core;
use Djck\system\AbstractAspectDelegate;
use Djck\system\AbstractSingleton;

use Djck\aspect\Advice;

class AspectDelegator extends AbstractSingleton {
  
  /**
   * Guarda todos os métodos que serão delegados para outros métodos de outras classes
   * 
   * Use o register() para declarar um aspecto
   * 
   * @var mixed 
   */
  static protected $delegations = array();
  
  public function destroy() {
    
  }

  public function reinit() {
    
  }
  
  static public function register(AbstractAspectDelegate $delegate, $pattern, Advice $aspect, $alias = null) {
    
    $class = new \ReflectionClass($delegate);
    $class_methods = $class->getMethods();
    
    if (count($class_methods) > 0) {
      
      // se for pattern, checar
      if (strpos($pattern, '/') === 0) { // começa com '/'
        $is_pattern = true;
        if (empty($alias)) {
          throw new \Djck\CoreException('O aspecto \''.$pattern.'\' requer um alias');
        }
      } else {
        $is_pattern = false;
      }
      
      // vai em cada método para mapea-lo
      foreach ($class_methods as $method) {
        $method_name = $method->getName();
        if ($is_pattern && preg_match($pattern, $method_name)) {
          $delegate->addAdvice($method_name, $aspect, $alias);
        } 
        elseif ($method_name === $pattern) {
          $delegate->addAdvice($method_name, $aspect);
        }
      }
      
      // reordena advices dentro do delegate
      $delegate->refreshAdvicePriority();
      
    } else {
      throw new \Djck\CoreException('Nenhum método pode ser mapeado pelo aspecto \''.$pattern.'\'');
    }
    
  }

}