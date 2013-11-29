<?php

namespace App\controller\home;

use Djck\Core;
use App\controller\AppController;
use App\view\AppView;

Core::uses('AppController', 'App\controller');
Core::uses('AppView', 'App\view');

/**
 * Description of HomeController
 *
 * @author usuario
 */
class LoginController extends AppController {
  
  function executeIndex(AppView $view) {
    //$view = new AppView('login/index.tpl');
    $view->render();
  }
  
  function executeAuth() {
    $user = new stdClass();
    $user->id = 1;
    
    $_SESSION[SESSION_USER_NAME] = $user;
    $_SESSION['logged'] = true;
    
    // segurança
    $this->session->interrupt();
    
    $this->Response->redirect('');
  }
  
  function executeLogout() {
    session_destroy();
    //$this->session->destroy();
    $this->Response->redirect('');
  }
  
}