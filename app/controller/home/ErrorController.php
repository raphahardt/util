<?php

namespace App\controller\home;

use Djck\Core;
use App\controller\AppController;

Core::uses('AppController', 'App');

/**
 * Description of HomeController
 *
 * @author usuario
 */
class ErrorController extends AppController {
  
  function index() {
    echo $this->request->referer();
  }
  
}