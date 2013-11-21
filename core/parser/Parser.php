<?php

// usa Neon (do framework Lette) como parser para os arquivos de configuração
Core::import('Neon', 'plugin/neon');

class Parser extends Neon {
  // TODO: fazer ele fazer cache da leituras e ler direto de um .php já parsado
  
  /**
   * Extensão dos arquivos de configuração
   * @var string 
   */
  static public $extension = '.neon';
  
}
