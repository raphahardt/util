<?php

Core::depends('Request');
Core::depends('Response');

class ControllerException extends CoreException {}

abstract class Controller {
  
  /**
   *
   * @var Request 
   */
  public $request;
  /**
   *
   * @var Response 
   */
  public $response;
  /**
   *
   * @var Router 
   */
  public $router;
  /**
   *
   * @var Session 
   */
  protected $session;
  
  protected $logged = null;
  protected $ip = null;
  protected $token = null;
  protected $user;
  protected $url;
  
  /**
   * Pasta onde estão os views do controller. Deixe vazio ou null para pasta raiz
   * Recomendado sempre definir uma pasta. Se definido, terminar sempre com barra (ex: home/)
   * @var string 
   */
  public $viewPath = null;
  
  public function __construct() {
    global $Session;
    
    if (isset($Session))
      $this->session = &$Session;
    else {
      Core::depends('Session');
      $this->session = new Session();
    }
   
    $this->request = new Request();
    $this->response = new Response();
    
    $this->ip = $this->request->clientIp();
    if (!isset($this->user))
      $this->user = $_SESSION[SESSION_USER_NAME];
    
    $this->logged = is_object($this->user) && $this->user->id > 0;
    $this->token = $_SESSION[SESSION_TOKEN_NAME];
    $this->url = $this->request->url;
  }
  
  public function beforeExecute() {
    return true;
  }
  
  public function afterExecute() {
    return;
  }
  
  public function execute($method, $params = array()) {
    switch (count($params)) {
      case 0:
        return $this->{$method}();
      case 1:
        return $this->{$method}($params[0]);
      case 2:
        return $this->{$method}($params[0], $params[1]);
      case 3:
        return $this->{$method}($params[0], $params[1], $params[2]);
      case 4:
        return $this->{$method}($params[0], $params[1], $params[2], $params[3]);
      case 5:
        return $this->{$method}($params[0], $params[1], $params[2], $params[3], $params[4]);
      case 6:
        return $this->{$method}($params[0], $params[1], $params[2], $params[3], $params[4], $params[5]);
      default:
        return call_user_func_array(array(&$this, $method), $params);
        break;
    }
  }
  
}