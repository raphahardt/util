<?php

namespace App\controller\home;

use Djck\Core;
use App\controller\AppController;
use App\view\AppView;

Core::uses('AppController', 'App');
Core::uses('AppView', 'App');

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