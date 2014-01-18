<?php

namespace Djck\system;

use Djck\Core;
use Djck\system\AbstractDelegate;
use Djck\system\AbstractSingleton;

use Djck\aspect\Advice;

class AspectDelegator extends AbstractSingleton {
  
  /**
   * Mapeia cada método da classe com seu aspecto correspondente.
   * 
   * É automaticamente preenchido pelo AspectDelegator.
   * 
   * @var Advice[] Array de advices
   */
  protected $advices = array();
  
  /**
   * Interliga um método da classe delegativa diretamente com um advice específico.
   * 
   * Só usado internamente. Para criar um aspecto, utilize register().
   * 
   * @access protected
   * @see AspectDelegator::register()
   * @param AbstractDelegate $Delegate Classe delegativa
   * @param string $method Nome do método. Não é permitido REGEX e nem callable
   * @param Advice $Advice Advice a ser interligado
   */
  protected function _addAdvice(AbstractDelegate $Delegate, $method, Advice $Advice) {
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
  
  /**
   * Atualiza a ordenação dos advices naquele aspecto.
   * 
   * Se for passado uma classe delegativa, irá ordenar somente os aspectos dela.
   * Só usado internamente, automaticamente chamado a cada register()
   * 
   * @access protected
   * @param AbstractDelegate $Delegate
   */
  protected function _refreshAdvicePriority(AbstractDelegate $Delegate = null) {
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
  
  /**
   * Retorna todos os advices.
   * 
   * Se passado uma classe delegativa, retorna os advices dela.
   * 
   * @param AbstractDelegate $Delegate
   * @return Advice[]
   */
  public function getAdvices(AbstractDelegate $Delegate = null) {
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
  
  /**
   * Cria um aspecto.
   * 
   * Recebe uma classe delegativa, o nome do método a ser interligado (pointcut) e
   * o advice a ser usado no aspecto.
   * O nome do método pode ser uma string com seu nome puro, uma REGEX que irá cobrir
   * 0 ou mais métodos ou uma callable, que irá receber como parametro o nome do método
   * da delegativa e deve retornar TRUE se encontrada, caso contrario FALSE.
   * 
   * @param AbstractDelegate $Delegate Classe delegativa
   * @param string|callable $pattern Nome, regex ou callable de um método
   * @param Advice $Advice Advice a ser interligado
   * @throws \Djck\CoreException
   */
  public function register(AbstractDelegate $Delegate, $pattern, Advice $Advice) {
    
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
        // nomes de metodos reservados, não usar advices para eles
        switch ($method_name) {
          case 'setup':
          case 'callMethod':
          case 'callStaticMethod':
          case 'getInstance':
            continue;
        }
        
        if (is_callable($pattern) && $pattern($method_name) == true) {
          // exemplo:
          // $delegator->register(new Obj, function ($metodo) { return $metodo == 'meu_metodo' ? true : false; }, new ObjAdvice)
          $this->_addAdvice($Delegate, $method_name, $Advice);
        } 
        elseif ($is_pattern && preg_match($pattern, $method_name)) {
          // exemplo:
          // $delegator->register(new Obj, '/meu_metodo_[a-z]+/', new ObjAdvice)
          $this->_addAdvice($Delegate, $method_name, $Advice);
        } 
        elseif ($method_name === $pattern) {
          // exemplo:
          // $delegator->register(new Obj, 'meu_metodo_x', new ObjAdvice)
          $this->_addAdvice($Delegate, $method_name, $Advice);
        }
      }
      
      // reordena advices dentro do delegate
      $this->_refreshAdvicePriority($Delegate);
      
      // livra o objeto da memoria
      unset($Delegate);
      
    } else {
      throw new \Djck\CoreException('A classe \''.$class->getName().'\' não possui métodos');
    }
    
  }

}