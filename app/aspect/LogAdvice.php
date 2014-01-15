<?php

namespace App\aspect;

use Djck\aspect\Advice;

class LogAdvice extends Advice {
  
  function after($result) {
    $arquivo = TEMP_PATH . DS. 'log' . DS . get_class($this->Delegate).'.log';
    $conteudo = $this->Delegate->calledMethod.'() chamado as '.date('d/m/Y H:i:s').': '.$result;
    if (file_put_contents($arquivo, $conteudo, FILE_APPEND) === false) {
      throw new \Exception('Falha ao salvar log do arquivo "'.$arquivo.'"');
    }
    return parent::after($result);
  }
  
  function afterThrowing(\Exception $thrown) {
    if ($thrown instanceof \Exception) {
      // ignorar exceptions de log na tela e mandar um email ao webmaster avisando problema
      mail('webmaster@site.com', 'Erro ao fazer log: '.$thrown->getMessage());
    } else {
      // senão, lançar exception normalmente para classe pai lidar
      parent::afterThrowing($thrown);
    }
  }
  
}