<?php

namespace Djck\mailer;

Core::import('Smarty', '/plugin/smarty');

/**
 * Description of View
 *
 * @author
 */
class MailerTemplate extends \Smarty {
  
  // pagina que será exibida
  private $view = array();
  
  public function __construct($view) {
    // inicia o smarty normalmente
    parent::__construct();

    // define os padrões
    $this->debugging = false;
    $this->caching = false;
    $this->cache_lifetime = 5;

    $this->setTemplateDir(array(
        ROOT .DS. 'public' .DS . 'tmpl'.DS.'mailer',
        DJCK .DS. 'public' .DS . 'tmpl'.DS.'mailer',
    ));
    $this->setCompileDir(TEMP_PATH .DS.'smarty'.DS. 'templates_c');
    $this->setCacheDir(TEMP_PATH .DS.'smarty'.DS. 'cache');
    $this->setConfigDir(PLUGIN_PATH.DS.'smarty'.DS. 'config');
    $this->setPluginsDir(array(
        PLUGIN_PATH.DS.'smarty'.DS. 'plugins'
        // TODO: colocar uma pasta só para os novos plugins pro smarty (ou não, continuar
        // deixando eles na pasta plugins dentro da pasta plugins/smarty
    ));
    
    $this->view = array();
    $this->view[] = 'skin.tpl';

    // pasta padrao que o smarty vai buscar as paginas
    $this->view[] = $view;
  }
  
  public function render() {

    try {
      
      $return = '';
      
      // mostra o conteudo da pagina
      if (count($this->view) > 1)
        $return = $this->fetch('extends:'.implode('|', $this->view));
      else
        $return = $this->fetch($this->view[0]);
      
      return $return;

    } catch (SmartyException $e) {
      throw $e;
    }
  }
  
}