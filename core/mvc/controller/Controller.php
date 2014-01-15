<?php

namespace Djck\mvc;

use Djck\Core;
use Djck\system\AbstractDelegate;

use Djck\network\Request;
use Djck\network\Response;

Core::registerPackage('Djck\mvc:controller\exceptions');

abstract class Controller extends AbstractDelegate {
  
  /**
   * Objeto 
   * @var \Djck\network\Request 
   */
  public $Request;
  /**
   *
   * @var \Djck\network\Response 
   */
  public $Response;
  /**
   *
   * @var \Djck\session\Session 
   */
  protected $session;
  
  protected $logged = null;
  protected $ip = null;
  protected $token = null;
  protected $user;
  
  /**
   * Pasta onde estão os views do controller. Deixe vazio ou null para pasta raiz
   * Recomendado sempre definir uma pasta. Se definido, terminar sempre com barra (ex: home/)
   * @var string 
   */
  public $viewPath = null;
  
  public function __construct() {
    parent::__construct();
    
    global $Session;
    
    if (isset($Session))
      $this->session = &$Session;
    else {
      //Core::depends('session\Session');
      $this->session = new \Session();
    }
   
    $this->Request = new Request();
    $this->Response = new Response();
    
    $this->ip = $this->Request->clientIp();
    if (!isset($this->user))
      $this->user = $_SESSION[SESSION_USER_NAME];
    
    $this->logged = is_object($this->user) && $this->user->id > 0;
    $this->token = $_SESSION[SESSION_TOKEN_NAME];
  }
  
}