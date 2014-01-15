<?php

namespace Djck\aspect;

use Djck\system\AbstractObject;

abstract class Advice extends AbstractObject {
  
  public $priority = null;
  protected $Delegate = null;
  
  public function setDelegate(\Djck\system\AbstractDelegate $delegate) {
    $this->Delegate = $delegate;
  }
  
  /**
   * Advice que é executado antes do método.
   * 
   * @param array $arguments Argumentos que foram passados para o método original
   * @return array Argumentos que serão passados para o método original
   */
  public function before($arguments) {
    return $arguments;
  }
  
  /**
   * Advice que é executado "em volta" do método.
   * 
   * Pode interromper o método original ou pode chamar normalmente o mesmo método,
   * usando o objeto ($Delegate) e chamando callMethod().
   * 
   * Deve retornar o que o método original retorna, lançar uma exception ou retornar
   * qualquer outra coisa.
   * 
   * @todo conseguir, neste escopo, chamar métodos privados do $Delegate
   * 
   * @param string $method
   * @param array $arguments
   * @return mixed
   */
  public function around($method, $arguments) {
    return $this->Delegate->callMethod($method, $arguments);
  }
  
  /**
   * Advice que é executado depois do retorno do método.
   * 
   * Recebe como parametro o retorno do método original. Este não é executado
   * se o método original (ou o around()) lançar uma exception.
   * 
   * @param mixed $result O que foi retornado do método original
   * @return mixed Novo retorno para o método original
   */
  public function after($result) {
    return $result;
  }
  
  /**
   * Advice que é executado depois do retorno caso seja lançado uma exception.
   * 
   * Recebe como parametro a exception lançada. Este método pode ignorar a exception
   * para continuar com o fluxo natural, ou re-lançar a mesma exception.
   * 
   * @param \Exception $thrown
   * @throws \Exception
   */
  public function afterThrowing(\Exception $thrown) {
    throw $thrown;
  }
  
  /**
   * Advice que é executado depois do método original, independente de exception ou não.
   * 
   * A partir daqui ele já não tem mais ligação com o fluxo do método, somente
   * tem acesso ao objeto original pelo $Delegate.
   */
  public function afterFinally() {
    ;
  }
  
}