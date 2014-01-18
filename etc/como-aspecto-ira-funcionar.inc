<?php

// criando um aspecto ---
// 
// criando advice
// deve estar num arquivo separado, na pasta app/aspect
class LogAdvice extends \Djck\aspect\Advice {
  
  function after($result) {
    $arquivo = get_class($this->Delegate).'.log';
    $conteudo = $this->Delegate->calledMethod.'() chamado as '.date('d/m/Y H:i:s').': '.$result;
    if (file_put_contents($arquivo, $conteudo, FILE_APPEND) === false) {
      throw new LogAdviceException('Falha ao salvar log do arquivo "'.$arquivo.'"');
    }
    return parent::after($result);
  }
  
  function afterThrowing(\Exception $throwed) {
    if ($throwed instanceof LogAdviceException) {
      // ignorar exceptions de log na tela e mandar um email ao webmaster avisando problema
      mail('webmaster@site.com', 'Erro ao fazer log: '.$throwed->getMessage());
    } else {
      // senão, lançar exception normalmente para classe pai lidar
      parent::afterThrowing($throwed);
    }
  }
  
}

// classe normal que será delegate (ou seja, será envolvida por uma join point e se tornará um aspecto)
class TesteQualquer extends \Djck\system\AbstractAspectDelegate {
  
  protected function setup() {
    
    // registrando os advices para criar os aspectos
    $delegator = \Djck\system\AspectDelegator::getInstance();
    $delegator->register($this, '/[a-z]+/i', new LogAdvice);
    
  }
  
  // importante: métodos publicos devem ser PROTECTED, se vc quer que o aspecto funcione para ele
  protected function metodoQualquer($argumento) {
    return "Você fez uma ação, e passou por parametro isto: $argumento";
  }
  
  protected function outroMetodo() {
    return "Você fez uma ação por outro método";
  }
  
  protected function metodoCom1Numero() {
    return "Essa ação não irá aparecer no log";
  }
  
}

// exemplo de uso:
$teste = new TesteQualquer();
$teste->metodoQualquer('aaa');
$teste->outroMetodo();
$teste->metodoCom1Numero(); // join point não bate com /[a-z]+/

// arquivo TesteQualquer.log
<<<FILE
metodoQualquer() chamado as 00/00/0000 00:00:00: Você fez uma ação, e passou por parametro isto: aaa
outroMetodo() chamado as 00/00/0000 00:00:00: Você fez uma ação por outro método
FILE;
