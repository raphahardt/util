<?php

namespace App\controller\home;

use Djck\Core;
use App\controller\AppController;

Core::uses('AppController', 'App\controller');

/**
 * Description of HomeController
 *
 * @author usuario
 */
class ErrorController extends AppController {
  
  function executeIndex() {
    echo $this->Request->referer();
  }
  
}