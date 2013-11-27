<?php

namespace Djck\mvc;

use Djck\Core;

Core::import('Smarty', '/plugin/smarty');

/**
 * Description of View
 *
 * @author Rapha e Dani
 */
class View extends \Smarty {
  
  // pagina que será exibida
  private $view = array();
  private $breadcrumbs = array();
  private $icons = array();
  private $title = '';
  private $vars = array();
  private $js_vars = array();
  
  protected $site_var = array();
  //protected $view_var = array(); // não usado, a variavel view é criada na hora do render
  
  public function __construct($view, $ajax = false) {
    // inicia o smarty normalmente
    parent::__construct();

    global $Q; // FIXME

    // define os padrões
    $this->debugging = false;
    $this->caching = false;
    $this->cache_lifetime = 5;
    
    // muda os delimitadores do smarty para não conflitarem com o angularjs
    // (pros .tpl funcionarem no netbeans, deve ser alterado nas config do projeto)
    $this->left_delimiter = '#{';
    $this->right_delimiter = '}';
    
    // desabilita a checagem de mudanças no template quando estiver em produção (aumenta performance)
    // http://www.smarty.net/docs/en/variable.compile.check.tpl
    $this->compile_check = _DEV;
    
    $this->setTemplateDir(array(
        APP_PATH .DS. 'view',
        DJCK . DS. PUBLIC_DIR .DS. 'tmpl'.DS.'components',
        ROOT . DS. PUBLIC_DIR .DS. 'tmpl'.DS.'components',
    ));
    $this->setCompileDir(TEMP_PATH .DS.'smarty'.DS. 'templates_c');
    $this->setCacheDir(TEMP_PATH .DS.'smarty'.DS. 'cache');
    $this->setConfigDir(PLUGIN_PATH.DS.'smarty'.DS. 'config');
    $this->setPluginsDir(array(
        PLUGIN_PATH.DS.'smarty'.DS. 'plugins'
        // TODO: colocar uma pasta só para os novos plugins pro smarty (ou não, continuar
        // deixando eles na pasta plugins dentro da pasta plugins/smarty)
    ));
    
    // definicoes principais
    $this->assign('site', ($this->site_var = array(
        'title' => SITE_TITLE,
        'subtitle' => SITE_SUBTITLE,
        'copyright' => SITE_COPYRIGHT,
        'description' => SITE_DESCRIPTION,
        'keywords' => SITE_KEYWORDS,
        'owner' => SITE_OWNER,
        'URL' => SITE_URL,
        'fullURL' => SITE_FULL_URL,
		'currentURL' => $Q,
        'domain' => SITE_DOMAIN,
        
        'charset' => SITE_CHARSET,
        'lang' => LANG_STR,
        'lang_alt' => LANG_STR_ALT,
        
    )));
    
    // favicon
    $this->addIcon(SITE_URL.'/favicon.ico', 'icon');
    
    // breadcrumb inicial
    $this->addBreadcrumb('Pagina inicial', '');
    
    // variaveis relativas a cookie
    $this->addJSVar('C', array(
        'd' => COOKIE_DOMAIN, // domain
        'p' => COOKIE_PATH // path
    ));
    
    // token
    if (defined('TOKEN')) {
      $this->addJSVar('T', TOKEN);
      $this->setVar('token', TOKEN);
    }
    
    $this->view = array();
    $this->view[] = $ajax ? 'skin_ajax.tpl' : 'skin.tpl';
    $this->view[] = 'site.tpl';
    $this->view[] = 'template.tpl';

    // pasta padrao que o smarty vai buscar as paginas
	//$this->view[] = str_replace('/', DS, $view);
    $this->view[] = $view;
  }
  
  protected function beforeRender() {
    return true;
  }
  
  protected function afterRender() {
    return true;
  }
  
  public function render() {

    try {
      
      $before = $this->beforeRender();
      
      if ($before === false) {
        return;
      }
      
      // define variaveis
      $vars = (array)$this->vars;
      if ($this->title) $vars['title'] = $this->title;
      
      if (!empty($this->js_vars) && is_array($this->js_vars)) { // vars javascript
        $vars['js_vars'] = $this->js_vars;
      }
      if (is_array($this->breadcrumbs) && count($this->breadcrumbs) > 1) {
        $vars['breadcrumb'] = $this->breadcrumbs;
      }
      
      $vars['icons'] = $this->icons;
      
      // vars
      $this->assign('view', $vars);
      
      // mostra pagina compilada
      $this->loadFilter('output', 'trimwhitespace');
      
      // suporte para i18n (multi lingual)
      $views = $this->view;
      foreach ($views as &$view) {
        $view = $this->_normalizeI18nPath($view, LANG);
      }

      // mostra o conteudo da pagina
      if (count($views) > 1)
        $this->display('extends:'.implode('|', $views));
      else
        $this->display($views[0]);
      
    } catch (\SmartyException $e) {
      throw $e;
    }
  }
  
  /**
   * Procura o arquivo na lingua selecionada na pasta dos views. 
   * Se não encontrar na lingua selecionada, tenta na lingua padrão (definido no cfg/Language)
   * Se não encontrar na lingua padrão, retorna o arquivo da raiz
   * Se a pasta da lingua (padrão e/ou selecionada), faz o mesmo caminho acima, e se ainda
   * assim não encontrar, retorna da raiz
   * @param type $file
   * @param type $lang
   * @return type
   */
  protected function _normalizeI18nPath($file, $lang = LANG_DEFAULT) {
    $lang_atual = $lang;
    $lang_default = LANG_DEFAULT;
    $root = $this->getTemplateDir(0);
    
    // se não existir a pasta da lingua padrão, ignorar a procura de suporte a
    // i18n (aumentar performance)
    if (!is_dir($root.DS.$lang_default)) {
      return $file;
    }
    
    // se não existir a pasta da lingua atual, tentar buscar pela lingua padrao
    if (!is_dir($root.DS.$lang_atual)) {
      return $this->_normalizeI18nPath($file, $lang_default);
    }
    
    // se não existir o arquivo naquela lingua, buscar pela lingua padrão, ou automaticamente
    // vai cair na raiz pelo primeiro if
    if (!is_file($root.DS.$lang_atual.DS.$file)) {
      if ($lang_atual !== $lang_default) {
        // se a lingua atual for diferente da default, tentar pela default
        return $this->_normalizeI18nPath($file, $lang_default);
      } else {
        // não tem nem na default, então retorna na raiz mesmo
        return $file;
      }
    }
    
    // se chegou até aqui, achou o arquivo na pasta da lingua do usuario
    return $lang_atual.DS.$file;
  }
  
  protected function addBreadcrumb($title, $url) {
    $this->breadcrumbs[] = array(
        'title' => $title,
        'url' => $url,
    );
  }
  
  protected function addIcon($file, $type, $sizes = null) {
    $this->icons[] = array(
        'file' => $file,
        'sizes' => $sizes, 
        'type' => $type,
    );
  }
  
  /**
   * Define uma variavel global para ser usada no Javascript da página
   * @param string $var Nome da variável
   * @param mixed $value Valor da variável, já formatada no padrão JS
   * @param boolean $raw Se TRUE, irá imprimir o valor "como ele está", sem formatação
   */
  public function addJSVar($var, $value, $raw = false) {
    if (is_string($value) && !$raw) {
      $value = "'$value'";
    } elseif (is_array($value)) {
      $value = json($value);
    }
    $this->js_vars[] = array(
        'name' => $var,
        'value' => $value,
    );
  }

  /**
   * Define uma variavel que vai responder em $view.[nomedavariavel]
   * @param type $title
   * @param type $val
   */
  public function setVar($title, $val) {
    $this->vars[$title] = $val;
  }
  
  public function getVar($title) {
    return $this->vars[$title];
  }
  
  /**
   * Define o template a ser renderizado. O construtor do view já exige que um view seja
   * definido, e ele é previamente definido pelo Router como pastapadraodocontroller/index.tpl
   * Esse método serve para ter a possibilidade de mudar o view dentro do controller.
   * Sempre é alterado o último view da "fila" de herança
   * @param type $template
   */
  public function setView($template) {
    array_pop($this->view); // deleta o ultimo view definido, pois o novo vai substituir
    $this->view[] = $template;
  }
  
  public function getView() {
    return end($this->view);
  }
  
  /**
   * Adiciona um view como filho na "fila" de herança de templates.
   * A herança padrão é: skin.tpl -> template.tpl -> controller.tpl
   * Com esse método, é possivel acrescentar mais um nível de herança na estrutura de views
   * @param type $template
   */
  public function addChildView($template) {
    $this->view[] = $template;
  }
  
}