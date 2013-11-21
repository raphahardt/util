<?php

Core::uses('AppController', 'controller');

/**
 * Description of HomeController
 *
 * @author usuario
 */
class ErrorController extends AppController {
  
  function index() {
    echo '<h1>404 Not Found, little padawan</h1>';
  }
  
}