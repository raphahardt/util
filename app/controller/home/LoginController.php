<?php

Core::uses('AppController', 'controller');
Core::uses('AppView', 'view');

/**
 * Description of HomeController
 *
 * @author usuario
 */
class LoginController extends AppController {
  
  function index(AppView $view) {
    //$view = new AppView('login/index.tpl');
    $view->render();
  }
  
  function auth() {
    $user = new stdClass();
    $user->id = 1;
    
    $_SESSION[SESSION_USER_NAME] = $user;
    $_SESSION['logged'] = true;
    
    // segurança
    $this->session->interrupt();
    
    $this->response->redirect('');
  }
  
  function logout() {
    session_destroy();
    //$this->session->destroy();
    $this->response->redirect('');
  }
  
}