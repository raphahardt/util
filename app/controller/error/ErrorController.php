<?php

namespace App\controller\error;

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
    echo '<h1>404 Not Found, little padawan</h1>';
  }
  
}